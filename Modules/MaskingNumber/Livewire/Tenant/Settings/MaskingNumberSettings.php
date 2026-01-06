<?php

namespace Modules\MaskingNumber\Livewire\Tenant\Settings;

use Livewire\Component;

class MaskingNumberSettings extends Component
{
    public bool $enabled = false;

    public function mount()
    {
        if (!checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect(route('admin.dashboard'));
        }

        $settings = tenant_settings_by_group('masking_number');
        $this->enabled = $settings['enabled'] ?? false;
    }

    protected function rules()
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function save()
    {
        if (checkPermission('system_settings.edit')) {
            $this->validate();

            save_tenant_setting('masking_number', 'enabled', $this->enabled);

            $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
        }
    }

    public function render()
    {
        return view('MaskingNumber::livewire.tenant.settings.masking-number-settings');
    }
}