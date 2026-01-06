<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Enum\Tenant\WhatsAppTemplateRelationType;
use App\Models\Tenant\MessageBot;
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

class MessageBotFilamentTable extends BaseFilamentTable
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

        return MessageBot::query()
            ->selectRaw('message_bots.*, (SELECT COUNT(*) FROM message_bots i2 WHERE i2.id <= message_bots.id AND i2.tenant_id = ?) as row_num', [$tenantId])
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
                ->label(t('type'))
                ->sortable()
                ->searchable()
                ->formatStateUsing(function ($state, $record) {
                    $replyData = WhatsAppTemplateRelationType::getReplyType((int) $record->reply_type);

                    return ucfirst($replyData ?? '');
                })
                ->toggleable(),

            TextColumn::make('trigger')
                ->label(t('trigger_keyword'))
                ->searchable()
                ->sortable()
                ->toggleable()
                ->formatStateUsing(function ($record) {
                    $replyTextArray = json_decode($record->trigger);

                    return is_array($replyTextArray) ? implode(', ', $replyTextArray) : $record->trigger;
                }),

            TextColumn::make('rel_type')
                ->label(t('relation_type'))
                ->sortable()
                ->searchable()
                ->toggleable()
                ->formatStateUsing(function (?string $state): string {
                    $label = t($state ?? 'N/A');

                    return match ($state) {
                        'lead' => <<<HTML
                <span class="bg-primary-100 text-primary-800 dark:text-primary-400 dark:bg-primary-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {$label}
                </span>
            HTML,
                        'customer' => <<<HTML
                <span class="bg-success-100 text-success-800 dark:text-success-400 dark:bg-success-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {$label}
                </span>
            HTML,
                        default => <<<HTML
                <span class="bg-danger-100 ring-1 ring-danger-300 text-danger-800 dark:bg-danger-800 dark:ring-danger-600 dark:text-danger-100 px-3 py-1 rounded-full text-xs font-semibold">
                    {$label}
                </span>
            HTML,
                    };
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
                    if (! checkPermission('tenant.message_bot.edit')) {
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
                ->dateTime()
                ->sortable()
                ->toggleable()
                ->since()
                ->tooltip(function (TextColumn $column): ?string {
                    return format_date_time($column->getState());
                }),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('rel_type')
                ->label(t('relation_type'))
                ->options([
                    'lead' => t('lead'),
                    'customer' => t('customer'),
                ]),

            SelectFilter::make('reply_type')
                ->label(t('reply_type'))
                ->options(collect(\App\Enum\Tenant\WhatsAppTemplateRelationType::getReplyType())
                    ->map(fn ($value, $key) => [
                        'value' => $key,
                        'label' => ucfirst($value['label'] ?? $value),
                    ])
                    ->pluck('label', 'value')
                    ->toArray()),

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
                            fn (Builder $query, $date): Builder => $query->whereDate('message_bots.created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('message_bots.created_at', '<=', $date),
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
                    ->url(fn (MessageBot $record) => tenant_route('tenant.messagebot.create', ['messagebotId' => $record->id]))
                    ->hidden(fn () => ! checkPermission('tenant.message_bot.edit')),

                Action::make('delete')
                    ->label('Delete')
                    ->action(fn (MessageBot $record) => $this->dispatch('confirmDelete', messagebotId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.message_bot.delete')),

                Action::make('clone')
                    ->label('Clone')
                    ->action(fn (MessageBot $record) => $this->dispatch('cloneRecord', messagebotId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.message_bot.clone')),
            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    #[On('cloneRecord')]
    public function cloneRecord($messagebotId)
    {
        if (checkPermission('tenant.message_bot.clone')) {
            // Check feature limit before cloning
            if ($this->featureLimitChecker->hasReachedLimit('message_bots', MessageBot::class)) {
                $this->notify([
                    'type' => 'warning',
                    'message' => t('message_bot_limit_reached_upgrade_plan'),
                ]);

                return;
            }

            $existingBot = MessageBot::findOrFail($messagebotId);
            if (! $existingBot) {
                $this->notify(['type' => 'info', 'message' => t('message_bot_not_found')]);

                return false;
            }

            $oldFilePath = $existingBot->filename;
            $newFilePath = null;

            if ($oldFilePath) {
                $folderPath = 'tenant/'.tenant_id().'/message-bot';
                $originalName = pathinfo($oldFilePath, PATHINFO_BASENAME);
                $newFilePath = $originalName;

                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->copy($oldFilePath, $newFilePath);
                } else {
                    $newFilePath = null;
                }
            }

            // Clone the bot and update the filename
            $cloneBot = $existingBot->replicate();
            $cloneBot->filename = $newFilePath;
            $this->featureLimitChecker->trackUsage('message_bots');
            $cloneBot->save();

            if ($cloneBot) {
                $this->notify(['type' => 'success', 'message' => t('bot_clone_successfully')], true);

                return redirect()->to(tenant_route('tenant.messagebot.create', ['messagebotId' => $cloneBot->id]));
            }
        }
    }

    #[On('message-bot-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
