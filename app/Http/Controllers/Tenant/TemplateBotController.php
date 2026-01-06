<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TemplateBot;
use App\Models\Tenant\WhatsappTemplate;
use App\Rules\PurifiedInput;
use App\Services\FeatureService;
use Corbital\LaravelEmails\Services\MergeFieldsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TemplateBotController extends Controller
{
    protected $featureLimitChecker;

    public $tenant_id;

    public $tenant_subdomain;

    public function __construct(FeatureService $featureLimitChecker)
    {
        $this->featureLimitChecker = $featureLimitChecker;
        $this->tenant_id = tenant_id();
        $this->tenant_subdomain = tenant_subdomain_by_tenant_id($this->tenant_id);
    }

    /**
     * Show the template bot creator page
     */
    public function create($subdomain, $templatebotId = null)
    {

        if (! checkPermission(['tenant.template_bot.edit', 'tenant.template_bot.create'])) {
            return redirect()->to(tenant_route('tenant.dashboard'))
                ->with('error', t('access_denied_note'));
        }
        $subdomain = $this->tenant_subdomain;
        $hasReachedLimit = $this->featureLimitChecker->hasReachedLimit('template_bots');

        return view('tenant.template-bot.index', [
            'templatebotId' => $templatebotId,
            'subdomain' => $subdomain,
            'hasReachedLimit' => $hasReachedLimit,
        ]);
    }

    /**
     * Get initial data for the component
     */
    public function getData($subdomain, $templatebotId = null)
    {
        if (! checkPermission(['tenant.template_bot.edit', 'tenant.template_bot.create'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        $data = [
            'templates' => $this->getTemplates(),
            'mergeFields' => $this->getMergeFields(),
            'relationTypes' => \App\Enum\Tenant\WhatsAppTemplateRelationType::getRelationtype(),
            'replyTypes' => \App\Enum\Tenant\WhatsAppTemplateRelationType::getReplyType(),
            'metaExtensions' => get_meta_allowed_extension(),
            'templateBot' => null,
            'limitInfo' => $this->getLimitInfo(),
        ];

        // Load existing bot if editing
        if ($templatebotId) {
            $templateBot = TemplateBot::find($templatebotId);

            if (! $templateBot) {
                return response()->json([
                    'success' => false,
                    'message' => t('template_bot_not_found'),
                ], 404);
            }

            $data['templateBot'] = [
                'id' => $templateBot->id,
                'name' => $templateBot->name,
                'rel_type' => $templateBot->rel_type,
                'template_id' => $templateBot->template_id,
                'reply_type' => $templateBot->reply_type,
                'trigger' => $templateBot->trigger ? array_filter(explode(',', $templateBot->trigger)) : [],
                'header_params' => json_decode($templateBot->header_params ?? '[]', true),
                'body_params' => json_decode($templateBot->body_params ?? '[]', true),
                'footer_params' => json_decode($templateBot->footer_params ?? '[]', true),
                'filename' => $templateBot->filename,
                'file_url' => $templateBot->filename ? asset('storage/' . $templateBot->filename) : null,
                // cards_params is automatically cast to array by Laravel
                'cards_params' => $templateBot->cards_params ?? [],
                'card_variables' => $this->extractCardVariables($templateBot),
                'body_variables' => $this->extractBodyVariables($templateBot),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get available templates
     */
    protected function getTemplates()
    {
        return WhatsappTemplate::where('tenant_id', tenant_id())
            ->get()
            ->map(function ($template) {
                return [
                    'template_id' => $template->template_id,
                    'template_name' => $template->template_name,
                    'language' => $template->language,
                    'header_data_text' => $template->header_data_text,
                    'header_data_format' => $template->header_data_format,
                    'body_data' => $template->body_data,
                    'footer_data' => $template->footer_data,
                    'buttons_data' => $template->buttons_data,
                    'header_params_count' => $template->header_params_count,
                    'body_params_count' => $template->body_params_count,
                    'footer_params_count' => $template->footer_params_count,
                    'template_type' => strtolower(trim($template->template_type ?? 'header')),
                    'cards_json' => $template->cards_json,
                ];
            });
    }

    /**
     * Get merge fields for variable substitution
     */
    protected function getMergeFields()
    {
        $mergeFieldsService = app(MergeFieldsService::class);

        $fields = array_merge(
            $mergeFieldsService->getFieldsForTemplate('tenant-contact-group'),
            $mergeFieldsService->getFieldsForTemplate('tenant-other-group'),
        );

        // Add authentication-specific merge fields
        $authFields = [
            ['name' => 'OTP Code', 'key' => '{otp_code}'],
        ];

        $fields = array_merge($fields, $authFields);

        return array_map(fn($value) => [
            'key' => ucfirst($value['name']),
            'value' => $value['key'],
        ], $fields);
    }

    /**
     * Get feature limit information
     */
    protected function getLimitInfo()
    {
        $limit = $this->featureLimitChecker->getLimit('template_bots');
        $remaining = $this->featureLimitChecker->getRemainingLimit('template_bots', TemplateBot::class);
        $hasReached = $this->featureLimitChecker->hasReachedLimit('template_bots', TemplateBot::class);

        return [
            'limit' => $limit,
            'remaining' => $remaining,
            'hasReached' => $hasReached,
            'isUnlimited' => $remaining === null,
        ];
    }

    /**
     * Store or update a template bot
     */
    public function store(Request $request)
    {

        if (! checkPermission(['tenant.template_bot.edit', 'tenant.template_bot.create'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        // Validation rules
        $rules = [
            'id' => 'nullable|integer|exists:template_bots,id',
            'template_name' => ['required', 'string', 'min:3', 'max:100', new PurifiedInput(t('sql_injection_error'))],
            'rel_type' => 'required',
            'template_id' => ['required', 'integer'],
            'reply_type' => ['required', 'integer'],
            'headerInputs' => 'nullable|array',
            'headerInputs.*' => [new PurifiedInput(t('dynamic_input_error'))],
            'bodyInputs' => 'nullable|array',
            'bodyInputs.*' => [new PurifiedInput(t('dynamic_input_error'))],
            'footerInputs' => 'nullable|array',
            'footerInputs.*' => [new PurifiedInput(t('dynamic_input_error'))],
            'trigger' => 'nullable|array',
            'trigger.*' => 'required|string|min:2',
            'file' => 'nullable|file',
            // Carousel validation
            'cards_params' => 'nullable|string', // JSON string of processed cards
            'card_variables' => 'nullable|string',
            'body_variables' => 'nullable|string',
            'carousel_media.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,avi,mov,wmv|max:16384', // 16MB max
        ];

        // If reply type requires triggers, validate them
        if (in_array($request->reply_type, [1, 2])) {
            $rules['trigger'] = 'required|array|min:1';
        }

        // Custom validation for file uploads based on template type
        if ($this->requiresFileUpload($request->template_id)) {
            // Only require file if no existing filename and not updating
            if (! $request->id || ! $request->existing_filename) {
                $rules['file'] = 'required|file';
            }
        }

        $validator = Validator::make($request->all(), $rules, [
            'template_name.required' => t('bot_name_required'),
            'rel_type.required' => t('relation_type_required'),
            'template_id.required' => t('template_required'),
            'reply_type.required' => t('reply_type_required'),
            'trigger.required' => t('trigger_required'),
            'trigger.*.required' => t('trigger_keyword_required'),
            'file.required' => t('file_required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => t('validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }
        try {
            // Determine if we're creating or updating
            $templateBot = $request->id ? TemplateBot::findOrFail($request->id) : new TemplateBot;
            $isNewRecord = ! $templateBot->exists;

            // For new records, check if creating one more would exceed the limit
            if ($isNewRecord) {
                $limit = $this->featureLimitChecker->getLimit('template_bots');

                // Skip limit check if unlimited (-1) or no limit set (null)
                if ($limit !== null && $limit !== -1) {
                    $currentCount = TemplateBot::where('tenant_id', tenant_id())->count();

                    if ($currentCount >= $limit) {
                        return response()->json([
                            'success' => false,
                            'message' => t('template_bot_limit_reached_upgrade_plan'),
                        ], 403);
                    }
                }
            }

            // Handle file upload if present
            $filename = $templateBot->filename;
            if ($request->hasFile('file')) {
                // Delete old file if exists
                if (! empty($filename) && Storage::disk('public')->exists($filename)) {
                    Storage::disk('public')->delete($filename);
                }

                // Store new file and get path
                $file = $request->file('file');
                $filename = $file->store('tenant/' . tenant_id() . '/template-bot', 'public');
            }

            // Handle carousel media uploads and existing media URLs
            $uploadedMediaUrls = [];

            // Process new file uploads
            if ($request->hasFile('carousel_media')) {
                foreach ($request->file('carousel_media') as $cardIndex => $mediaFile) {
                    if ($mediaFile) {
                        // Store with a more organized naming convention
                        $extension = $mediaFile->getClientOriginalExtension();
                        $filename = 'card_' . $cardIndex . '_' . time() . '_' . uniqid() . '.' . $extension;
                        $mediaPath = $mediaFile->storeAs('tenant/' . tenant_id() . '/template-bot/carousel', $filename, 'public');
                        $uploadedMediaUrls[$cardIndex] = url('storage/' . $mediaPath);
                    }
                }
            }

            // Handle existing media URLs (from frontend existing_carousel_media)
            $preservedUrls = [];
            if ($request->has('existing_carousel_media') && ! empty($request->existing_carousel_media)) {
                $existingMediaUrls = json_decode($request->existing_carousel_media, true);

                if (is_array($existingMediaUrls)) {
                    // Merge existing URLs with uploaded URLs (new uploads take priority)
                    foreach ($existingMediaUrls as $cardIndex => $mediaUrl) {
                        // Only use existing URL if no new upload for this card
                        if (! isset($uploadedMediaUrls[$cardIndex])) {
                            $uploadedMediaUrls[$cardIndex] = $mediaUrl;
                            $preservedUrls[] = $mediaUrl; // Track URLs we want to keep

                        }
                    }
                }
            }

            // Handle old carousel media cleanup when updating (AFTER determining what to preserve)
            if (! $isNewRecord) {
                $this->cleanupOldCarouselMedia($templateBot, $preservedUrls);
            }

            // Set model attributes
            $templateBot->tenant_id = tenant_id();
            $templateBot->name = $request->template_name;
            $templateBot->rel_type = $request->rel_type;
            $templateBot->template_id = $request->template_id;
            $templateBot->reply_type = $request->reply_type;
            $templateBot->trigger = ($request->reply_type == 1 || $request->reply_type == 2)
                ? implode(',', $request->trigger ?? [])
                : null;

            $template = WhatsappTemplate::where('template_id', $request->template_id)
                ->where('tenant_id', tenant_id())
                ->first();

            $isCarouselTemplate = $template && strtolower($template->template_type) === 'carousel';

            if ($isCarouselTemplate) {
                // Handle carousel template data
                $cardsData = $this->processCarouselCards($request, $uploadedMediaUrls);

                // Store as proper JSON array, not double-encoded string
                $templateBot->cards_params = $cardsData;

                // Handle body variables for carousel templates
                $bodyVariables = $request->body_variables ? json_decode($request->body_variables, true) : [];
                $templateBot->body_params = json_encode(array_values(array_filter($bodyVariables)));

                // For carousel templates, header and footer params are usually empty
                $templateBot->header_params = json_encode([]);
                $templateBot->footer_params = json_encode([]);
            } else {
                // Handle regular template data
                $templateBot->header_params = json_encode(array_values(array_filter($request->headerInputs ?? [])));
                $templateBot->body_params = json_encode(array_values(array_filter($request->bodyInputs ?? [])));
                $templateBot->footer_params = json_encode(array_values(array_filter($request->footerInputs ?? [])));
                $templateBot->cards_params = null;
            }
            $templateBot->filename = $filename;

            // Save to database
            $templateBot->save();

            if ($isNewRecord) {
                $this->featureLimitChecker->trackUsage('template_bots');
            }

            return response()->json([
                'success' => true,
                'message' => $isNewRecord
                    ? t('template_bot_created_successfully')
                    : t('template_bot_updated_successfully'),
                'template_bot_id' => $templateBot->id,
                'redirect_url' => tenant_route('tenant.templatebot.list'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => t('error_saving_template_bot') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if the selected template requires a file upload
     */
    protected function requiresFileUpload($templateId)
    {
        if (! $templateId) {
            return false;
        }

        $template = WhatsappTemplate::find($templateId);
        if (! $template) {
            return false;
        }

        // Check if the template has a header format that requires a file
        $headerFormat = $template->header_format;

        return in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT']);
    }

    /**
     * Handle file upload with progress tracking
     */
    public function uploadFile(Request $request)
    {
        if (! checkPermission(['tenant.template_bot.edit', 'tenant.template_bot.create'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => t('validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $filename = $file->store('tenant/' . tenant_id() . '/template-bot', 'public');

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'file_url' => asset('storage/' . $filename),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => t('error_uploading_file') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process carousel cards data and merge with uploaded media URLs
     */
    private function processCarouselCards($request, $uploadedMediaUrls)
    {
        // Check if we have the processed cards data from frontend
        if ($request->has('cards_params') && ! empty($request->cards_params)) {
            // Frontend is sending the complete processed cards data
            $frontendCards = is_string($request->cards_params)
                ? json_decode($request->cards_params, true)
                : $request->cards_params;

            if (is_array($frontendCards)) {
                // Merge uploaded media URLs into the frontend cards data
                return $this->mergeMediaUrlsIntoCards($frontendCards, $uploadedMediaUrls);
            }
        }

        // Fallback: Process from template + variables + uploaded media
        $template = WhatsappTemplate::where('template_id', $request->template_id)
            ->where('tenant_id', tenant_id())
            ->first();

        if (! $template || ! $template->cards_json) {
            return [];
        }

        // Parse the original template cards
        $originalCards = is_string($template->cards_json)
            ? json_decode($template->cards_json, true)
            : $template->cards_json;

        if (! is_array($originalCards)) {
            return [];
        }

        // Get card variables if present
        $cardVariables = $request->card_variables ? json_decode($request->card_variables, true) : [];

        $processedCards = [];

        foreach ($originalCards as $cardIndex => $card) {
            if (! isset($card['components']) || ! is_array($card['components'])) {
                continue;
            }

            $processedCard = ['components' => []];

            foreach ($card['components'] as $component) {
                $processedComponent = $component;

                // Handle HEADER component with media
                if (
                    $component['type'] === 'HEADER' &&
                    in_array($component['format'] ?? '', ['IMAGE', 'VIDEO'])
                ) {
                    // Use uploaded media URL if available, otherwise keep original
                    if (isset($uploadedMediaUrls[$cardIndex])) {
                        $processedComponent['example'] = [
                            'header_handle' => [$uploadedMediaUrls[$cardIndex]],
                        ];
                    }
                }

                // Handle BODY component with variables
                if ($component['type'] === 'BODY' && isset($component['text'])) {
                    $bodyText = $component['text'];

                    // Replace variables in body text with actual values
                    if (isset($cardVariables[$cardIndex])) {
                        foreach ($cardVariables[$cardIndex] as $variableKey => $variableValue) {
                            // Replace both {{1}}, {{2}} format and {{variableKey}} format
                            $bodyText = str_replace([
                                '{{' . $variableKey . '}}',
                                '{{' . ($variableKey + 1) . '}}',
                            ], $variableValue, $bodyText);
                        }
                    }

                    $processedComponent['text'] = $bodyText;
                }

                $processedCard['components'][] = $processedComponent;
            }

            $processedCards[] = $processedCard;
        }

        return $processedCards;
    }

    /**
     * Extract card variables from saved carousel template data
     */
    private function extractCardVariables($templateBot)
    {
        if (! $templateBot->cards_params || ! is_array($templateBot->cards_params)) {
            return [];
        }

        // cards_params is automatically cast to array by Laravel model
        $cardsData = $templateBot->cards_params;
        $cardVariables = [];

        foreach ($cardsData as $cardIndex => $card) {
            if (! isset($card['components'])) {
                continue;
            }

            foreach ($card['components'] as $component) {
                if ($component['type'] === 'BODY' && isset($component['text'])) {
                    // Extract variables from the saved text
                    preg_match_all('/\{\{(\d+)\}\}/', $component['text'], $matches);

                    if (! empty($matches[1])) {
                        $cardVariables[$cardIndex] = [];

                        // Get the actual values from example.body_text
                        $exampleValues = $component['example']['body_text'][0] ?? [];

                        foreach ($matches[1] as $varIndex => $variableKey) {
                            // Map the variable key to its actual value from example
                            $cardVariables[$cardIndex][(int) $variableKey] = $exampleValues[$varIndex] ?? '';
                        }
                    }
                }
            }
        }

        return $cardVariables;
    }

    /**
     * Extract body variables from saved template data
     */
    private function extractBodyVariables($templateBot)
    {
        $bodyParams = json_decode($templateBot->body_params ?? '[]', true);
        $bodyVariables = [];

        // Convert array of values back to key-value pairs
        foreach ($bodyParams as $index => $value) {
            $bodyVariables[(string) ($index + 1)] = $value;
        }

        return $bodyVariables;
    }

    /**
     * Merge uploaded media URLs into processed cards data
     */
    private function mergeMediaUrlsIntoCards($cards, $uploadedMediaUrls)
    {
        if (empty($uploadedMediaUrls) || ! is_array($cards)) {
            return $cards;
        }

        foreach ($cards as $cardIndex => &$card) {
            if (! isset($card['components']) || ! is_array($card['components'])) {
                continue;
            }

            foreach ($card['components'] as &$component) {
                // Handle HEADER component with media
                if (
                    $component['type'] === 'HEADER' &&
                    in_array($component['format'] ?? '', ['IMAGE', 'VIDEO']) &&
                    isset($uploadedMediaUrls[$cardIndex])
                ) {
                    $component['example'] = [
                        'header_handle' => [$uploadedMediaUrls[$cardIndex]],
                    ];
                }
            }
        }

        return $cards;
    }

    /**
     * Clean up old carousel media files when updating a template bot
     * Only deletes files that are NOT in the preservedUrls array.
     */
    private function cleanupOldCarouselMedia($templateBot, $preservedUrls = [])
    {
        if (! $templateBot->cards_params || ! is_array($templateBot->cards_params)) {
            return;
        }

        $oldCards = $templateBot->cards_params;

        foreach ($oldCards as $card) {
            if (! empty($card['components']) && is_array($card['components'])) {
                foreach ($card['components'] as $component) {
                    // Handle HEADER components with media
                    if (
                        $component['type'] === 'HEADER' &&
                        isset($component['example']['header_handle']) &&
                        is_array($component['example']['header_handle'])
                    ) {

                        foreach ($component['example']['header_handle'] as $mediaUrl) {
                            if (! empty($mediaUrl)) {
                                // Skip deletion if this URL is being preserved
                                if (in_array($mediaUrl, $preservedUrls)) {

                                    continue;
                                }

                                // Convert full URL to storage path
                                $relativePath = str_replace([
                                    url('storage') . '/',
                                    url('/storage/'),
                                    config('app.url') . '/storage/',
                                ], '', $mediaUrl);

                                if (Storage::disk('public')->exists($relativePath)) {

                                    Storage::disk('public')->delete($relativePath);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
