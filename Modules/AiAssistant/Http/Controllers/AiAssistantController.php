<?php

namespace Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Rules\PurifiedInput;
use App\Services\FeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\AiAssistant\Models\Tenant\PersonalAssistant;
use Modules\AiAssistant\Services\OpenAIAssistantService;

class AiAssistantController extends Controller
{
    protected OpenAIAssistantService $openAIService;

    protected $featureLimitChecker;

    protected $remainingLimit;

    public function __construct(?OpenAIAssistantService $openAIService = null, ?FeatureService $featureLimitChecker = null)
    {
        $this->openAIService = $openAIService ?? new OpenAIAssistantService;
        $this->featureLimitChecker = $featureLimitChecker ?? app(FeatureService::class);
        $this->remainingLimit = $this->getRemainingLimitProperty();
    }

    /**
     * Get remaining limit property for compatibility
     */
    private function getRemainingLimitProperty()
    {
        try {
            if ($this->featureLimitChecker) {
                $limit = $this->featureLimitChecker->getRemainingLimit('ai_personal_assistants');

                return $limit === -1 ? null : $limit;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Display a listing of personal assistants
     */
    public function index()
    {
        if (! checkPermission('tenant.personal_assistant.view')) {
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $assistants = PersonalAssistant::where('tenant_id', tenant_id())
            ->with(['documents'])
            ->orderBy('created_at', 'desc')
            ->get();

        // ENHANCED: Get comprehensive status info for each assistant
        foreach ($assistants as $assistant) {
            $assistant->status_info = $this->getAssistantStatusInfo($assistant);
        }

        $tenantSubdomain = tenant_subdomain();

        // Get feature limits (maintaining compatibility with your existing system)
        $isUnlimited = $this->remainingLimit === null;
        $remainingLimit = $this->remainingLimit;
        $totalLimit = $this->featureLimitChecker ? $this->featureLimitChecker->getLimit('ai_personal_assistants') : null;

        return view('AiAssistant::tenant.personal-assistant.index', compact('assistants', 'tenantSubdomain', 'isUnlimited', 'remainingLimit', 'totalLimit'));
    }

    /**
     * Show the form for creating a new assistant
     */
    public function create()
    {
        if (! checkPermission('tenant.personal_assistant.create')) {
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        // Check assistant limit
        $hasReachedLimit = $this->featureLimitChecker->hasReachedLimit('ai_personal_assistants', PersonalAssistant::class);

        return view('AiAssistant::tenant.personal-assistant.create', compact('hasReachedLimit'));
    }

    /**
     * Store a newly created assistant in storage
     */
    public function store(Request $request)
    {
        if (! checkPermission('tenant.personal_assistant.create')) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        // Check assistant limit
        if ($this->featureLimitChecker->hasReachedLimit('ai_personal_assistants', PersonalAssistant::class)) {
            return response()->json([
                'success' => false,
                'error' => 'Personal assistant limit reached. Please upgrade your plan.',
            ], 500);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'system_prompt' => ['nullable', 'string', 'max:40000', new PurifiedInput(t('sql_injection_error'))],
            'model' => 'nullable|string|in:gpt-4o-mini,gpt-4o,gpt-3.5-turbo',
            'temperature' => 'nullable|numeric|between:0,2',
            'is_active' => 'boolean',
            'instruction' => ['nullable', 'string', 'max:40000', new PurifiedInput(t('sql_injection_error'))],
        ]);

        if ($validator->fails()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        if (! $this->openAIService->isApiKeyConfigured()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'OpenAI API key not configured. Please set it in WhatsApp settings.',
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'OpenAI API key not configured. Please set it in WhatsApp settings.'])
                ->withInput();
        }

        try {
            $assistant = PersonalAssistant::create([
                'name' => $request->name,
                'system_prompt' => $request->system_prompt,
                'model' => $request->model ?? 'gpt-4o-mini',
                'temperature' => $request->temperature ?? 0.7,
                'tools' => [['type' => 'file_search']],
                'is_active' => $request->boolean('is_active', true),
                'user_id' => Auth::id(),
                'instruction' => $request->instruction ?? null,
            ]);

            $this->featureLimitChecker->trackUsage('ai_personal_assistants');

            // Check if OpenAI API key is configured before creating assistant
            if ($this->openAIService->isApiKeyConfigured()) {
                $result = $this->openAIService->createAssistant($assistant);

                if (! $result['success']) {
                    Log::warning('Failed to create OpenAI assistant during creation', [
                        'assistant_id' => $assistant->id,
                        'error' => $result['error'],
                    ]);
                }
            }

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Personal assistant created successfully!',
                    'assistant' => $assistant,
                ]);
            }

            return redirect()->tenant_route('tenant.personal-assistants.index')
                ->with('success', 'Personal assistant created successfully!');
        } catch (\Exception $e) {
            Log::error('Assistant creation error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while creating the assistant.',
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'An error occurred while creating the assistant.'])
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified assistant
     */
    public function edit($subdomain, PersonalAssistant $assistant)
    {
        if (! checkPermission('tenant.personal_assistant.edit')) {
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        // ENHANCED: Check if sync is in progress
        $tenantId = tenant_id();
        $lockKey = "assistant_sync_{$tenantId}_{$assistant->id}";

        $syncInProgress = Cache::has($lockKey);
        if ($syncInProgress) {
            session()->flash('notification', [
                'type' => 'warning',
                'message' => 'Assistant sync is in progress. Please wait for completion before editing.',
            ]);
        }

        $assistant->status_info = $this->getAssistantStatusInfo($assistant);
        $assistant->sync_in_progress = $syncInProgress;

        return view('AiAssistant::tenant.personal-assistant.edit', compact('assistant'));
    }

    /**
     * Update the specified assistant in storage
     */
    public function update(Request $request, $subdomain, PersonalAssistant $assistant)
    {
        if (! checkPermission('tenant.personal_assistant.edit')) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        // ENHANCED: Check if sync is in progress
        $tenantId = tenant_id();
        $lockKey = "assistant_sync_{$tenantId}_{$assistant->id}";

        if (Cache::has($lockKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit assistant while sync is in progress. Please wait for completion.',
            ], 409);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'system_prompt' => ['nullable', 'string', 'max:40000', new PurifiedInput(t('sql_injection_error'))],
            'model' => 'nullable|string|in:gpt-4o-mini,gpt-4o,gpt-3.5-turbo',
            'temperature' => 'nullable|numeric|between:0,2',
            'is_active' => 'boolean',
            'instruction' => ['nullable', 'string', 'max:40000', new PurifiedInput(t('sql_injection_error'))],
        ]);

        if ($validator->fails()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $assistant->update([
                'name' => $request->name,
                'system_prompt' => $request->system_prompt,
                'model' => $request->model ?? 'gpt-4o-mini',
                'temperature' => $request->temperature ?? 0.7,
                'is_active' => $request->boolean('is_active', true),
                'instruction' => $request->instruction ?? null,
            ]);

            // Update OpenAI assistant if it exists
            if ($assistant->openai_assistant_id && $this->openAIService->isApiKeyConfigured()) {
                $result = $this->openAIService->updateAssistant($assistant);

                if (! $result['success']) {
                    Log::warning('Failed to update OpenAI assistant', [
                        'assistant_id' => $assistant->id,
                        'openai_assistant_id' => $assistant->openai_assistant_id,
                        'error' => $result['error'],
                    ]);
                }
            }

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Personal assistant updated successfully!',
                    'assistant' => $assistant,
                ]);
            }

            return redirect()->tenant_route('tenant.personal-assistants.index')
                ->with('success', 'Personal assistant updated successfully!');
        } catch (\Exception $e) {
            Log::error('Assistant update error', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the assistant.',
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'An error occurred while updating the assistant.'])
                ->withInput();
        }
    }

    /**
     * Remove the specified assistant from storage
     */
    public function destroy($subdomain, PersonalAssistant $assistant)
    {
        if (! checkPermission('tenant.personal_assistant.delete')) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        try {
            // Delete from OpenAI first if configured
            if ($this->openAIService->isApiKeyConfigured()) {
                $result = $this->openAIService->deleteAssistant($assistant);

                if (! $result['success']) {

                    Log::warning('Failed to delete OpenAI assistant resources', [
                        'assistant_id' => $assistant->id,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to delete personal assistant.',
                    ]);
                }
                // Delete local assistant (this will cascade delete documents due to foreign key)
                $assistant->delete();

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Personal assistant deleted successfully!',
                    ]);
                }

                return redirect()->tenant_route('tenant.personal-assistants.index')
                    ->with('success', 'Personal assistant deleted successfully!');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete personal assistant.',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Assistant deletion error', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'An error occurred while deleting the assistant.',
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'An error occurred while deleting the assistant.']);
        }
    }

    /**
     * Toggle assistant status
     */
    public function toggleStatus($subdomain, $id)
    {
        if (! checkPermission('tenant.personal_assistant.edit')) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        $assistant = PersonalAssistant::findOrFail($id);

        $assistant->is_active = ! $assistant->is_active;
        $assistant->save();

        return response()->json([
            'success' => true,
            'message' => $assistant->is_active ? 'Assistant activated successfully!' : 'Assistant deactivated successfully!',
        ]);
    }

    /**
     * ENHANCED: Get comprehensive status information for an assistant
     */
    private function getAssistantStatusInfo($assistant)
    {
        $documents = $assistant->documents;
        $statusSummary = [];

        // Use OpenAI service to get real sync status if API is configured
        if ($this->openAIService->isApiKeyConfigured()) {
            try {
                $realStatus = $this->openAIService->getAssistantSyncStatus($assistant);

                return [
                    'total_documents' => $documents->count(),
                    'has_openai_assistant' => $realStatus['assistant_synced'],
                    'has_vector_store' => $realStatus['vector_store_exists'],
                    'openai_assistant_id' => $assistant->openai_assistant_id,
                    'openai_vector_store_id' => $assistant->openai_vector_store_id,
                    'status_summary' => $this->buildStatusSummaryFromReal($realStatus['documents_status']),
                    'is_fully_synced' => $realStatus['overall_status'] === 'fully_synced',
                    'sync_percentage' => $realStatus['sync_percentage'],
                    'real_sync_status' => $realStatus,
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to get real sync status, falling back to local status', [
                    'assistant_id' => $assistant->id,
                    'error' => $e->getMessage(),
                ]);
                // Fall back to local status checking
            }
        }

        // Fallback to local status checking
        foreach ($documents as $document) {
            $status = $this->getDocumentStatus($document);
            $statusSummary[$status] = ($statusSummary[$status] ?? 0) + 1;
        }

        return [
            'total_documents' => $documents->count(),
            'has_openai_assistant' => ! empty($assistant->openai_assistant_id),
            'has_vector_store' => ! empty($assistant->openai_vector_store_id),
            'openai_assistant_id' => $assistant->openai_assistant_id,
            'openai_vector_store_id' => $assistant->openai_vector_store_id,
            'status_summary' => $statusSummary,
            'is_fully_synced' => $this->isAssistantFullySynced($assistant),
            'sync_percentage' => $this->getAssistantSyncPercentage($assistant),
        ];
    }

    /**
     * ENHANCED: Get the status of a document with better verification
     */
    private function getDocumentStatus($document)
    {
        // If we have OpenAI API access, verify actual status
        if ($this->openAIService->isApiKeyConfigured() && $document->openai_file_id) {
            $fileExists = $this->openAIService->verifyFileExists($document->openai_file_id);

            if (! $fileExists) {
                return 'FILE_NOT_FOUND_IN_OPENAI';
            }

            // Check if attached to vector store
            if ($document->assistant->openai_vector_store_id) {
                $attached = $this->openAIService->verifyFileAttachedToVectorStore(
                    $document->assistant->openai_vector_store_id,
                    $document->openai_file_id
                );

                if ($attached) {
                    return 'FULLY_SYNCED';
                } else {
                    return 'UPLOADED_NOT_ATTACHED';
                }
            }

            return 'UPLOADED_NO_VECTOR_STORE';
        }

        // Fallback to local status checking
        if (
            $document->openai_file_id && $document->processed_content &&
            ! empty($document->assistant->openai_vector_store_id)
        ) {
            return 'FULLY_SYNCED'; // Assumed based on local data
        }

        if ($document->openai_file_id && empty($document->assistant->openai_vector_store_id)) {
            return 'UPLOADED_NOT_ATTACHED';
        }

        if ($document->processed_content && ! $document->openai_file_id) {
            return 'PENDING_UPLOAD';
        }

        if ($this->checkPendingJobs($document->id)) {
            return 'IN_QUEUE';
        }

        if ($document->openai_file_id && empty($document->processed_content)) {
            return 'UPLOADED_NO_CONTENT';
        }

        if (! empty($document->processed_content) && empty(trim($document->processed_content))) {
            return 'PROCESSED_NO_CONTENT';
        }

        return 'PENDING_UPLOAD';
    }

    /**
     * NEW: Build status summary from real OpenAI status
     */
    private function buildStatusSummaryFromReal($documentsStatus)
    {
        $summary = [];

        foreach ($documentsStatus as $docStatus) {
            $status = strtoupper($docStatus['status']);
            $summary[$status] = ($summary[$status] ?? 0) + 1;
        }

        return $summary;
    }

    /**
     * Check if there are pending jobs for a document
     */
    private function checkPendingJobs($documentId)
    {
        try {
            $pendingJobs = DB::table('jobs')
                ->where('payload', 'like', '%"documentId":'.$documentId.'%')
                ->orWhere('payload', 'like', '%"document_id":'.$documentId.'%')
                ->count();

            return $pendingJobs > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ENHANCED: Check if an assistant is fully synced with better verification
     */
    private function isAssistantFullySynced($assistant)
    {
        if (! $assistant->openai_assistant_id || ! $assistant->openai_vector_store_id) {
            return false;
        }

        $documents = $assistant->documents;
        if ($documents->isEmpty()) {
            return true; // No documents to sync
        }

        // If OpenAI API is available, use real verification
        if ($this->openAIService->isApiKeyConfigured()) {
            try {
                $realStatus = $this->openAIService->getAssistantSyncStatus($assistant);

                return $realStatus['overall_status'] === 'fully_synced';
            } catch (\Exception $e) {
                // Fall back to local checking
            }
        }

        // Fallback to local status checking
        foreach ($documents as $document) {
            if ($this->getDocumentStatus($document) !== 'FULLY_SYNCED') {
                return false;
            }
        }

        return true;
    }

    /**
     * ENHANCED: Get the sync percentage for an assistant
     */
    private function getAssistantSyncPercentage($assistant)
    {
        $documents = $assistant->documents;
        if ($documents->isEmpty()) {
            return 100;
        }

        // If OpenAI API is available, use real verification
        if ($this->openAIService->isApiKeyConfigured()) {
            try {
                $realStatus = $this->openAIService->getAssistantSyncStatus($assistant);

                return $realStatus['sync_percentage'];
            } catch (\Exception $e) {
                // Fall back to local checking
            }
        }

        // Fallback to local status checking
        $syncedCount = 0;
        foreach ($documents as $document) {
            if ($this->getDocumentStatus($document) === 'FULLY_SYNCED') {
                $syncedCount++;
            }
        }

        return round(($syncedCount / $documents->count()) * 100);
    }

    /**
     * Get status color for display
     */
    private function getStatusColor($status)
    {
        switch ($status) {
            case 'FULLY_SYNCED':
                return 'success';
            case 'UPLOADED_NOT_ATTACHED':
            case 'PENDING_UPLOAD':
            case 'UPLOADED_NO_VECTOR_STORE':
                return 'warning';
            case 'IN_QUEUE':
            case 'PROCESSING':
                return 'info';
            case 'PROCESSED_NO_CONTENT':
            case 'UPLOADED_NO_CONTENT':
            case 'FILE_NOT_FOUND_IN_OPENAI':
            case 'STALLED':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    /**
     * NEW: Get human-readable status message
     */
    private function getStatusMessage($status)
    {
        switch ($status) {
            case 'FULLY_SYNCED':
                return 'Fully synchronized with OpenAI';
            case 'UPLOADED_NOT_ATTACHED':
                return 'Uploaded but not attached to vector store';
            case 'PENDING_UPLOAD':
                return 'Waiting to be uploaded to OpenAI';
            case 'UPLOADED_NO_VECTOR_STORE':
                return 'Uploaded but no vector store exists';
            case 'IN_QUEUE':
                return 'Processing in background queue';
            case 'PROCESSING':
                return 'Currently being processed';
            case 'PROCESSED_NO_CONTENT':
                return 'Processed but no content extracted';
            case 'UPLOADED_NO_CONTENT':
                return 'Uploaded but missing content';
            case 'FILE_NOT_FOUND_IN_OPENAI':
                return 'File not found in OpenAI (may need re-upload)';
            case 'STALLED':
                return 'Processing stalled - needs attention';
            default:
                return 'Status unknown';
        }
    }

    /**
     * NEW: API endpoint to get assistant sync status
     */
    public function getSyncStatus($subdomain, PersonalAssistant $assistant)
    {
        if (! checkPermission('tenant.personal_assistant.view')) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        try {
            $statusInfo = $this->getAssistantStatusInfo($assistant);

            // Add sync progress if in progress
            $tenantId = tenant_id();
            $progressKey = "assistant_sync_progress_{$tenantId}_{$assistant->id}";
            $lockKey = "assistant_sync_{$tenantId}_{$assistant->id}";

            $syncProgress = Cache::get($progressKey);
            $syncInProgress = Cache::has($lockKey);

            return response()->json([
                'success' => true,
                'status_info' => $statusInfo,
                'sync_in_progress' => $syncInProgress,
                'sync_progress' => $syncProgress,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sync status', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get sync status',
            ], 500);
        }
    }

    /**
     * NEW: Bulk sync multiple assistants
     */
    public function bulkSync(Request $request, $subdomain)
    {
        if (! checkPermission('tenant.personal_assistant.edit')) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'assistant_ids' => 'required|array',
            'assistant_ids.*' => 'integer|exists:personal_assistants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $assistantIds = $request->assistant_ids;
            $tenantId = tenant_id();
            $results = [];

            foreach ($assistantIds as $assistantId) {
                $assistant = PersonalAssistant::find($assistantId);

                // Check ownership
                if (! $assistant) {
                    $results[$assistantId] = [
                        'success' => false,
                        'message' => 'Assistant not found',
                    ];

                    continue;
                }

                // Check if sync is already in progress
                $lockKey = "assistant_sync_{$tenantId}_{$assistantId}";
                if (Cache::has($lockKey)) {
                    $results[$assistantId] = [
                        'success' => false,
                        'message' => 'Sync already in progress',
                    ];

                    continue;
                }

                // Trigger sync
                try {
                    Cache::put($lockKey, true, 600);

                    $exitCode = Artisan::call('process:document', [
                        'assistant_id' => $assistantId,
                        'tenant_id' => $tenantId,
                    ]);

                    $results[$assistantId] = [
                        'success' => true,
                        'message' => 'Sync started successfully',
                        'exit_code' => $exitCode,
                    ];
                } catch (\Exception $e) {
                    Cache::forget($lockKey);
                    $results[$assistantId] = [
                        'success' => false,
                        'message' => 'Failed to start sync: '.$e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk sync initiated',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk sync error', [
                'error' => $e->getMessage(),
                'assistant_ids' => $request->assistant_ids ?? [],
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk sync failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * NEW: Force re-sync an assistant (clears all sync locks and restarts)
     */
    public function forceSync($subdomain, PersonalAssistant $assistant)
    {
        if (! checkPermission('tenant.personal_assistant.edit')) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        try {
            $tenantId = tenant_id();
            $lockKey = "assistant_sync_{$tenantId}_{$assistant->id}";
            $progressKey = "assistant_sync_progress_{$tenantId}_{$assistant->id}";

            // Clear any existing locks and progress
            Cache::forget($lockKey);
            Cache::forget($progressKey);

            // Reset assistant sync state
            $assistant->update([
                'last_synced_at' => null,
            ]);

            // Reset all document processing flags to force re-processing
            $assistant->documents()->update([
                'is_processed' => false,
                'openai_file_id' => null,
            ]);

            // Clear vector store ID to force recreation
            $assistant->update([
                'openai_vector_store_id' => null,
            ]);

            Log::info('Force sync initiated', [
                'assistant_id' => $assistant->id,
                'user_id' => Auth::id(),
                'tenant_id' => $tenantId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assistant reset successfully. You can now start a fresh sync.',
            ]);
        } catch (\Exception $e) {
            Log::error('Force sync error', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Force sync failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * NEW: Get detailed sync report for an assistant
     */
    public function getSyncReport($subdomain, PersonalAssistant $assistant)
    {
        if (! checkPermission('tenant.personal_assistant.view')) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied_note'),
            ], 403);
        }

        try {
            $statusInfo = $this->getAssistantStatusInfo($assistant);
            $documents = $assistant->documents()->get();

            $report = [
                'assistant' => [
                    'id' => $assistant->id,
                    'name' => $assistant->name,
                    'model' => $assistant->model,
                    'openai_assistant_id' => $assistant->openai_assistant_id,
                    'openai_vector_store_id' => $assistant->openai_vector_store_id,
                    'last_synced_at' => $assistant->last_synced_at,
                    'created_at' => $assistant->created_at,
                ],
                'sync_status' => $statusInfo,
                'documents' => $documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'filename' => $document->original_filename,
                        'file_size' => $document->file_size,
                        'mime_type' => $document->mime_type,
                        'openai_file_id' => $document->openai_file_id,
                        'is_processed' => $document->is_processed,
                        'processed_content_length' => strlen($document->processed_content ?? ''),
                        'status' => $this->getDocumentStatus($document),
                        'status_message' => $this->getStatusMessage($this->getDocumentStatus($document)),
                        'created_at' => $document->created_at,
                        'updated_at' => $document->updated_at,
                    ];
                }),
            ];

            // Add real-time OpenAI verification if API is configured
            if ($this->openAIService->isApiKeyConfigured()) {
                try {
                    $realStatus = $this->openAIService->getAssistantSyncStatus($assistant);
                    $report['openai_verification'] = $realStatus;
                } catch (\Exception $e) {
                    $report['openai_verification_error'] = $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating sync report', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate sync report',
            ], 500);
        }
    }
}
