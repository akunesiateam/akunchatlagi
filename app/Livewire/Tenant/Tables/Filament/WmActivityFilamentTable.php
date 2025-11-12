<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\WmActivityLog;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class WmActivityFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = true;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): Builder
    {
        $tenantId = tenant_id();

        return WmActivityLog::query()
            ->leftJoin('template_bots', function ($join) {
                $join->on('wm_activity_logs.category_id', '=', 'template_bots.id')
                    ->where('wm_activity_logs.category', '=', 'template_bot');
            })
            ->leftJoin('message_bots', function ($join) {
                $join->on('wm_activity_logs.category_id', '=', 'message_bots.id')
                    ->where('wm_activity_logs.category', '=', 'message_bot');
            })
            ->leftJoin('campaigns', function ($join) {
                $join->on('wm_activity_logs.category_id', '=', 'campaigns.id')
                    ->where('wm_activity_logs.category', '=', 'campaign');
            })
            ->leftJoin('whatsapp_templates', 'template_bots.template_id', '=', 'whatsapp_templates.template_id')
            ->where('wm_activity_logs.tenant_id', tenant_id())
            ->select(
                'wm_activity_logs.*',
                DB::raw('(SELECT COUNT(*) FROM wm_activity_logs i2 WHERE i2.id <= wm_activity_logs.id AND i2.tenant_id = '.$tenantId.') as row_num'),
                DB::raw("COALESCE(template_bots.name, message_bots.name, campaigns.name, '-') as name"),
                DB::raw("
                COALESCE(
                    CASE
                        WHEN wm_activity_logs.category = 'template_bot'
                            AND wm_activity_logs.category_id = template_bots.id
                            THEN (SELECT template_name FROM whatsapp_templates WHERE whatsapp_templates.template_id = template_bots.template_id LIMIT 1)
                        WHEN wm_activity_logs.category = 'campaign'
                            AND wm_activity_logs.category_id = campaigns.id
                            THEN (SELECT template_name FROM whatsapp_templates WHERE whatsapp_templates.template_id = campaigns.template_id LIMIT 1)
                        WHEN wm_activity_logs.category = 'Initiate Chat'
                            THEN (
                            SELECT template_name
                            FROM whatsapp_templates
                             WHERE whatsapp_templates.template_id = JSON_UNQUOTE(JSON_EXTRACT(wm_activity_logs.category_params, '$.templateId'))
                             LIMIT 1)
                        ELSE '-'
                    END, '-') as template_name
                ")
            );
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label(t('SR.NO'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('category')
                ->label(t('category'))
                ->sortable()
                ->toggleable()
                ->formatStateUsing(fn ($state) => t($state)),

            TextColumn::make('name')
                ->label(t('name'))
                ->toggleable()
                ->sortable(),

            TextColumn::make('template_name', 'whatsapp_templates.template_name')
                ->label(t('template_name'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->placeholder('-'),

            TextColumn::make('response_code')
                ->label(t('response_code'))
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    $responseCode = $record->response_code ?? 'N/A';

                    $class = match ($responseCode) {
                        '200' => 'bg-success-100 text-success-800 dark:text-success-400 dark:bg-success-900/20',
                        '400' => 'bg-danger-100 text-danger-800 dark:text-danger-400 dark:bg-danger-900/20',
                        default => 'bg-warning-100 text-warning-800 dark:text-warning-400 dark:bg-warning-900/20',
                    };

                    return '<div class="flex justify-center">
                    <span class="'.$class.' px-2.5 py-0.5 rounded-full text-xs font-medium">'
                        .htmlspecialchars($responseCode).
                        '</span>
            </div>';
                })
                ->sortable()
                ->searchable(),

            TextColumn::make('rel_type')
                ->label(t('relation_type'))
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    $type = $record->rel_type ?? 'N/A';

                    $class = match ($type) {
                        'lead' => 'bg-primary-100 text-primary-800 dark:text-primary-400 dark:bg-primary-900/20',
                        'customer' => 'bg-success-100 text-success-800 dark:text-success-400 dark:bg-success-900/20',
                        default => 'bg-gray-100 text-gray-800 dark:text-gray-400 dark:bg-gray-900/20',
                    };

                    return '<div class="flex justify-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium '.$class.'">'
                        .ucfirst($type).
                        '</span>
            </div>';
                })
                ->sortable()
                ->searchable(),

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

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->label(t('view'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center rounded-md',
                ])
                ->action(fn (WmActivityLog $record) => $this->dispatch('viewLogDetails', logId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->action(fn (WmActivityLog $record) => $this->dispatch('confirmDelete', logId: $record->id))
                ->hidden(fn () => ! checkPermission('tenant.activity_log.delete')),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('bulk_delete')
                ->label(t('bulk_delete'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 rounded shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->action(function (Collection $records) {
                    $this->dispatch('bulkDelete', [
                        'ids' => $records->pluck('id')->toArray(),
                    ]);
                })

                ->hidden(fn () => ! checkPermission('tenant.activity_log.delete')),
        ];
    }

    #[On('bulkDelete')]
    public function handleBulkDelete(array $ids): void
    {
        if (! empty($ids) && count($ids) !== 0) {
            $this->dispatch('confirmDelete', $ids['ids']);
        } else {
            $this->notify(['type' => 'danger', 'message' => t('no_contact_selected')]);
        }
    }

    #[On('wm-activity-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
