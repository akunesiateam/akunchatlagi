<?php

namespace Modules\Ecommerce\Livewire;

use Livewire\Component;
use Modules\Ecommerce\Models\WebhookLogs;

class WebhookLogDetails extends Component
{
    public $data;

    public $formattedPayload;

    public $formattedExtractedFields;

    public $formattedMetaResponse;

    public function mount($logId)
    {
        $this->data = WebhookLogs::with('webhookEndpoint')
            ->where('tenant_id', tenant_id())
            ->findOrFail($logId);

        // Format JSON data for display
        $this->formattedPayload = $this->formatJsonData($this->data->payload);
        $this->formattedExtractedFields = $this->formatJsonData($this->data->extracted_fields);
        $this->formattedMetaResponse = $this->formatJsonData($this->data->meta_response);
    }

    /**
     * Format JSON data for display
     */
    private function formatJsonData($data)
    {
        if (empty($data)) {
            return 'No data available';
        }

        // If it's already a string, try to decode it first
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            } else {
                // If it's not valid JSON, return as is
                return $data;
            }
        }

        // Format as pretty JSON
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function render()
    {
        return view('Ecommerce::livewire.webhook-log-details');
    }
}
