<?php

namespace App\Livewire\Tenant\Settings\WhatsMark;

use App\Rules\PurifiedInput;
use Corbital\LaravelEmails\Services\MergeFieldsService;
use Livewire\Component;

class WhatsappSessionManagementSettings extends Component
{
    public ?bool $session_management_enabled = false;

    public $session_expiry_message = '';

    public $session_expiry_hours = 23;

    public $mergeFields;

    protected function rules()
    {
        return [
            'session_management_enabled' => 'nullable|boolean',
            'session_expiry_message' => [
                'required_if:session_management_enabled,true',
                'nullable',
                'string',
                'min:10',
                'max:1000',
            ],
            'session_expiry_hours' => [
                'required_if:session_management_enabled,true',
                'nullable',
                'integer',
                'min:1',
                'max:23',
                new PurifiedInput(t('sql_injection_error')),
            ],
        ];
    }

    public function mount()
    {
        if (! checkPermission('tenant.whatsmark_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(tenant_route('tenant.dashboard'));
        }

        $settings = tenant_settings_by_group('whats-mark');

        $this->session_management_enabled = $settings['session_management_enabled'] ?? false;
        $this->session_expiry_message = $settings['session_expiry_message'] ?? '';
        $this->session_expiry_hours = $settings['session_expiry_hours'] ?? 23;
        $this->loadMergeFields();

    }

    public function loadMergeFields()
    {
        $mergeFieldsService = app(MergeFieldsService::class);

        $field = array_merge(
            $mergeFieldsService->getFieldsForTemplate('tenant-contact-group'),
        );

        $this->mergeFields = json_encode(array_map(fn ($value) => [
            'key' => ucfirst($value['name']),
            'value' => $value['key'],
        ], $field));
    }

    public function save()
    {
        if (checkPermission('tenant.whatsmark_settings.edit')) {
            $this->validate();

            $settings = tenant_settings_by_group('whats-mark');

            $originalSettings = [
                'session_management_enabled' => $settings['session_management_enabled'] ?? false,
                'session_expiry_message' => $settings['session_expiry_message'] ?? '',
                'session_expiry_hours' => $settings['session_expiry_hours'] ?? 23,

            ];

            $modifiedSettings = [];

            foreach ($originalSettings as $key => $originalValue) {
                $newValue = $this->{$key};

                // If the value is new or changed, mark it for saving
                if (! array_key_exists($key, $settings) || $originalValue !== $newValue) {
                    $modifiedSettings[$key] = ! empty($newValue) ? $newValue : null;
                }
            }

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
    }

    public function render()
    {
        return view('livewire.tenant.settings.whats-mark.whatsapp-session-management-settings');
    }
}
