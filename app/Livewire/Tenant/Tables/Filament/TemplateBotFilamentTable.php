<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Enum\Tenant\WhatsAppTemplateRelationType;
use App\Models\Tenant\TemplateBot;
use App\Services\FeatureService;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class TemplateBotFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected FeatureService $featureLimitChecker;

    public function boot(FeatureService $featureLimitChecker): void
    {
        $this->featureLimitChecker = $featureLimitChecker;
    }

    protected function getTableQuery(): Builder
    {
        $tenantId = tenant_id();

        return TemplateBot::query()
            ->selectRaw('template_bots.*, (SELECT COUNT(*) FROM template_bots i2 WHERE i2.id <= template_bots.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('tenant_id', $tenantId);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label(t('SR.NO'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('name')
                ->label(t('name'))
                ->sortable()
                ->searchable()
                ->toggleable(),

            TextColumn::make('reply_type')
                ->label(t('reply_type'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->formatStateUsing(function ($record) {
                    return ucfirst(WhatsAppTemplateRelationType::getReplyType($record->reply_type) ?? '');
                }),

            TextColumn::make('trigger')
                ->label(t('trigger_keyword'))
                ->toggleable()
                ->searchable()
                ->sortable(),

            TextColumn::make('rel_type')
                ->label(t('relation_type'))
                ->searchable()
                ->sortable()
                ->toggleable()
                ->formatStateUsing(function (string $state): string {
                    $label = t($state);
                    $class = $state === 'lead'
                        ? 'bg-primary-100 text-primary-800 dark:text-primary-400 dark:bg-primary-900/20'
                        : 'bg-success-100 text-success-800 dark:text-success-400 dark:bg-success-900/20';

                    return <<<HTML
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}">
                {$label}
            </span>
        HTML;
                })
                ->html(),

            ToggleColumn::make('is_bot_active')
                ->label(t('active'))
                ->sortable()
                ->toggleable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.template_bot.edit')) {
                        return;
                    }

                    $record->is_bot_active = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = t('status_updated_successfully');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                }),

            TextColumn::make('created_at')
                ->label(t('created_at'))
                ->toggleable()
                ->dateTime()
                ->sortable()
                ->since()
                ->tooltip(function (TextColumn $column): ?string {
                    return format_date_time($column->getState());
                }),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('reply_type')
                ->label(t('reply_type'))
                ->options(collect(WhatsAppTemplateRelationType::getReplyType())
                    ->map(fn ($value, $key) => [
                        'value' => $key,
                        'label' => ucfirst($value['label'] ?? $value),
                    ])
                    ->pluck('label', 'value')
                    ->toArray()),

            SelectFilter::make('rel_type')
                ->label(t('relation_type'))
                ->options([
                    'lead' => t('lead'),
                    'customer' => t('customer'),
                ]),

            Filter::make('created_at')
                ->form([
                    DatePicker::make('created_from')
                        ->label(t('created_from'))
                        ->maxDate(now()),
                    DatePicker::make('created_until')
                        ->label(t('created_until'))
                        ->maxDate(now()),
                ])
                ->indicateUsing(function (array $data): ?string {
                    if (! $data['created_from'] && ! $data['created_until']) {
                        return null;
                    }

                    $indicator = [];

                    if ($data['created_from']) {
                        $indicator[] = t('created_from').': '.$data['created_from'];
                    }

                    if ($data['created_until']) {
                        $indicator[] = t('created_until').': '.$data['created_until'];
                    }

                    return implode(', ', $indicator);
                })
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('template_bots.created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('template_bots.created_at', '<=', $date),
                        );
                }),

        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('edit')
                    ->label('Edit')
                    ->url(fn (TemplateBot $record) => tenant_route('tenant.templatebot.create', ['templatebotId' => $record->id]))
                    ->hidden(fn () => ! checkPermission('tenant.template_bot.edit')),

                Action::make('delete')
                    ->label('Delete')
                    ->action(fn (TemplateBot $record) => $this->dispatch('confirmDelete', templatebotId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.template_bot.delete')),

                Action::make('clone')
                    ->label('Clone')
                    ->action(fn (TemplateBot $record) => $this->dispatch('cloneRecord', templatebotId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.template_bot.clone')),
            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    #[On('cloneRecord')]
    public function cloneRecord($templatebotId)
    {
        if (checkPermission('tenant.template_bot.clone')) {
            // Check feature limit before cloning
            if ($this->featureLimitChecker->hasReachedLimit('template_bots', TemplateBot::class)) {
                $this->notify([
                    'type' => 'warning',
                    'message' => t('template_bot_limit_reached_upgrade_plan'),
                ]);

                return;
            }

            $existingBot = TemplateBot::findOrFail($templatebotId);
            if (! $existingBot) {
                $this->notify(['type' => 'info', 'message' => t('template_bot_not_found')]);

                return false;
            }

            $oldFilePath = $existingBot->filename;
            $newFilePath = null;

            if ($oldFilePath) {
                $folderPath = 'tenant/'.tenant_id().'/template-bot';
                $fileName = pathinfo($oldFilePath, PATHINFO_BASENAME);

                $fileParts = explode('_', $fileName);
                $originalName = isset($fileParts[2]) ? $fileParts[2] : $fileName;
                $newFileName = time().'_'.$originalName;
                $newFilePath = $folderPath.'/'.$newFileName;

                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->copy($oldFilePath, $newFilePath);
                } else {
                    $newFilePath = null;
                }
            }

            // Clone the bot and update the filename
            $cloneBot = $existingBot->replicate();
            $cloneBot->filename = $newFilePath;
            $this->featureLimitChecker->trackUsage('template_bots');
            $cloneBot->save();

            if ($cloneBot) {
                $this->notify(['type' => 'success', 'message' => t('bot_clone_successfully')], true);

                return redirect()->to(tenant_route('tenant.templatebot.create', ['templatebotId' => $cloneBot->id]));
            }
        }
    }

    #[On('template-bot-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
