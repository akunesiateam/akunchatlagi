<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\BotFlow;
use Corbital\ModuleManager\Facades\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BotFlowController extends Controller
{
    public function edit($subdomain, $id)
    {

        $flow = BotFlow::where('tenant_id', tenant_id())->findOrFail($id);
        $isAiAssistantModuleEnabled = ModuleManager::isActive('AiAssistant');

        return view('tenant.bot-flows.edit', compact('flow', 'isAiAssistantModuleEnabled'));
    }

    public function upload(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'file' => 'required|file',
                'type' => 'required|string|in:image,video,audio,document',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Get the file
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            // Define allowed extensions for each media type
            $allowedExtensions = get_meta_allowed_extension();

            // Check if extension is allowed for this media type
            if (! isset($allowedExtensions[$request->type]['extension']) || ! in_array('.'.$extension, explode(', ', $allowedExtensions[$request->type]['extension']))) {
                return response()->json([
                    'message' => "Invalid file type. Allowed types for {$request->type} are: ".$allowedExtensions[$request->type]['extension'],
                ], 422);
            }

            // Generate a unique filename
            $filename = Str::uuid().'.'.$extension;

            // Define the storage path based on media type
            $path = "bot_flow/{$request->type}s"; // e.g., images, videos, audios, documents

            // Store the file in the public disk
            $storedPath = $file->storeAs($path, $filename, 'public');

            // Verify the file was stored successfully
            if (! $storedPath) {
                return response()->json([
                    'message' => 'File upload failed',
                ], 500);
            }

            // Generate the public URL for the file
            $url = Storage::disk('public')->url($storedPath);

            // Return the URL to the frontend
            return response()->json([
                'url' => $url,
                'fileName' => $file->getClientOriginalName(),
            ]);
        } catch (\Exception $e) {
            // Return a detailed error response
            return response()->json([
                'message' => 'An error occurred during file upload',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function save(Request $request, $subdomain)
    {
        // Check if this is a flow data save (only id and flow_data) vs name/description save
        $isFlowDataSave = $request->has('flow_data') && ! $request->has('name');

        // Different validation rules based on what's being saved
        if ($isFlowDataSave) {
            $validator = Validator::make($request->all(), [
                'flow_data' => 'required|json',
                'id' => 'required|exists:bot_flows,id',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'flow_data' => 'nullable|json',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->id) {
            $flow = BotFlow::where('tenant_id', tenant_id())->findOrFail($request->id);

            if ($isFlowDataSave) {
                // Only update flow_data
                $flow->update(['flow_data' => $request->flow_data]);
            } else {
                // Prepare data for name/description update
                $flowData = [];

                if ($request->has('name')) {
                    $flowData['name'] = $request->name;
                }

                if ($request->has('description')) {
                    $flowData['description'] = $request->description;
                }

                if ($request->has('is_active')) {
                    $flowData['is_active'] = $request->has('is_active') ? 1 : 0;
                }

                // Only update flow_data if provided (to avoid overwriting existing data)
                if ($request->has('flow_data') && ! is_null($request->flow_data)) {
                    $flowData['flow_data'] = $request->flow_data;
                }

                $flow->update($flowData);
            }

            $message = t('flow_updated_successfully');
        } else {
            // For new flows, both name and flow_data are required
            if (! $request->has('flow_data') || ! $request->has('name')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Name and flow data are required for new flows',
                ], 422);
            }

            $flowData = [
                'tenant_id' => tenant_id(),
                'name' => $request->name,
                'flow_data' => $request->flow_data,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            if ($request->has('description')) {
                $flowData['description'] = $request->description;
            }

            $flow = BotFlow::create($flowData);
            $message = t('flow_created_successfully');
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'flow_id' => $flow->id,
        ]);
    }

    public function get($subdomain, $id)
    {

        $flow = BotFlow::where('tenant_id', tenant_id())->findOrFail($id);

        return response()->json([
            'success' => true,
            'flow' => json_decode($flow->flow_data),
        ]);
    }

    public function delete($id)
    {
        if (! checkPermission(['bot_flows.delete'])) {
            return response()->json([
                'success' => false,
                'message' => t('access_denied'),
            ], 403);
        }

        $flow = BotFlow::where('tenant_id', tenant_id())->findOrFail($id);
        $flow->delete();

        return response()->json([
            'success' => true,
            'message' => t('flow_deleted_successfully'),
        ]);
    }

    /**
     * Export flow as JSON file
     *
     * @param  string  $subdomain  Tenant subdomain
     * @param  int  $id  Flow ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function export($subdomain, $id)
    {
        try {
            $flow = BotFlow::where('tenant_id', tenant_id())->findOrFail($id);

            // Prepare export data
            $exportData = [
                'name' => $flow->name,
                'description' => $flow->description,
                'flow_data' => json_decode($flow->flow_data, true),
                'is_active' => $flow->is_active,
                'exported_at' => now()->toIso8601String(),
                'version' => '1.0',
            ];

            // Generate filename
            $filename = Str::slug($flow->name).'-'.date('Y-m-d-His').'.json';

            return response()->json($exportData)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => t('flow_export_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import flow from JSON file
     *
     * @param  string  $subdomain  Tenant subdomain
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request, $subdomain)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:json|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Read and decode JSON file
            $jsonContent = file_get_contents($request->file('file')->getRealPath());
            $flowData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => t('invalid_json_file'),
                ], 422);
            }

            // Validate required fields
            if (! isset($flowData['name']) || ! isset($flowData['flow_data'])) {
                return response()->json([
                    'success' => false,
                    'message' => t('invalid_flow_format'),
                ], 422);
            }

            // Check if flow name already exists, if so, append timestamp
            $name = $flowData['name'];
            $existingFlow = BotFlow::where('tenant_id', tenant_id())
                ->where('name', $name)
                ->first();

            if ($existingFlow) {
                $name = $name.' ('.now()->format('Y-m-d H:i').')';
            }

            // Create new flow
            $flow = BotFlow::create([
                'tenant_id' => tenant_id(),
                'name' => $name,
                'description' => $flowData['description'] ?? '',
                'flow_data' => json_encode($flowData['flow_data']),
                'is_active' => $flowData['is_active'] ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => t('flow_imported_successfully'),
                'flow_id' => $flow->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => t('flow_import_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statuses for dropdown in Update Contact node
     */
    public function getStatuses()
    {
        $statuses = \App\Models\Tenant\Status::where('tenant_id', tenant_id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($statuses);
    }

    /**
     * Get sources for dropdown in Update Contact node
     */
    public function getSources()
    {
        $sources = \App\Models\Tenant\Source::where('tenant_id', tenant_id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($sources);
    }

    /**
     * Get groups for dropdown in Update Contact node
     */
    public function getGroups()
    {
        $groups = \App\Models\Tenant\Group::where('tenant_id', tenant_id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($groups);
    }
}
