<?php

namespace App\Livewire\Tenant\Contact;

use App\Models\Tenant\Chat;
use App\Models\Tenant\ChatMessage;
use App\Models\Tenant\Contact;
use App\Models\Tenant\ContactNote;
use App\Models\Tenant\Group;
use App\Models\Tenant\Source;
use App\Models\Tenant\Status;
use App\Models\Tenant\WhatsappTemplate;
use App\Models\User;
use App\Rules\PurifiedInput;
use App\Services\FeatureService;
use App\Traits\WhatsApp;
use App\Traits\WithTenantContext;
use Corbital\LaravelEmails\Services\MergeFieldsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class ContactList extends Component
{
    use WhatsApp, WithFileUploads, WithTenantContext;

    public Contact $contact;

    public ?int $contactId = null;

    public $confirmingDeletion = false;

    public $viewContactModal = false;

    public $showInitiateChatModal = false;

    public $showBulkActionsModal = false;

    // Bulk change fields
    public ?int $bulk_status_id = null;

    public ?int $bulk_source_id = null;

    public array $bulk_group_ids = [];

    public ?int $bulk_assigned_id = null;

    public $contact_id = null;

    public $template_id;

    public $headerInputs = [];

    public $bodyInputs = [];

    public $file;

    public $filename;

    public $footerInputs = [];

    public $contacts = [];

    public array $selectedStatus = [];

    public $notes = [];

    public $customFields;

    public bool $isBulckDelete = false;

    protected $featureLimitChecker;

    public $tenant_id;

    public $tenant_subdomain;

    public $mergeFields;

    public $whatsapp_settings;

    public $groups = [];

    protected $listeners = [
        'editContact' => 'editContact',
        'confirmDelete' => 'confirmDelete',
        'viewContact' => 'viewContact',
        'openViewModal' => 'viewContact',
        'initiateChat' => 'initiateChat',
        'bulkInitiateChatSending' => 'bulkInitiateChatSending',
        'refreshComponent' => '$refresh',
        'bulkActionsOnContacts' => 'bulkActionsOnContacts',
    ];

    public function boot(FeatureService $featureLimitChecker)
    {
        $this->featureLimitChecker = $featureLimitChecker;

        $this->bootWithTenantContext();
    }

    public function mount()
    {
        if (! checkPermission(['tenant.contact.view', 'tenant.contact.view_own'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->contact = new Contact;
        $this->tenant_id = tenant_id();
        $this->tenant_subdomain = tenant_subdomain_by_tenant_id($this->tenant_id);
        $this->groups = $this->getGroups();
    }

    public function createContact()
    {
        $this->redirect(tenant_route('tenant.contacts.save'));
    }

    public function viewContact($contactId)
    {
        // Initialize contact with correct table name
        if (! checkPermission('tenant.contact.view')) {
            $this->notify([
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }
        $this->contact = new Contact;
        $this->contact->setTable($this->tenant_subdomain.'_contacts');

        // Then load the contact
        $this->contact = Contact::fromTenant($this->tenant_subdomain)->findOrFail($contactId);
        $this->notes = ContactNote::fromTenant($this->tenant_subdomain)->where(['contact_id' => $contactId])->get();

        // Load custom fields with values
        $this->customFields = collect();
        $customFields = $this->contact->getAvailableCustomFields();
        $customFieldsData = $this->contact->custom_fields_data ?? [];

        $this->customFields = collect();
        foreach ($customFields as $field) {
            $value = $customFieldsData[$field->field_name] ?? $field->default_value;
            $this->customFields->push([
                'field' => $field,
                'value' => $value,
                'display_value' => $this->contact->getCustomFieldDisplayValue($field, $value),
            ]);
        }

        $this->viewContactModal = true;
    }

    public function importContact()
    {
        if (! checkPermission('tenant.contact.bulk_import')) {
            $this->notify([
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        return redirect()->to(tenant_route('tenant.contacts.import_log'));
    }

    public function editContact($contactId)
    {
        $this->contact = Contact::fromTenant($this->tenant_subdomain)->findOrFail($contactId);
        $this->redirect(tenant_route('tenant.contacts.save', ['contactId' => $contactId]));
    }

    public function updatedConfirmingDeletion($value)
    {
        if (! $value) {
            $this->js('window.pgBulkActions.clearAll()');
        }
    }

    public function confirmDelete($contactId)
    {
        $this->contact_id = $contactId;

        $this->isBulckDelete = is_array($this->contact_id) && count($this->contact_id) !== 1 ? true : false;

        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('tenant.contact.delete')) {
            if (is_array($this->contact_id) && count($this->contact_id) !== 0) {
                $selectedIds = $this->contact_id;
                // dispatch(function () use ($selectedIds) {
                Contact::fromTenant($this->tenant_subdomain)->whereIn('id', $selectedIds)
                    ->chunk(100, function ($contacts) {
                        foreach ($contacts as $contact) {
                            $contact->delete();
                        }
                    });
                // })->afterResponse();
                $this->contact_id = null;
                $this->js('window.pgBulkActions.clearAll()');
                $this->notify([
                    'type' => 'success',
                    'message' => t('contacts_delete_successfully'),
                ]);
            } else {

                $contact = Contact::fromTenant($this->tenant_subdomain)->findOrFail($this->contact_id);
                $this->contact_id = null;
                $contact->delete();

                $this->notify([
                    'type' => 'success',
                    'message' => t('contact_delete_success'),
                ]);
            }

            $this->confirmingDeletion = false;
            $this->dispatch('contact-table-refresh');
        }
    }

    public function resetForm()
    {
        $this->dispatch('refreshComponent');
        $this->reset('template_id');
        $this->template_id = '';
    }

    public function initiateChat($id)
    {
        if (checkPermission('tenant.contact.create', 'tenant.contact.edit')) {
            $this->template_id = [];
            $this->resetForm();
            $this->loadMergeFields();
            $this->contacts = [$id];
            $this->showInitiateChatModal = true;
        } else {
            $this->notify([
                'message' => t('access_denied_note'),
                'type' => 'warning',
            ]);
        }
    }

    public function bulkInitiateChatSending($ids)
    {
        $this->resetForm();
        $this->contacts = $ids;
        $this->loadMergeFields();
        $this->showInitiateChatModal = true;
    }

    public function bulkActionsOnContacts($ids)
    {
        $this->resetForm();
        $this->contacts = Contact::fromTenant($this->tenant_subdomain)->with('contact_notes')->findOrFail($ids);
        $this->showBulkActionsModal = true;
    }

    public function loadMergeFields()
    {
        $mergeFieldsService = app(MergeFieldsService::class);
        $field = array_merge(
            $mergeFieldsService->getFieldsForTemplate('tenant-other-group'),
            $mergeFieldsService->getFieldsForTemplate('tenant-contact-group')
        );

        $mergeFields = array_map(fn ($value) => [
            'key' => ucfirst($value['name']),
            'value' => $value['key'],
        ], $field);

        // If this is an HTTP request (not a Livewire call), return JSON response
        if (request()->expectsJson() || request()->isMethod('get')) {
            return response()->json([
                'success' => true,
                'data' => $mergeFields,
            ]);
        }

        // For Livewire calls, set the property and return the JSON string
        $this->mergeFields = json_encode($mergeFields);

        return $this->mergeFields;
    }

    protected function rules()
    {
        return [
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'nullable|integer',
            'headerInputs' => 'nullable|array|max:10',
            'headerInputs.*' => ['nullable', 'string', 'max:1000', new PurifiedInput(t('dynamic_input_error'))],
            'bodyInputs' => 'nullable|array|max:10',
            'bodyInputs.*' => ['nullable', 'string', 'max:1000', new PurifiedInput(t('dynamic_input_error'))],
            'footerInputs' => 'nullable|array|max:10',
            'footerInputs.*' => ['nullable', 'string', 'max:1000', new PurifiedInput(t('dynamic_input_error'))],
            'template_id' => 'required|string',
            'file' => 'nullable|file',
            'cards_params' => 'nullable|json',
            'card_variables' => 'nullable|json',
            'body_variables' => 'nullable|json',
            'existing_filename' => 'nullable|string',
            'existing_carousel_media' => 'nullable|json',
        ];
    }

    protected function getFileValidationRules($format)
    {
        return match ($format) {
            'IMAGE' => ['mimes:jpeg,png', 'max:8192'],
            'DOCUMENT' => ['mimes:pdf,doc,docx,txt,ppt,pptx,xlsx,xls', 'max:102400'],
            'VIDEO' => ['mimes:mp4,3gp', 'max:16384'],
            'AUDIO' => ['mimes:mp3,wav,aac,ogg', 'max:16384'],
            default => ['file', 'max:5120'],
        };
    }

    #[Computed]
    public function templates()
    {
        return WhatsappTemplate::where('tenant_id', tenant_id())->get();
    }

    #[Computed]
    public function metaExtensions()
    {
        return get_meta_allowed_extension();
    }

    #[Computed]
    public function statuses()
    {
        return Status::where('tenant_id', tenant_id())->get();
    }

    #[Computed]
    public function sources()
    {
        return Source::where('tenant_id', tenant_id())->get();
    }

    public function getGroups()
    {
        return Group::where('tenant_id', tenant_id())->get();
    }

    #[Computed]
    public function assignees()
    {
        return User::where('tenant_id', tenant_id())->get();
    }

    public function updatedShowInitiateChatModal($value)
    {
        if (! $value) {
            $this->js('window.pgBulkActions.clearAll()');
            $this->reset('template_id', 'headerInputs', 'bodyInputs', 'footerInputs', 'file', 'filename');
            $this->dispatch('reset-campaign-select');
        }
    }

    public function updatedShowBulkActionsModal($value)
    {
        if (! $value) {
            // reset temporary bulk fields on close
            $this->bulk_status_id = null;
            $this->bulk_source_id = null;
            $this->bulk_group_ids = [];
            $this->bulk_assigned_id = null;
            $this->js('window.pgBulkActions.clearAll()');
        }
    }

    public function applyBulkChanges()
    {
        if (! checkPermission('tenant.contact.edit')) {
            $this->notify(['type' => 'warning', 'message' => t('access_denied_note')]);

            return;
        }

        try {
            $ids = collect($this->contacts)->pluck('id')->toArray();
            if (empty($ids)) {
                $this->notify(['type' => 'warning', 'message' => t('no_contact_selected')]);

                return;
            }

            $update = [];
            if (! is_null($this->bulk_status_id)) {
                $update['status_id'] = $this->bulk_status_id;
                $update['last_status_change'] = now();
            }

            if (! is_null($this->bulk_source_id)) {
                $update['source_id'] = $this->bulk_source_id;
            }

            if (! is_null($this->bulk_assigned_id)) {
                $update['assigned_id'] = $this->bulk_assigned_id;
                $update['dateassigned'] = now();
            }

            // If groups provided, set group_id column to the array
            if (! empty($this->bulk_group_ids)) {
                $update['group_id'] = array_values(array_map('intval', $this->bulk_group_ids));
            }

            if (! empty($update)) {
                Contact::fromTenant($this->tenant_subdomain)->whereIn('id', $ids)->update($update);
                $this->notify(['type' => 'success', 'message' => t('bulk_update_success')]);
            }

            $this->showBulkActionsModal = false;
            $this->dispatch('contact-table-refresh');
            $this->js('window.pgBulkActions.clearAll()');
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => t('something_went_wrong').': '.$e->getMessage()]);
        }
    }

    protected function handleFileUpload($format)
    {
        // Ensure storage link exists
        create_storage_link();

        // Delete old file if exists
        if (! empty($this->filename) && Storage::disk('public')->exists($this->filename)) {
            Storage::disk('public')->delete($this->filename);
        }

        // Determine subdirectory by format
        $formatDirectory = match ($format) {
            'IMAGE' => 'images',
            'DOCUMENT' => 'documents',
            'VIDEO' => 'videos',
            'AUDIO' => 'audio',
            default => 'misc',
        };

        // Final path like: tenant/123/init_chat/images
        $directory = 'tenant/'.tenant_id().'/init_chat/'.$formatDirectory;

        // Store the file
        $path = $this->file->storeAs(
            $directory,
            $this->generateFileName(),
            'public'
        );

        $this->filename = $path;

        return $path;
    }

    protected function generateFileName()
    {
        $original = str_replace(' ', '_', $this->file->getClientOriginalName());

        return pathinfo($original, PATHINFO_FILENAME).'_'.time().'.'.$this->file->extension();
    }

    protected function handleCarouselMediaUpload(array $files)
    {
        create_storage_link();

        $uploadedFiles = [];

        foreach ($files as $cardIndex => $file) {
            if ($file && $file->isValid()) {
                // Determine file type
                $mimeType = $file->getMimeType();
                $formatDirectory = 'misc';

                if (str_starts_with($mimeType, 'image/')) {
                    $formatDirectory = 'images';
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $formatDirectory = 'videos';
                } elseif (str_starts_with($mimeType, 'audio/')) {
                    $formatDirectory = 'audio';
                } else {
                    $formatDirectory = 'documents';
                }

                $directory = 'tenant/'.tenant_id().'/init_chat/carousel/'.$formatDirectory;

                $original = str_replace(' ', '_', $file->getClientOriginalName());
                $filename = pathinfo($original, PATHINFO_FILENAME).'_'.time().'_'.$cardIndex.'.'.$file->extension();

                $path = $file->storeAs($directory, $filename, 'public');

                $uploadedFiles[$cardIndex] = $path;
            }
        }

        return $uploadedFiles;
    }

    public function save(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate($this->rules());
            // Get contact IDs from request
            $contactIds = $validated['contact_ids'] ?? [];
            $this->tenant_subdomain = tenant_subdomain();
            $this->tenant_id = tenant_id();

            if (empty($contactIds)) {
                return response()->json([
                    'success' => false,
                    'message' => t('no_contacts_found') ?? 'No contacts selected. Please try again.',
                ], 404);
            }

            // Load contacts from database
            $contacts = Contact::fromTenant($this->tenant_subdomain)
                ->with('contact_notes')
                ->whereIn('id', $contactIds)
                ->get();

            // Ensure we have contacts to process
            if ($contacts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => t('no_contacts_found') ?? 'No contacts found',
                ], 404);
            }

            // Get the template
            $template = WhatsappTemplate::where('template_id', $validated['template_id'])->firstOrFail();

            $headerFormat = $template->header_data_format ?? 'TEXT';

            $isCarouselTemplate = ! empty($template->template_type) && strtolower($template->template_type) === 'carousel';

            $filename = null;
            $carouselMediaFiles = [];

            // Handle regular template file upload (same file for all contacts)
            if (! $isCarouselTemplate && $request->hasFile('file')) {
                $this->file = $request->file('file');
                $filename = $this->handleFileUpload($headerFormat);
            }

            // Handle carousel media files (same files for all contacts)
            if ($isCarouselTemplate && $request->hasFile('carousel_media')) {
                $carouselMediaFiles = $this->handleCarouselMediaUpload($request->file('carousel_media'));
            }

            // Parse carousel data if present
            $cardsParams = ! empty($validated['cards_params']) ? json_decode($validated['cards_params'], true) : null;
            $cardVariables = ! empty($validated['card_variables']) ? json_decode($validated['card_variables'], true) : [];
            $bodyVariables = ! empty($validated['body_variables']) ? json_decode($validated['body_variables'], true) : [];
            $existingCarouselMedia = ! empty($validated['existing_carousel_media']) ? json_decode($validated['existing_carousel_media'], true) : [];

            // Process variables for regular templates
            $headerInputs = $request->input('headerInputs', []);
            $bodyInputs = $request->input('bodyInputs', []);
            $footerInputs = $request->input('footerInputs', []);

            // Track results
            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            // Process each contact
            foreach ($contacts as $contact) {
                $response = $this->processContactChat(
                    $contact,
                    $template,
                    $filename,
                    $headerInputs,
                    $bodyInputs,
                    $footerInputs,
                    $isCarouselTemplate,
                    $cardsParams,
                    $carouselMediaFiles,
                    $existingCarouselMedia,
                    $bodyVariables
                );

                if (! empty($response['status'])) {
                    $successCount++;
                } else {
                    $failureCount++;
                    $errors[] = [
                        'contact' => $contact->firstname.' '.$contact->lastname,
                        'phone' => $contact->phone,
                        'error' => $response['log_data']['response_data'] ?? 'Unknown error',
                    ];
                }
            }

            // Prepare response
            $totalContacts = is_countable($contacts) ? count($contacts) : (method_exists($contacts, 'count') ? $contacts->count() : 0);

            if ($successCount === $totalContacts) {
                $message = $totalContacts === 1
                    ? t('chat_initiated_successfully')
                    : t('chat_initiated_successfully')." ({$successCount}/{$totalContacts})";

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => tenant_route('tenant.contacts.list'),
                    'stats' => [
                        'success' => $successCount,
                        'failed' => $failureCount,
                        'total' => $totalContacts,
                    ],
                ]);
            } elseif ($successCount > 0) {
                $message = t('partial_success_message', [
                    'success' => $successCount,
                    'total' => $totalContacts,
                ]) ?? "Sent to {$successCount} out of {$totalContacts} contacts";

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => tenant_route('tenant.contacts.list'),
                    'stats' => [
                        'success' => $successCount,
                        'failed' => $failureCount,
                        'total' => $totalContacts,
                    ],
                    'errors' => $errors,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => t('failed_to_send_to_all_contacts') ?? 'Failed to send to all contacts',
                    'stats' => [
                        'success' => $successCount,
                        'failed' => $failureCount,
                        'total' => $totalContacts,
                    ],
                    'errors' => $errors,
                ], 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => t('validation_error') ?? 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            whatsapp_log('Error during template sending: '.$e->getMessage(), 'error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], $e);

            return response()->json([
                'success' => false,
                'message' => t('something_went_wrong').': '.$e->getMessage(),
            ], 500);
        }
    }

    protected function processContactChat(
        Contact $contact,
        WhatsappTemplate $template,
        ?string $filename = null,
        array $headerInputs = [],
        array $bodyInputs = [],
        array $footerInputs = [],
        bool $isCarouselTemplate = false,
        ?array $cardsParams = null,
        array $carouselMediaFiles = [],
        array $existingCarouselMedia = [],
        array $bodyVariables = []
    ) {
        $pusher_settings = get_settings_by_group('pusher');
        $this->whatsapp_settings = tenant_settings_by_group('whatsapp', $this->tenant_id);

        $headerFormat = $template->header_data_format ?? 'TEXT';

        // Prepare base rel_data
        $rel_data = [
            'rel_type' => $contact->type,
            'rel_id' => $contact->id,
            'campaign_id' => 0,
            'header_data_format' => $headerFormat,
        ];

        // Merge template data
        $rel_data = array_merge($rel_data, $template->toArray());

        // Handle carousel template
        if ($isCarouselTemplate && ! empty($cardsParams)) {
            // Process body variables - replace placeholders in body text
            $bodyText = $template->body_data ?? '';
            if (! empty($bodyVariables)) {
                foreach ($bodyVariables as $index => $value) {
                    $placeholder = '{{'.($index + 1).'}}';
                    $bodyText = str_replace($placeholder, $value, $bodyText);
                }
            }

            // Process carousel cards - merge media files with card params
            $processedCards = [];
            foreach ($cardsParams as $cardIndex => $cardData) {
                $processedCard = $cardData;

                // Update media in card components
                if (isset($processedCard['components']) && is_array($processedCard['components'])) {
                    foreach ($processedCard['components'] as $compIndex => $component) {
                        // Handle HEADER component with media
                        if (isset($component['type']) && $component['type'] === 'HEADER' && isset($component['format'])) {
                            // Check for new uploaded file
                            if (isset($carouselMediaFiles[$cardIndex])) {
                                $processedCard['components'][$compIndex]['example'] = [
                                    'header_handle' => [asset('storage/'.$carouselMediaFiles[$cardIndex])],
                                ];
                            }
                            // Check for existing media URL
                            elseif (isset($existingCarouselMedia[$cardIndex])) {
                                $processedCard['components'][$compIndex]['example'] = [
                                    'header_handle' => [$existingCarouselMedia[$cardIndex]],
                                ];
                            }
                        }

                        // Handle BODY component with variables
                        if (isset($component['type']) && $component['type'] === 'BODY' && isset($component['text'])) {
                            // Replace variables in card body text
                            $cardBodyText = $component['text'];
                            if (isset($component['example']['body_text']) && is_array($component['example']['body_text']) && isset($component['example']['body_text'][0]) && is_array($component['example']['body_text'][0])) {
                                foreach ($component['example']['body_text'][0] as $varIndex => $varValue) {
                                    $placeholder = '{{'.($varIndex + 1).'}}';
                                    $cardBodyText = str_replace($placeholder, $varValue, $cardBodyText);
                                }
                            }
                            $processedCard['components'][$compIndex]['text'] = $cardBodyText;
                        }
                    }
                }

                $processedCards[] = $processedCard;
            }

            $rel_data['cards_json'] = json_encode($processedCards);
            $rel_data['cards_params'] = $processedCards;
            $rel_data['body_data'] = $bodyText;
            $rel_data['body_message'] = $bodyText;
            $rel_data['header_message'] = null;
            $rel_data['footer_message'] = null;
        } else {
            // Handle regular template
            $rel_data['filename'] = $filename ?? null;
            $rel_data['header_params'] = ! empty($headerInputs) ? json_encode(array_values(array_filter($headerInputs))) : null;
            $rel_data['body_params'] = ! empty($bodyInputs) ? json_encode(array_values(array_filter($bodyInputs))) : null;
            $rel_data['footer_params'] = ! empty($footerInputs) ? json_encode(array_values(array_filter($footerInputs))) : null;
            $rel_data['header_message'] = $template->header_data_text ?? null;
            $rel_data['body_message'] = $template->body_data ?? null;
            $rel_data['footer_message'] = $template->footer_data ?? null;
        }

        $response = $this->sendTemplate($contact->phone, $rel_data, 'Initiate Chat');

        if (! empty($response['status'])) {

            $header = parseText($rel_data['rel_type'], 'header', $rel_data);
            $body = parseText($rel_data['rel_type'], 'body', $rel_data);
            $footer = parseText($rel_data['rel_type'], 'footer', $rel_data);

            $buttonHtml = '';
            if (! empty($rel_data['buttons_data']) && is_string($rel_data['buttons_data'])) {
                $buttons = json_decode($rel_data['buttons_data']);
                if (is_array($buttons) || is_object($buttons)) {
                    $buttonHtml = "<div class='flex flex-col mt-2 space-y-2'>";
                    foreach ($buttons as $button) {
                        $buttonHtml .= "<button class='bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
                        dark:bg-gray-800 dark:text-green-400'>".e($button->text).'</button>';
                    }
                    $buttonHtml .= '</div>';
                }
            }
            // Header media / text rendering
            $headerData = '';
            $fileExtensions = get_meta_allowed_extension();

            if (! empty($rel_data['filename'])) {
                $extension = strtolower(pathinfo($rel_data['filename'], PATHINFO_EXTENSION));
                $fileType = array_key_first(array_filter($fileExtensions, fn ($data) => in_array('.'.$extension, explode(', ', $data['extension']))));

                if ($rel_data['header_data_format'] == 'IMAGE' && $fileType == 'image') {
                    $headerData = "<a href='".asset('storage/'.$rel_data['filename'])."'>
                    <img src='".asset('storage/'.$rel_data['filename'])."' class='img-responsive rounded-lg object-cover'>
                    </a>";
                } elseif ($rel_data['header_data_format'] == 'VIDEO' && $fileType == 'video') {
                    $headerData = "<a href='".asset('storage/'.$rel_data['filename'])."'>
                    <video src='".asset('storage/'.$rel_data['filename'])."' class='rounded-lg object-cover' controls>
                    </a>";
                } elseif ($rel_data['header_data_format'] == 'DOCUMENT') {
                    $headerData = "<a href='".asset('storage/'.$rel_data['filename'])."' target='_blank' class='btn btn-secondary w-full'>".t('document').'</a>';
                }
            }

            if (empty($headerData) && ($rel_data['header_data_format'] == 'TEXT' || empty($rel_data['header_data_format'])) && ! empty($header)) {
                $headerData = "<span class='font-bold mb-3'>".nl2br(decodeWhatsAppSigns(e($header))).'</span>';
            }

            // Handle phone format
            $phone = ltrim($contact->phone, '+');

            // Get or create chat
            $chat_id = Chat::fromTenant($this->tenant_subdomain)->where([
                ['receiver_id', '=', $phone],
                ['wa_no', '=', $this->whatsapp_settings['wm_default_phone_number']],
                ['wa_no_id', '=', $this->whatsapp_settings['wm_default_phone_number_id']],
            ])->value('id');

            if (empty($chat_id)) {
                $chat_id = Chat::fromTenant($this->tenant_subdomain)->insertGetId([
                    'tenant_id' => $this->tenant_id,
                    'receiver_id' => $phone,
                    'wa_no' => $this->whatsapp_settings['wm_default_phone_number'],
                    'wa_no_id' => $this->whatsapp_settings['wm_default_phone_number_id'],
                    'name' => $contact->firstname.' '.$contact->lastname,
                    'last_message' => $body ?? '',
                    'time_sent' => now(),
                    'type' => $contact->type ?? 'guest',
                    'type_id' => $contact->id ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($isCarouselTemplate) {
                // cards_params already set in processContactChat with processed cards including media
                $carouselHtml = $this->renderCarouselTemplate($rel_data);

                $chatMessage = ChatMessage::fromTenant($this->tenant_subdomain)->create([
                    'tenant_id' => $this->tenant_id,
                    'interaction_id' => $chat_id,
                    'sender_id' => get_tenant_setting_by_tenant_id('whatsapp', 'wm_default_phone_number', null, $this->tenant_id),
                    'url' => null,
                    'message' => $carouselHtml,
                    'status' => 'sent',
                    'time_sent' => now()->toDateTimeString(),
                    'message_id' => $response['data']->messages[0]->id ?? null,
                    'staff_id' => 0,
                    'type' => 'text',
                    'is_read' => '1',
                ]);
            } else {
                $chatMessage = ChatMessage::fromTenant($this->tenant_subdomain)->create([
                    'tenant_id' => $this->tenant_id,
                    'interaction_id' => $chat_id,
                    'sender_id' => $this->whatsapp_settings['wm_default_phone_number'],
                    'url' => null,
                    'message' => "
                    $headerData
                    <p>".nl2br(decodeWhatsAppSigns(e($body ?? '')))."</p>
                    <span class='text-gray-500 text-sm'>".nl2br(decodeWhatsAppSigns(e($footer ?? '')))."</span>
                    $buttonHtml
                ",
                    'status' => 'sent',
                    'time_sent' => now()->toDateTimeString(),
                    'message_id' => $response['data']->messages[0]->id ?? null,
                    'staff_id' => 0,
                    'type' => 'text',
                ]);
            }

            $chatMessageId = $chatMessage->id;
            Chat::fromTenant($this->tenant_subdomain)->where('id', $chat_id)->update([
                'last_message' => $body ?? '',
            ]);
            if (! empty($pusher_settings?->app_key) && ! empty($pusher_settings?->app_secret) && ! empty($pusher_settings?->app_id) && ! empty($pusher_settings?->cluster)) {
                // Use centralized notification method with enhanced metadata
                \App\Http\Controllers\Whatsapp\WhatsAppWebhookController::triggerChatNotificationStatic($chat_id, $chatMessageId, $this->tenant_id, false);
            }
        }

        return $response;
    }

    protected function renderCarouselTemplate(array $data): string
    {
        $cards_params = is_string($data['cards_params'])
            ? json_decode($data['cards_params'], true)
            : $data['cards_params'];

        if (empty($cards_params) || ! is_array($cards_params)) {
            return '<div class="text-gray-500 italic p-3 bg-gray-100 rounded-lg dark:bg-gray-700">🎠 Carousel template (no cards data available)</div>';
        }

        // Start carousel container with better styling
        $carouselHtml = '<div class="carousel-template bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 p-4 mb-2">';

        // Add carousel header with icon
        $cardCount = count($cards_params);
        $carouselHtml .= '<div class="flex items-center mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">🎠 Carousel Template</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">'.$cardCount.' card'.($cardCount !== 1 ? 's' : '').'</p>
            </div>
        </div>';

        // Parse and display main body text if present
        if (! empty($data['body_params'])) {
            $bodyParams = is_string($data['body_params']) ? json_decode($data['body_params'], true) : $data['body_params'];
            if (is_array($bodyParams) && ! empty($bodyParams)) {
                $body = implode(' ', $bodyParams);
                $carouselHtml .= '<div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-gray-800 dark:text-gray-200">'.nl2br(e($body)).'</p>
                </div>';
            }
        }

        // Render carousel cards in horizontal layout
        $carouselHtml .= '<div class="carousel-cards overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-700" style="scroll-behavior: smooth;">
            <div class="flex space-x-4 pb-3" style="min-width: fit-content; scroll-snap-type: x mandatory;">';

        foreach ($cards_params as $cardIndex => $cardData) {
            $carouselHtml .= '<div class="carousel-card flex-shrink-0 w-72 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200" style="scroll-snap-align: start;">';

            // Extract components data with validation
            $components = $cardData['components'] ?? [];
            $headerComponent = null;
            $bodyComponent = null;
            $buttonsComponent = null;

            // Parse components
            if (is_array($components)) {
                foreach ($components as $component) {
                    if (! is_array($component) || ! isset($component['type'])) {
                        continue;
                    }

                    switch (strtoupper($component['type'])) {
                        case 'HEADER':
                            $headerComponent = $component;
                            break;
                        case 'BODY':
                            $bodyComponent = $component;
                            break;
                        case 'BUTTONS':
                            $buttonsComponent = $component;
                            break;
                    }
                }
            }

            // Card header (image/video from header component)
            if ($headerComponent && isset($headerComponent['example']['header_handle'][0])) {
                $mediaUrl = $headerComponent['example']['header_handle'][0];
                $format = strtolower($headerComponent['format'] ?? 'image');

                if ($format === 'image') {
                    $carouselHtml .= '<div class="card-media relative">
                        <img src="'.$mediaUrl.'" class="w-full h-48 object-cover" alt="Card '.($cardIndex + 1).'" loading="lazy">
                        <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                            '.($cardIndex + 1).'/'.count($cards_params).'
                        </div>
                    </div>';
                } elseif ($format === 'video') {
                    $carouselHtml .= '<div class="card-media">
                        <video class="w-full h-48 object-cover" controls>
                            <source src="'.$mediaUrl.'" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>';
                }
            } else {
                // Placeholder for cards without media
                $carouselHtml .= '<div class="card-media h-48 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-gray-300 dark:bg-gray-500 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Card '.($cardIndex + 1).'</p>
                    </div>
                </div>';
            }

            // Card content
            $carouselHtml .= '<div class="card-content p-4">';

            // Card body text from body component
            if ($bodyComponent && ! empty($bodyComponent['text'])) {
                $bodyText = $bodyComponent['text'];

                // Replace {{1}}, {{2}}, etc. with actual values from body_text examples
                if (isset($bodyComponent['example']['body_text'][0]) && is_array($bodyComponent['example']['body_text'][0])) {
                    $bodyValues = $bodyComponent['example']['body_text'][0];

                    // Replace each variable placeholder with its value
                    foreach ($bodyValues as $index => $value) {
                        $placeholder = '{{'.(($index) + 1).'}}';
                        $bodyText = str_replace($placeholder, $value, $bodyText);
                    }
                }

                $carouselHtml .= '<p class="text-sm text-gray-700 dark:text-gray-300 mb-3 leading-relaxed">'.nl2br(e($bodyText)).'</p>';
            }

            // Card buttons from buttons component
            if ($buttonsComponent && ! empty($buttonsComponent['buttons'])) {
                $carouselHtml .= '<div class="card-buttons space-y-2">';

                foreach ($buttonsComponent['buttons'] as $button) {
                    $buttonType = strtoupper($button['type'] ?? '');
                    $buttonText = $button['text'] ?? 'Button';

                    if ($buttonType === 'URL' && ! empty($button['url'])) {
                        $carouselHtml .= '<a href="'.$button['url'].'" target="_blank" class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            '.e($buttonText).'
                        </a>';
                    } else {
                        $carouselHtml .= '<button class="inline-flex items-center justify-center w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition-colors duration-200 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200">
                            '.e($buttonText).'
                        </button>';
                    }
                }

                $carouselHtml .= '</div>';
            }

            $carouselHtml .= '</div>'; // Close card content
            $carouselHtml .= '</div>'; // Close card
        }

        $carouselHtml .= '</div>'; // Close cards flex container
        $carouselHtml .= '</div>'; // Close carousel-cards

        // Add footer if present
        if (! empty($data['footer_params'])) {
            $footerParams = is_string($data['footer_params']) ? json_decode($data['footer_params'], true) : $data['footer_params'];
            if (is_array($footerParams) && ! empty($footerParams)) {
                $footer = implode(' ', $footerParams);
                $carouselHtml .= '<div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
                    <p class="text-xs text-gray-500 dark:text-gray-400">'.nl2br(e($footer)).'</p>
                </div>';
            }
        }

        $carouselHtml .= '</div>'; // Close carousel template

        return $carouselHtml;
    }

    public function getRemainingLimitProperty()
    {
        return $this->featureLimitChecker->getRemainingLimit('contacts', Contact::class);
    }

    public function getIsUnlimitedProperty()
    {
        return $this->remainingLimit === null;
    }

    public function getHasReachedLimitProperty()
    {
        return $this->featureLimitChecker->hasReachedLimit('contacts', Contact::class);
    }

    public function getTotalLimitProperty()
    {
        return $this->featureLimitChecker->getLimit('contacts');
    }

    public function refreshTable()
    {
        $this->dispatch('contact-table-refresh');
    }

    public function render()
    {
        return view('livewire.tenant.contact.contact-list');
    }
}
