<?php

namespace Modules\SignAdmin\Livewire\Tenant\Settings\System;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class SignAdminSettings extends Component
{
    public bool $signature_enabled = false;
    public bool $show_in_admin_chat = true;
    public bool $show_in_whatsapp = true;
    public string $preview_signature = '';

    public function mount()
    {
        if (!checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect(route('admin.dashboard'));
        }

        $settings = tenant_settings_by_group('sign_admin');
        
        $this->signature_enabled = $settings['enabled'] ?? false;
        $this->show_in_admin_chat = $settings['show_in_admin_chat'] ?? true;
        $this->show_in_whatsapp = $settings['show_in_whatsapp'] ?? true;
        
        $this->updatePreview();
    }

    public function updatedSignatureEnabled()
    {
        $this->updatePreview();
    }

    public function updatePreview()
    {
    if ($this->signature_enabled) {
        $user = auth()->user();
        
        // Panggil service yang baru dibuat
        $signatureService = new \Modules\SignAdmin\Services\SignatureService();
        $this->preview_signature = $signatureService->getUserSignature($user->id, $user->tenant_id) ?? '';

    } else {
        $this->preview_signature = '';
    }
    }

    protected function rules()
    {
        return [
            'signature_enabled' => 'boolean',
            'show_in_admin_chat' => 'boolean', 
            'show_in_whatsapp' => 'boolean',
        ];
    }

    public function save()
    {
        if (checkPermission('system_settings.edit')) {
            $this->validate();

            $originalSettings = tenant_settings_by_group('sign_admin');

            $newSettings = [
                'enabled' => $this->signature_enabled,
                'show_in_admin_chat' => $this->show_in_admin_chat,
                'show_in_whatsapp' => $this->show_in_whatsapp,
            ];

            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                return !array_key_exists($key, $originalSettings) || $originalSettings[$key] !== $value;
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($modifiedSettings)) {
                foreach ($modifiedSettings as $key => $value) {
                    save_tenant_setting('sign_admin', $key, $value);
                }

                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('SignAdmin::livewire.tenant.settings.system.sign-admin-settings');
    }
}