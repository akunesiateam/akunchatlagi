<?php

namespace Modules\Ecommerce\Livewire;

use Livewire\Component;
use Modules\Ecommerce\Models\WebhookLogs;

class WebhookLogsList extends Component
{
    public $confirmingDeletion = false;

    public $log_id = null;

    public bool $isBulckDelete = false;

    protected $listeners = [
        'confirmDelete' => 'confirmDelete',
        'viewLogDetails' => 'viewLogDetails',
    ];

    public function mount()
    {
        if (! checkPermission('tenant.ecommerce_webhook.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(tenant_route('tenant.dashboard'));
        }
    }

    public function viewLogDetails($logId = '')
    {
        return redirect()->to(tenant_route('tenant.webhooks.logs.details', [
            'logId' => $logId,
        ]));
    }

    public function updatedConfirmingDeletion($value)
    {
        if (! $value) {
            $this->js('window.pgBulkActions.clearAll()');
        }
    }

    public function confirmDelete($logId = '')
    {
        if (WebhookLogs::where('tenant_id', tenant_id())->count() === 0) {
            $this->notify([
                'type' => 'danger',
                'message' => 'No webhook logs found',
            ]);

            return;
        }
        $this->log_id = $logId;
        $this->isBulckDelete = is_array($this->log_id) && count($this->log_id) !== 1 ? true : false;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('tenant.ecommerce_webhook.delete')) {
            if (is_array($this->log_id) && count($this->log_id) !== 0) {
                $deletedCount = WebhookLogs::where('tenant_id', tenant_id())->whereIn('id', $this->log_id)->delete();
                $this->log_id = null;
                $this->js('window.pgBulkActions.clearAll()');
                $this->notify([
                    'type' => 'success',
                    'message' => $deletedCount.' webhook logs deleted successfully',
                ]);
            } elseif (! empty($this->log_id)) {
                $delete = WebhookLogs::where('tenant_id', tenant_id())->find($this->log_id);
                if ($delete) {
                    $delete->delete();
                    $this->notify(['type' => 'success', 'message' => 'Log deleted successfully']);
                } else {
                    $this->notify(['type' => 'danger', 'message' => 'Log not found']);
                }
            } else {
                $clearlog = WebhookLogs::where('tenant_id', tenant_id())->delete();
                $clearlog ? $this->notify(['type' => 'success', 'message' => 'All webhook logs cleared successfully'])
                    : $this->notify(['type' => 'danger', 'message' => 'No logs found to clear']);
            }

            $this->confirmingDeletion = false;
            $this->dispatch('pg:eventRefresh-webhook-logs-table');
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-webhook-logs-table');
    }

    public function render()
    {
        return view('Ecommerce::livewire.webhook-logs-list');
    }
}
