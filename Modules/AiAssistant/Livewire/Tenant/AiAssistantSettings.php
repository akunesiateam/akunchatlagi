<?php

namespace Modules\AiAssistant\Livewire\Tenant;

use App\Rules\PurifiedInput;
use Livewire\Component;

class AiAssistantSettings extends Component
{
    public $ai_stop_keywords = [];

    public $ai_footer_message = '';

    public $ai_response_delay = 0;

    protected function rules()
    {
        return [
            'ai_stop_keywords' => ['nullable', 'array', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'ai_footer_message' => ['nullable', 'string', 'max:60', new PurifiedInput(t('sql_injection_error'))],
            'ai_response_delay' => 'nullable|numeric|min:0',
        ];
    }

    public function mount()
    {
        if (! checkPermission('tenant.whatsmark_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(tenant_route('tenant.dashboard'));
        }

        $settings = tenant_settings_by_group('whats-mark');
        $this->ai_stop_keywords = $settings['ai_stop_keywords'] ?? [];
        $this->ai_footer_message = $settings['ai_footer_message'] ?? '';
        $this->ai_response_delay = $settings['ai_response_delay'] ?? 0;
    }

    public function save()
    {
        if (! checkPermission('tenant.whatsmark_settings.edit')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(tenant_route('tenant.dashboard'));
        }

        $this->validate();

        $originalSettings = tenant_settings_by_group('whats-mark');

        $newSettings = [
            'ai_stop_keywords' => $this->ai_stop_keywords,
            'ai_footer_message' => $this->ai_footer_message,
            'ai_response_delay' => $this->ai_response_delay,
        ];

        $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
            return ! array_key_exists($key, $originalSettings) || $originalSettings[$key] !== $value;
        }, ARRAY_FILTER_USE_BOTH);

        if (! empty($modifiedSettings)) {
            foreach ($modifiedSettings as $key => $value) {
                save_tenant_setting('whats-mark', $key, $value);
            }

            $this->notify([
                'type' => 'success',
                'message' => t('setting_save_successfully'),
            ]);
        }
    }

    public function render()
    {
        return view('aiassistant::livewire.tenant.ai-assistant-settings');
    }
}
