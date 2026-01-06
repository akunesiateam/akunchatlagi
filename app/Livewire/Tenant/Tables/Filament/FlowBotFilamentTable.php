<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\BotFlow;
use App\Services\FeatureService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class FlowBotFilamentTable extends BaseFilamentTable
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

        return BotFlow::query()
            ->selectRaw('bot_flows.*, (SELECT COUNT(*) FROM bot_flows i2 WHERE i2.id <= bot_flows.id AND i2.tenant_id = ?) as row_num', [$tenantId])
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
                ->toggleable()
                ->searchable()
                ->sortable(),

            TextColumn::make('description')
                ->label('Description')
                ->toggleable()
                ->searchable()
                ->sortable()
                ->limit(100)
                ->tooltip(function (TextColumn $column): ?string {
                    $state = $column->getState();

                    return strlen($state) > 100 ? $state : null;
                }),

            ToggleColumn::make('is_active')
                ->label(t('active'))
                ->toggleable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.bot_flow.edit')) {
                        return;
                    }

                    $record->is_active = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = t('status_updated_successfully');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                }),

        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('flow')
                    ->label(t('flow'))
                    ->hidden(fn () => ! checkPermission('tenant.bot_flow.view'))
                    ->action(fn (BotFlow $record) => $this->dispatch('editRedirect', flowId: $record->id)),

                Action::make('clone')
                    ->label(t('Clone'))
                    ->hidden(fn () => ! checkPermission('tenant.bot_flow.create'))
                    ->action(fn (BotFlow $record) => $this->dispatch('confirmClone', flowId: $record->id)),

                Action::make('export')
                    ->label(t('Export'))
                    ->hidden(fn () => ! checkPermission('tenant.bot_flow.view'))
                    ->action(fn (BotFlow $record) => $this->exportFlow($record->id)),

                Action::make('edit')
                    ->label(t('edit'))
                    ->hidden(fn () => ! checkPermission('tenant.bot_flow.edit'))
                    ->action(fn (BotFlow $record) => $this->dispatch('editFlow', flowId: $record->id)),

                Action::make('delete')
                    ->label(t('delete'))
                    ->hidden(fn () => ! checkPermission('tenant.bot_flow.delete'))
                    ->action(fn (BotFlow $record) => $this->dispatch('confirmDelete', flowId: $record->id)),
            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    /**
     * Export a flow
     */
    public function exportFlow(int $flowId): void
    {
        $this->dispatch('export-flow', flowId: $flowId);
    }

    #[On('edit')]
    public function edit($rowId): void
    {
        $this->redirect(tenant_route('tenant.bot_flows.edit', ['id' => $rowId]));
    }

    #[On('flow-bot-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
