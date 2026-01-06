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
        'ar_EG' => 'Arabic (Egypt)',
        'ar_AE' => 'Arabic (UAE)',
        'ar_LB' => 'Arabic (Lebanon)',
        'ar_MA' => 'Arabic (Morocco)',
        'ar_QA' => 'Arabic (Qatar)',
        'az' => 'Azerbaijani',
        'be_BY' => 'Belarusian',
        'bn' => 'Bengali',
        'bn_IN' => 'Bengali (India)',
        'bg' => 'Bulgarian',
        'ca' => 'Catalan',
        'zh_CN' => 'Chinese (CHN)',
        'zh_HK' => 'Chinese (Hong Kong)',
        'zh_TW' => 'Chinese (Taiwan)',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'prs_AF' => 'Dari (Afghanistan)',
        'nl' => 'Dutch',
        'nl_BE' => 'Dutch (Belgium)',
        'en' => 'English',
        'en_GB' => 'English (UK)',
        'en_US' => 'English (US)',
        'en_AE' => 'English (UAE)',
        'en_AU' => 'English (Australia)',
        'en_CA' => 'English (Canada)',
        'en_GH' => 'English (Ghana)',
        'en_IE' => 'English (Ireland)',
        'en_IN' => 'English (India)',
        'en_JM' => 'English (Jamaica)',
        'en_MY' => 'English (Malaysia)',
        'en_NZ' => 'English (New Zealand)',
        'en_QA' => 'English (Qatar)',
        'en_SG' => 'English (Singapore)',
        'en_UG' => 'English (Uganda)',
        'en_ZA' => 'English (South Africa)',
        'et' => 'Estonian',
        'fil' => 'Filipino',
        'fi' => 'Finnish',
        'fr' => 'French',
        'fr_BE' => 'French (Belgium)',
        'fr_CA' => 'French (Canada)',
        'fr_CH' => 'French (Switzerland)',
        'fr_CI' => 'French (Ivory Coast)',
        'fr_MA' => 'French (Morocco)',
        'ka' => 'Georgian',
        'de' => 'German',
        'de_AT' => 'German (Austria)',
        'de_CH' => 'German (Switzerland)',
        'el' => 'Greek',
        'gu' => 'Gujarati',
        'ha' => 'Hausa',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'id' => 'Indonesian',
        'ga' => 'Irish',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'kn' => 'Kannada',
        'kk' => 'Kazakh',
        'rw_RW' => 'Kinyarwanda',
        'ko' => 'Korean',
        'ky_KG' => 'Kyrgyz (Kyrgyzstan)',
        'lo' => 'Lao',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'mk' => 'Macedonian',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mr' => 'Marathi',
        'nb' => 'Norwegian',
        'ps_AF' => 'Pashto (Afghanistan)',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_PT' => 'Portuguese (Portugal)',
        'pa' => 'Punjabi',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sr' => 'Serbian',
        'si_LK' => 'Sinhala (Sri Lanka)',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'es' => 'Spanish',
        'es_AR' => 'Spanish (Argentina)',
        'es_CL' => 'Spanish (Chile)',
        'es_CO' => 'Spanish (Colombia)',
        'es_CR' => 'Spanish (Costa Rica)',
        'es_DO' => 'Spanish (Dominican Republic)',
        'es_EC' => 'Spanish (Ecuador)',
        'es_HN' => 'Spanish (Honduras)',
        'es_MX' => 'Spanish (Mexico)',
        'es_PA' => 'Spanish (Panama)',
        'es_PE' => 'Spanish (Peru)',
        'es_ES' => 'Spanish (Spain)',
        'es_UY' => 'Spanish (Uruguay)',
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
