<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant\WhatsappTemplate;
use App\Rules\PurifiedInput;
use App\Traits\WhatsApp;
use Corbital\LaravelEmails\Services\MergeFieldsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\WebhookEndpoints;

class TemplateMappingController extends Controller
{
    use WhatsApp;
    // =================================================================
    // MAIN CRUD METHODS (Standard RESTful Routes)
    // =================================================================

    public $tenant_id;

    public $tenant_subdomain;

    public function __construct()
    {
        $this->tenant_id = tenant_id();
        $this->tenant_subdomain = tenant_subdomain_by_tenant_id($this->tenant_id);
    }

    /**
     * Show campaign edit form
     */
    public function edit(string $subdomain, $id)
    {

        // Check permissions
        if (! checkPermission(['tenant.ecommerce_webhook.edit'])) {
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        // Find webhook
        $webhook = WebhookEndpoints::where('tenant_id', tenant_id())->findOrFail($id);
        // Check if test_payload is empty
        if (empty($webhook->test_payload)) {
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('webhook_test_payload_missing', locale: app()->getLocale())
                    ?? 'Webhook test payload is missing. Please configure it before editing the mapping.',
            ]);

            return redirect()->to(tenant_route('tenant.webhooks.index'));
        }
        // Load form data with webhook
        $formData = $this->getFormInitialData($webhook);

        return view('Ecommerce::template-mapping', array_merge($formData, ['webhook' => $webhook]));
    }

    /**
     * Update existing webhook
     */
    public function update(Request $request, $subdoamin, int $id): JsonResponse|RedirectResponse
    {
        // Check permissions
        if (! checkPermission(['tenant.ecommerce_webhook.edit'])) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => t('access_denied')], 403);
            }
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        try {
            DB::beginTransaction();

            $webhook = WebhookEndpoints::where('tenant_id', tenant_id())->findOrFail($id);

            // Validate the request
            $validatedData = $this->validateCampaignData($request, $id);

            // Handle file upload if present
            $filename = $this->handleTemplateMappingFileUpload($request, $webhook);
            // Update template mapping
            $webhook->update([
                'template_id' => $validatedData['template_id'],
                'phone_extraction_config' => $validatedData['phone_extraction_config'] ?? '',
                'header_params' => $this->encodeParams($validatedData['headerInputs'] ?? []),
                'body_params' => $this->encodeParams($validatedData['bodyInputs'] ?? []),
                'footer_params' => $this->encodeParams($validatedData['footerInputs'] ?? []),
                'filename' => $filename ?? $webhook->filename,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => t('webhook_updated_successfully'),
                    'redirect' => tenant_route('tenant.webhooks.index'),
                ]);
            }

            return redirect()->tenant_route('tenant.webhooks.index')
                ->with('success', t('webhook_updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            app_log(t('webhook_update_failed'), 'error', $e, [
                'webhook_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => t('webhook_update_failed').': '.$e->getMessage(),
                ], 500);
            }

            return back()->withInput()
                ->with('error', t('webhook_update_failed').': '.$e->getMessage());
        }
    }

    /**
     * Get template data (AJAX)
     */
    public function getTemplate(Request $request): JsonResponse
    {
        try {
            $templateId = $request->input('template_id');
            $template = WhatsappTemplate::where('template_id', $templateId)->firstOrFail();

            $templateData = $this->processTemplateData($template);

            return response()->json([
                'success' => true,
                'data' => $templateData,
            ]);
        } catch (\Exception $e) {
            app_log('Failed to load template', 'error', $e, [
                'template_id' => $request->input('template_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => t('failed_to_load_template').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle file upload (AJAX)
     */
    public function uploadFile(Request $request): JsonResponse
    {
        try {
            // Validate file
            $this->validateFileUpload($request);

            // Handle upload
            $filename = $this->processFileUpload($request->file('file'), $request->input('type'));

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'url' => Storage::disk('public')->url($filename),
                'message' => t('file_uploaded_successfully'),
            ]);
        } catch (\Exception $e) {
            app_log('File upload failed', 'error', $e, [
                'error' => $e->getMessage(),
                'file_name' => $request->file('file')?->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => t('file_upload_failed').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get initial form data
     */
    private function getFormInitialData(?WebhookEndpoints $webhook = null): array
    {
        // Decode existing parameters if webhook exists
        $existingVariables = [
            'header' => [],
            'body' => [],
            'footer' => [],
        ];

        $existingFile = null;

        if ($webhook) {
            $existingVariables['header'] = json_decode($webhook->header_params ?? '[]', true) ?: [];
            $existingVariables['body'] = json_decode($webhook->body_params ?? '[]', true) ?: [];
            $existingVariables['footer'] = json_decode($webhook->footer_params ?? '[]', true) ?: [];

            // Get existing file info
            if ($webhook->filename) {
                $existingFile = [
                    'filename' => $webhook->filename,
                    'url' => Storage::disk('public')->url($webhook->filename),
                ];
            }
        }

        return [
            'templates' => WhatsappTemplate::where(['status' => 'APPROVED', 'tenant_id' => tenant_id()])->get(),
            'mergeFields' => $this->getMergeFieldsData($webhook),
            'webhook' => $webhook,
            'existingVariables' => $existingVariables,
            'existingFile' => $existingFile,
            'isEditMode' => $webhook ? true : false,
        ];
    }

    /**
     * Get merge fields data
     */
    private function getMergeFieldsData(?WebhookEndpoints $webhook = null): string
    {
        $data = $webhook->test_payload ?? [];

        // 1. Existing merge fields from your service
        $mergeFieldsService = app(MergeFieldsService::class);
        $existingFields = $mergeFieldsService->getFieldsForTemplate('tenant-other-group');

        $staticFields = array_map(fn ($field) => [
            'key' => ucfirst($field['name']),
            'value' => $field['key'],
        ], $existingFields);

        // 2. Dynamic merge fields from JSON
        $dynamicFields = $this->generateMergeFieldsFromJson($data);

        // Merge both arrays
        $allFields = array_merge($staticFields, $dynamicFields);

        return json_encode($allFields, JSON_PRETTY_PRINT);
    }

    // Helper function to convert numbers to ordinal words
    private function numberToOrdinal(int $number): string
    {
        $ordinals = [
            0 => 'first',
            1 => 'second',
            2 => 'third',
            3 => 'fourth',
            4 => 'fifth',
            5 => 'sixth',
            6 => 'seventh',
            7 => 'eighth',
            8 => 'ninth',
            9 => 'tenth',
        ];

        return $ordinals[$number] ?? ($number + 1).'th';
    }

    // Enhanced function for generating dynamic merge fields from JSON
    private function generateMergeFieldsFromJson(array $data, string $prefix = '', string $displayPrefix = ''): array
    {
        $fields = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix.'.'.$key : $key;
            $displayKey = $displayPrefix ? $displayPrefix.' '.$key : $key;

            if (is_array($value)) {
                // Handle numeric arrays (e.g., items[])
                if (array_keys($value) === range(0, count($value) - 1)) {
                    foreach ($value as $index => $item) {
                        $ordinal = $this->numberToOrdinal($index);
                        $arrayKey = $fullKey.'['.$index.']';
                        $arrayDisplayKey = $displayKey.' '.$ordinal;
                        $fields = array_merge(
                            $fields,
                            is_array($item) ? $this->generateMergeFieldsFromJson(
                                $item,
                                $arrayKey,
                                $arrayDisplayKey
                            ) : []
                        );
                    }
                } else {
                    // Handle associative arrays
                    $fields = array_merge(
                        $fields,
                        $this->generateMergeFieldsFromJson(
                            $value,
                            $fullKey,
                            $displayKey
                        )
                    );
                }
            } else {
                $fields[] = [
                    'key' => ucwords(str_replace(['.', '[', ']', '_'], ' ', $displayKey)),
                    'value' => '{'.$fullKey.'}',
                ];
            }
        }

        return $fields;
    }

    // =================================================================
    // VALIDATION METHODS
    // =================================================================

    /**
     * Validate campaign data
     */
    private function validateCampaignData(Request $request, ?int $campaignId = null): array
    {
        if ($request->has('file') && is_string($request->input('file'))) {
            $request->request->remove('file');
        }

        $rules = [
            'template_id' => 'required|exists:whatsapp_templates,template_id',
            'phone_extraction_config' => ['required', 'string', new PurifiedInput(t('dynamic_input_error'))],
            'headerInputs' => 'array',
            'bodyInputs' => 'array',
            'footerInputs' => 'array',
        ];

        // Only add file validation if there's an actual file upload
        if ($request->hasFile('file')) {
            $rules['file'] = 'nullable|file';
        }

        $validatedData = $request->validate($rules);

        return $validatedData;
    }

    /**
     * Process template data for frontend
     */
    private function processTemplateData(WhatsappTemplate $template): array
    {
        return [
            'id' => $template->template_id,
            'name' => $template->template_name,
            'language' => $template->language,
            'header' => [
                'format' => $template->header_data_format ?? 'TEXT',
                'text' => $template->header_data_text ?? '',
                'params_count' => $template->header_params_count ?? 0,
            ],
            'body' => [
                'text' => $template->body_data ?? '',
                'params_count' => $template->body_params_count ?? 0,
            ],
            'footer' => [
                'text' => $template->footer_data ?? '',
                'params_count' => $template->footer_params_count ?? 0,
            ],
            'buttons' => $this->parseTemplateButtons($template->buttons_data),
            'allowed_file_types' => $this->getAllowedFileTypes($template->header_data_format),
            'max_file_size' => $this->getMaxFileSize($template->header_data_format),
        ];
    }

    /**
     * Parse template buttons data
     */
    private function parseTemplateButtons(?string $buttonsData): array
    {
        if (empty($buttonsData)) {
            return [];
        }

        try {
            $buttons = json_decode($buttonsData, true);

            return is_array($buttons) ? $buttons : [];
        } catch (\Exception $e) {
            app_log('Failed to parse template buttons', 'warning', $e, [
                'buttons_data' => $buttonsData,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get allowed file types based on template format
     */
    private function getAllowedFileTypes(?string $format): array
    {
        $extensions = get_meta_allowed_extension();

        return match ($format) {
            'IMAGE' => [
                'extensions' => $extensions['image']['extension'] ?? '.jpeg,.png',
                'accept' => 'image/*',
            ],
            'VIDEO' => [
                'extensions' => $extensions['video']['extension'] ?? '.mp4,.3gp',
                'accept' => 'video/*',
            ],
            'DOCUMENT' => [
                'extensions' => $extensions['document']['extension'] ?? '.pdf,.doc,.docx',
                'accept' => '.pdf,.doc,.docx,.txt,.xls,.xlsx,.ppt,.pptx',
            ],
            'AUDIO' => [
                'extensions' => $extensions['audio']['extension'] ?? '.mp3,.aac',
                'accept' => 'audio/*',
            ],
            default => [
                'extensions' => '',
                'accept' => '',
            ]
        };
    }

    /**
     * Get max file size based on template format
     */
    private function getMaxFileSize(?string $format): int
    {
        $extensions = get_meta_allowed_extension();

        return match ($format) {
            'IMAGE' => ($extensions['image']['size'] ?? 5) * 1024 * 1024, // Convert MB to bytes
            'VIDEO' => ($extensions['video']['size'] ?? 16) * 1024 * 1024,
            'DOCUMENT' => ($extensions['document']['size'] ?? 100) * 1024 * 1024,
            'AUDIO' => ($extensions['audio']['size'] ?? 16) * 1024 * 1024,
            default => 5 * 1024 * 1024 // 5MB default
        };
    }

    /**
     * Encode parameters array to JSON
     */
    private function encodeParams(array $params): string
    {
        return json_encode(array_values(array_filter($params)));
    }

    // =================================================================
    // FILE HANDLING METHODS
    // =================================================================

    /**
     * Handle template mapping file upload
     */
    private function handleTemplateMappingFileUpload(Request $request, ?WebhookEndpoints $existingWebhook = null): ?string
    {
        $file = $request->file('file');
        $templateId = $request->input('template_id');

        // Get template to determine file type requirements
        $template = WhatsappTemplate::where('template_id', $templateId)->first();

        if (! $template) {
            throw new \Exception(t('template_not_found'));
        }

        if (! $request->hasFile('file')) {
            return ($template->header_data_format === 'TEXT' || $template->header_data_format === null) ? '' : $existingWebhook?->filename;
        }

        // Validate file
        $this->validateFileUpload($request, $template->header_data_format);

        // Delete existing file if updating
        if ($existingWebhook && $existingWebhook->filename) {
            Storage::disk('public')->delete($existingWebhook->filename);
        }

        // Process upload
        return $this->processFileUpload($file, $template->header_data_format);
    }

    /**
     * Validate file upload
     */
    private function validateFileUpload(Request $request, ?string $expectedFormat = null): void
    {
        $file = $request->file('file');

        if (! $file || ! $file->isValid()) {
            throw new \Exception(t('invalid_file_upload'));
        }

        // Get file extension and MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Get allowed file types and sizes
        $extensions = get_meta_allowed_extension();

        // Validate based on expected format
        if ($expectedFormat) {
            $formatKey = strtolower($expectedFormat);

            if (! isset($extensions[$formatKey])) {
                throw new \Exception('Unsupported file format: '.$expectedFormat);
            }

            $allowedExtensions = explode(',', $extensions[$formatKey]['extension']);
            $allowedExtensions = array_map('trim', $allowedExtensions);
            $maxSize = $extensions[$formatKey]['size'] * 1024 * 1024; // Convert MB to bytes

            // Check extension
            if (! in_array('.'.$extension, $allowedExtensions)) {
                throw new \Exception('Invalid file extension. Allowed: '.implode(', ', $allowedExtensions));
            }

            // Check file size
            if ($fileSize > $maxSize) {
                throw new \Exception('File size too large. Maximum: '.$extensions[$formatKey]['size'].'MB');
            }

            // Validate MIME type for security
            $this->validateMimeType($mimeType, $formatKey);
        }
    }

    /**
     * Validate MIME type for security
     */
    private function validateMimeType(string $mimeType, string $format): void
    {
        $allowedMimeTypes = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'video' => ['video/mp4', 'video/3gpp', 'video/quicktime'],
            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
            ],
            'audio' => ['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/aac'],
        ];

        if (isset($allowedMimeTypes[$format]) && ! in_array($mimeType, $allowedMimeTypes[$format])) {
            throw new \Exception('Invalid file type. Detected: '.$mimeType);
        }
    }

    /**
     * Process file upload and return filename
     */
    private function processFileUpload(UploadedFile $file, string $format): string
    {
        // Generate secure filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug($originalName).'_'.time().'.'.$extension;

        // Determine storage directory
        $directory = match (strtolower($format)) {
            'image' => '/ecom_webhook/images',
            'video' => '/ecom_webhook/videos',
            'document' => '/ecom_webhook/documents',
            'audio' => '/ecom_webhook/audio',
            default => '/ecom_webhook'
        };

        // Store file
        $path = $file->storeAs('tenant/'.tenant_id().$directory, $filename, 'public');

        if (! $path) {
            throw new \Exception('Failed to store file');
        }

        return $path;
    }
}
