<?php

namespace App\Livewire\Tenant\Settings\WhatsMark;

use App\Models\Tenant\BotFlow;
use App\Models\Tenant\MessageBot;
use App\Models\Tenant\TemplateBot;
use Corbital\LaravelEmails\Services\MergeFieldsService;
use Livewire\Component;

class OptInOutSettings extends Component
{
    public ?bool $opt_out_enabled = false;

    public $trigger_keyword_opt_out = [];

    public $opt_out_message = '';

    public $trigger_keyword_opt_in = [];

    public $opt_in_message = '';

    public array $used_triggers = [];

    public $mergeFields;

    protected function rules()
    {
        return [
            'opt_out_enabled' => 'nullable|boolean',

            'trigger_keyword_opt_out' => [
                'nullable',
                'required_if:opt_out_enabled,true',
                function ($attribute, $value, $fail) {
                    foreach ((array) $value as $keyword) {
                        $keywordLower = strtolower($keyword);

                        // Check against used triggers in all bots
                        if (in_array($keywordLower, $this->used_triggers)) {
                            $fail("The keyword '{$keyword}' already exists in bot.");
                        }

                        // Check against opt-in keywords
                        if (in_array($keywordLower, array_map('strtolower', $this->trigger_keyword_opt_in))) {
                            $fail("The keyword '{$keyword}' is already used in Opt-In keywords.");
                        }
                    }
                },
            ],

            'opt_out_message' => 'nullable|string',

            'trigger_keyword_opt_in' => [
                'nullable',
                'required_if:opt_out_enabled,true',

                function ($attribute, $value, $fail) {
                    foreach ((array) $value as $keyword) {
                        $keywordLower = strtolower($keyword);

                        // Check against used triggers in bots
                        if (in_array($keywordLower, $this->used_triggers)) {
                            $fail("The keyword '{$keyword}' already exists in bot.");
                        }

                        // Check against opt-out keywords
                        if (in_array($keywordLower, array_map('strtolower', $this->trigger_keyword_opt_out))) {
                            $fail("The keyword '{$keyword}' is already used in Opt-Out keywords.");
                        }
                    }
                },
            ],

            'opt_in_message' => 'nullable|string',
        ];
    }

    public function mount()
    {
        if (! checkPermission('tenant.whatsmark_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(tenant_route('tenant.dashboard'));
        }

        $this->used_triggers = $this->get_available_keywords();

        $settings = tenant_settings_by_group('whats-mark');

        $this->opt_out_enabled = $settings['opt_out_enabled'] ?? false;
        $this->trigger_keyword_opt_out = $settings['trigger_keyword_opt_out'] ?? [];
        $this->opt_out_message = $settings['opt_out_message'] ?? '';
        $this->trigger_keyword_opt_in = $settings['trigger_keyword_opt_in'] ?? [];
        $this->opt_in_message = $settings['opt_in_message'] ?? '';

        $this->loadMergeFields();
    }

    public function save()
    {
        if (checkPermission('tenant.whatsmark_settings.edit')) {
            $this->validate();

            $settings = tenant_settings_by_group('whats-mark');

            $originalSettings = [
                'opt_out_enabled' => $settings['opt_out_enabled'] ?? false,
                'trigger_keyword_opt_out' => $settings['trigger_keyword_opt_out'] ?? [],
                'opt_out_message' => $settings['opt_out_message'] ?? '',
                'trigger_keyword_opt_in' => $settings['trigger_keyword_opt_in'] ?? [],
                'opt_in_message' => $settings['opt_in_message'] ?? '',
            ];

            $modifiedSettings = [];

            foreach ($originalSettings as $key => $originalValue) {
                $newValue = $this->{$key};

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

    private function get_available_keywords(): array
    {
        $template_bot = TemplateBot::where('tenant_id', tenant_id())->pluck('trigger')->toArray();
        $message_bot = MessageBot::where('tenant_id', tenant_id())->pluck('trigger')->toArray();
        $flows = BotFlow::where('tenant_id', tenant_id())->pluck('flow_data')->toArray();

        $triggers = [];

        // From TemplateBot and MessageBot
        $all_simple_triggers = array_merge($template_bot, $message_bot);
        foreach ($all_simple_triggers as $trigger) {
            if (! empty($trigger)) {
                $triggers = array_merge($triggers, explode(',', $trigger));
            }
        }

        // From BotFlow
        foreach ($flows as $json) {
            $flow = json_decode($json, true);
            if (! isset($flow['nodes']) || ! is_array($flow['nodes'])) {
                continue;
            }

            foreach ($flow['nodes'] as $node) {
                if (($node['type'] ?? '') === 'trigger') {
                    $outputs = $node['data']['output'] ?? [];
                    foreach ($outputs as $output) {
                        if (! empty($output['trigger'])) {
                            $triggers = array_merge($triggers, explode(',', $output['trigger']));
                        }
                    }
                }
            }
        }

        return array_unique(array_filter(array_map(fn ($t) => strtolower($t), $triggers)));

    }

    public function loadMergeFields()
    {
        $mergeFieldsService = app(MergeFieldsService::class);

        $field = array_merge(
            $mergeFieldsService->getFieldsForTemplate('tenant-contact-group'),
            $mergeFieldsService->getFieldsForTemplate('tenant-other-group'),
        );

        $this->mergeFields = json_encode(array_map(fn ($value) => [
            'key' => ucfirst($value['name']),
            'value' => $value['key'],
        ], $field));

    }

    public function render()
    {
        return view('livewire.tenant.settings.whats-mark.opt-in-out-settings');
    }
}
