<?php

namespace App\Livewire\Tenant\Settings\System;

use App\Models\Tenant\WhatsappTemplate;
use App\Rules\PurifiedInput;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MiscellaneousSettings extends Component
{
    public $tables_pagination_limit = 0;

    public $default_template = 'hello_world';

    public $default_template_language = 'en_US';

    const LANGUAGES = [
        'af' => 'Afrikaans',
        'sq' => 'Albanian',
        'ar' => 'Arabic',
        'az' => 'Azerbaijani',
        'bn' => 'Bengali',
        'bg' => 'Bulgarian',
        'ca' => 'Catalan',
        'zh_CN' => 'Chinese (Simplified)',
        'zh_HK' => 'Chinese (Traditional - Hong Kong)',
        'zh_TW' => 'Chinese (Traditional - Taiwan)',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'nl' => 'Dutch',
        'en_GB' => 'English (UK)',
        'en_US' => 'English (US)',
        'et' => 'Estonian',
        'fil' => 'Filipino',
        'fi' => 'Finnish',
        'fr' => 'French',
        'ka' => 'Georgian',
        'de' => 'German',
        'el' => 'Greek',
        'gu' => 'Gujarati',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'id' => 'Indonesian',
        'ga' => 'Irish',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'kn' => 'Kannada',
        'kk' => 'Kazakh',
        'ko' => 'Korean',
        'ky' => 'Kyrgyz',
        'lo' => 'Lao',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'mk' => 'Macedonian',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mr' => 'Marathi',
        'nb' => 'Norwegian',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_PT' => 'Portuguese (Portugal)',
        'pa' => 'Punjabi',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sr' => 'Serbian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'es' => 'Spanish',
        'es_MX' => 'Spanish (Mexico)',
        'sw' => 'Swahili',
        'sv' => 'Swedish',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        'vi' => 'Vietnamese',
        'zu' => 'Zulu',
    ];

    protected function rules()
    {
        return [
            'tables_pagination_limit' => ['nullable', 'integer', 'min:1', 'max:1000', new PurifiedInput(t('sql_injection_error'))],
            'default_template' => ['nullable', 'string'],
            'default_template_language' => ['required', Rule::in(array_keys(self::LANGUAGES))],
        ];
    }

    public function mount()
    {
        if (! checkPermission('tenant.system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $settings = tenant_settings_by_group('miscellaneous');

        $this->tables_pagination_limit = $settings['tables_pagination_limit'] ?? 0;
        $this->default_template = $settings['default_template'] ?? 'hello_world';
        $this->default_template_language = $settings['default_template_language'] ?? 'en_US';
    }

    public function save()
    {
        if (checkPermission('tenant.system_settings.edit')) {
            $this->validate();

            $originalSettings = tenant_settings_by_group('miscellaneous');

            $newSettings = [
                'tables_pagination_limit' => $this->tables_pagination_limit,
                'default_template' => $this->default_template,
                'default_template_language' => $this->default_template_language,
            ];

            // Filter only modified or undefined settings
            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                return ! array_key_exists($key, $originalSettings) || $originalSettings[$key] !== $value;
            }, ARRAY_FILTER_USE_BOTH);

            if (! empty($modifiedSettings)) {
                foreach ($modifiedSettings as $key => $value) {
                    save_tenant_setting('miscellaneous', $key, $value);
                }

                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    /**
     * Get available templates
     */
    public function getTemplatesProperty()
    {
        return WhatsappTemplate::where('tenant_id', tenant_id())->get();
    }

    public function getLanguagesProperty()
    {
        return self::LANGUAGES;
    }

    public function render()
    {
        return view('livewire.tenant.settings.system.miscellaneous-settings');
    }
}
