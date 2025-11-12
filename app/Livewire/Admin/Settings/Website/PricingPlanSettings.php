<?php

namespace App\Livewire\Admin\Settings\Website;

use App\Rules\PurifiedInput;
use Livewire\Component;

class PricingPlanSettings extends Component
{
    public ?string $plans_page_title = '';

    public ?string $plans_page_link = '';

    protected function rules()
    {
        return [
            'plans_page_title' => [
                empty($this->plans_page_link) ? 'nullable' : 'required',
                'string',
                'max:255',
                new PurifiedInput(t('sql_injection_error')),
            ],
            'plans_page_link' => [
                empty($this->plans_page_title) ? 'nullable' : 'required',
                'string',
                'url',
                new PurifiedInput(t('sql_injection_error')),
            ],
        ];
    }

    public function mount()
    {
        if (! checkPermission('admin.website_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }

        $settings = get_settings_by_group('theme');

        // 🔹 Plans Page Settings
        $this->plans_page_title = $settings->plans_page_title ?? '';
        $this->plans_page_link = $settings->plans_page_link ?? '';
    }

    public function save()
    {
        if (checkPermission('admin.website_settings.edit')) {
            $this->validate();

            $originalSettings = get_settings_by_group('theme');

            $newSettings = [
                // 🔹 Plans Page Settings
                'plans_page_title' => $this->plans_page_title,
                'plans_page_link' => $this->plans_page_link,
            ];

            // Filter only modified settings
            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                return $value !== $originalSettings->$key;
            }, ARRAY_FILTER_USE_BOTH);

            // Save only if there are changes
            if (! empty($modifiedSettings)) {
                set_settings_batch('theme', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.website.pricing-plan-settings');
    }
}
