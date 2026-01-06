<?php

namespace App\Livewire\Tenant\Components;

use App\Models\Tenant\Contact;
use App\Models\Tenant\WhatsappTemplate;
use App\Services\OtpService;
use App\Traits\WhatsApp;
use Livewire\Component;

class SendOtpButton extends Component
{
    use WhatsApp;

    public Contact $contact;

    public $selectedTemplate;

    public $otpCode;

    protected $rules = [
        'selectedTemplate' => 'required|exists:whatsapp_templates,id',
        'otpCode' => 'nullable|string|min:4|max:8',
    ];

    public function sendOtp()
    {
        $this->validate();

        $otpService = app(OtpService::class);

        // Get template
        $template = WhatsappTemplate::findOrFail($this->selectedTemplate);

        // Check rate limit
        $rateLimit = $otpService->checkRateLimit($this->contact->phone);
        if (! $rateLimit['allowed']) {
            $this->addError('rateLimit', $rateLimit['message']);

            return;
        }

        // Generate or use provided OTP
        if (empty($this->otpCode)) {
            $this->otpCode = $otpService->generate(6);
        }

        // Store OTP
        $expiryMinutes = $template->code_expiration_minutes ?? 10;
        $otpService->store($this->contact->phone, $this->otpCode, $expiryMinutes);

        // Send via WhatsApp
        $this->setWaTenantId(tenant_id());

        // Authentication templates require both body and button components with the OTP
        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $this->otpCode],
                ],
            ],
            [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => $this->otpCode],
                ],
            ],
        ];

        try {
            $response = $this->sendTemplateMessage(
                $this->contact->phone,
                $template->template_name,
                $template->language,
                $components
            );

            if ($response['status']) {
                // Increment rate limit
                $otpService->incrementRateLimit($this->contact->phone);

                // Log activity
                whatsapp_log('Manual OTP sent', 'info', [
                    'contact_id' => $this->contact->id,
                    'phone' => $this->contact->phone,
                    'template' => $template->template_name,
                ], null, tenant_id());

                $this->dispatch('otp-sent');
                $this->reset(['selectedTemplate', 'otpCode']);

                session()->flash('message', __('OTP sent successfully!'));
            } else {
                $this->addError('send', $response['message'] ?? __('Failed to send OTP'));
            }
        } catch (\Exception $e) {
            $this->addError('send', __('An error occurred while sending OTP'));

            whatsapp_log('Manual OTP send error', 'error', [
                'contact_id' => $this->contact->id,
                'error' => $e->getMessage(),
            ], $e, tenant_id());
        }
    }

    protected function sendTemplateMessage($phone, $templateName, $language, $components)
    {
        $whatsappApi = $this->loadConfig();

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $language],
                    'components' => $components,
                ],
            ];

            $response = $whatsappApi->sendMessage($payload);

            return [
                'status' => true,
                'data' => json_decode($response->body(), true),
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function render()
    {
        $authTemplates = WhatsappTemplate::where('tenant_id', tenant_id())
            ->where('category', 'AUTHENTICATION')
            ->where('status', 'APPROVED')
            ->get();

        return view('livewire.tenant.components.send-otp-button', [
            'authTemplates' => $authTemplates,
        ]);
    }
}
