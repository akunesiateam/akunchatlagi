<?php

namespace Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\AiAssistant\Jobs\ProcessAssistantDocument;
use Modules\AiAssistant\Models\Tenant\AssistantDocument;
use Modules\AiAssistant\Models\Tenant\PersonalAssistant;
use Modules\AiAssistant\Services\OpenAIAssistantService;

class AssistantDocumentController extends Controller
{
    public function store($subdomain, Request $request, $assistantId)
    {
        $assistant = PersonalAssistant::findOrFail($assistantId);

        // Support both single and multiple files
        $rules = [
            'document' => 'required|file|mimes:pdf,doc,docx,txt,md,markdown,csv|max:10240', // 10MB max
        ];

        // Check if multiple files are being uploaded
        if ($request->hasFile('documents')) {
            $rules = [
                'documents' => 'required|array|max:10',
                'documents.*' => 'required|file|mimes:pdf,doc,docx,txt,md,markdown,csv|max:10240',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedDocuments = [];
        $files = $request->hasFile('documents') ? $request->file('documents') : [$request->file('document')];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $fileType = $file->getClientOriginalExtension();
            $storedFilename = Str::random(40).'.'.$fileType;

            // Store file
            $path = $file->storeAs('tenant/'.tenant_id().'/assistant_documents', $storedFilename, 'public');

            // Create document record
            $document = AssistantDocument::create([
                'assistant_id' => $assistant->id,
                'original_filename' => $originalName,
                'stored_filename' => $storedFilename,
                'file_path' => $path,
                'file_type' => $fileType,
                'tenant_id' => tenant_id(),
            ]);

            // Queue document processing job
            ProcessAssistantDocument::dispatch($document);

            $uploadedDocuments[] = $document;
        }

        return response()->json([
            'success' => true,
            'documents' => $uploadedDocuments,
            'message' => count($uploadedDocuments) === 1
                ? 'Document uploaded successfully!'
                : count($uploadedDocuments).' documents uploaded successfully!',
        ]);
    }

    public function destroy($subdomain, $id)
    {
        $document = AssistantDocument::findOrFail($id);
        $assistant = $document->assistant;

        try {
            // Clean up OpenAI resources first
            $openAIService = app(OpenAIAssistantService::class);
            $cleanupResult = $openAIService->cleanupDocumentResources($document);

            // Log cleanup results
            if (isset($cleanupResult['results'])) {
                foreach ($cleanupResult['results'] as $action => $result) {
                    if ($result['success']) {
                        Log::info("Document cleanup successful: {$action}", [
                            'document_id' => $document->id,
                            'openai_file_id' => $document->openai_file_id,
                        ]);
                    } else {
                        Log::warning("Document cleanup failed: {$action}", [
                            'document_id' => $document->id,
                            'openai_file_id' => $document->openai_file_id,
                            'error' => $result['error'],
                        ]);
                    }
                }
            }

            // Delete the local file from storage
            Storage::disk('public')->delete($document->file_path);

            // Delete the document record
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Document deletion failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete document: '.$e->getMessage(),
            ], 500);
        }
    }
}
