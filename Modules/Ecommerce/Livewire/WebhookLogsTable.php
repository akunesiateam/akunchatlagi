<?php

namespace Modules\Ecommerce\Livewire;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Modules\Ecommerce\Models\WebhookLogs;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class WebhookLogsTable extends PowerGridComponent
{
    public string $tableName = 'webhook-logs-table';

    public string $sortField = 'webhook_activity_logs.created_at';

    public string $sortDirection = 'DESC';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.custom-loading';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->withoutLoading()
                ->showToggleColumns()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage(perPage: table_pagination_settings()['current'], perPageValues: table_pagination_settings()['options'])
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return WebhookLogs::query()
            ->leftJoin('webhook_endpoints', 'webhook_activity_logs.webhook_endpoint_id', '=', 'webhook_endpoints.id')
            ->where('webhook_activity_logs.tenant_id', tenant_id())
            ->select([
                'webhook_activity_logs.*',
                'webhook_endpoints.name as webhook_name',
                'webhook_endpoints.webhook_url',
            ]);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('webhook_name')
            ->add('recipient_phone')
            ->add('send_status')
            ->add('send_status_formatted', function ($model) {
                $statusColors = [
                    'sent' => 'success',
                    'failed' => 'danger',
                    'pending' => 'warning',
                ];

                $color = $statusColors[$model->send_status] ?? 'secondary';

                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
        bg-'.$color.'-100 text-'.$color.'-800">
        '.ucfirst($model->send_status).'
    </span>';
            })

            ->add('delivery_status')
            ->add('delivery_status_formatted', function ($model) {
                if (! $model->delivery_status) {
                    return '-';
                }

                $statusColors = [
                    'sent' => 'primary',
                    'delivered' => 'success',
                    'read' => 'success',
                    'failed' => 'danger',
                ];

                $color = $statusColors[$model->delivery_status] ?? 'secondary';

                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
        bg-'.$color.'-100 text-'.$color.'-800">
        '.ucfirst($model->delivery_status).'
    </span>';
            })

            ->add('created_at_formatted', function ($model) {
                return '<div class="relative group">
                        <span class="cursor-default" data-tippy-content="'.format_date_time($model->created_at).'">'
                    .Carbon::parse($model->created_at)->setTimezone(config('app.timezone'))->diffForHumans(['options' => Carbon::JUST_NOW]).'</span>
                    </div>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->searchable(),

            Column::make('Webhook Name', 'webhook_name')
                ->sortable()
                ->searchable(),

            Column::make('Recipient Phone', 'recipient_phone')
                ->sortable()
                ->searchable(),

            Column::make('Send Status', 'send_status_formatted', 'send_status')
                ->sortable()
                ->searchable(),

            Column::make('Delivery Status', 'delivery_status_formatted', 'delivery_status')
                ->sortable()
                ->searchable(),

            Column::make('Created At', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Action'),
        ];
    }

    public function filters(): array
    {
        return [
            // Add filters if needed
        ];
    }

    public function actions($row): array
    {
        $actions[] = Button::add('View')
            ->slot(t('view'))
            ->id()
            ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 rounded shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center')
            ->route('tenant.webhooks.logs.details', ['subdomain' => tenant_subdomain(), 'logId' => $row->id]);

        if (checkPermission('tenant.ecommerce_webhook.delete')) {
            $actions[] = Button::add('Delete')
                ->slot(t('delete'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 rounded shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center')
                ->dispatch('confirmDelete', ['logId' => $row->id]);
        }

        return empty($actions) ? ['-'] : $actions;
    }

    public function header(): array
    {
        $buttons = [];

        if (checkPermission('tenant.ecommerce_webhook.delete')) {
            $buttons[] = Button::add('bulk-delete')
                ->id()
                ->slot('Bulk Delete (<span x-text="window.pgBulkActions.count(\''.$this->tableName.'\')"></span>)')
                ->class('inline-flex items-center justify-center px-3 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-danger-600 text-white hover:bg-danger-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-600 absolute md:top-0 top-[116px] left-[100px] lg:left-[120px] sm:left-[136px] sm:top-0 whitespace-nowrap')
                ->dispatch('bulkDelete.'.$this->tableName, []);
        }

        return $buttons;
    }

    #[On('bulkDelete.{tableName}')]
    public function bulkDelete(): void
    {
        if (checkPermission('tenant.ecommerce_webhook.delete')) {
            $this->dispatch('confirmDelete', $this->checkboxValues);
        }
    }

    public function actionRules($row): array
    {
        return [
            // Hide/show actions based on conditions
        ];
    }
}
