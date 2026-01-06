<?php

namespace App\Livewire\Admin\Settings\Language;

use App\Models\Language;
use App\Rules\PurifiedInput;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class LanguageManager extends Component
{
    use WithFileUploads;

    public Language $language;

    public $showLanguageModal = false;

    public $confirmingDeletion = false;

    public $language_id = null;

    public $name;

    public $code;

    public $editUploadFile = null;

    public $editUploadResults = null;

    public $editUploadProgress = 0;

    public $editFilePreview = null;

    public $editIsUploading = false;

    protected $listeners = [
        'editLanguage' => 'editLanguage',
        'confirmDelete' => 'confirmDelete',
        'downloadLanguage' => 'downloadLanguage',
    ];

    public function mount()
    {
        $this->language = new Language;
    }

    public function createLanguage()
    {
        $this->resetForm();
        $this->showLanguageModal = true;
    }

    private function resetForm()
    {
        $this->reset();
        $this->resetValidation();
        $this->language = new Language;
    }

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('languages', 'name')
                    ->where(function ($query) {
                        return $query->where('tenant_id', tenant_id());
                    })
                    ->ignore($this->language->id ?? null),
                new PurifiedInput(t('sql_injection_error')),
            ],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:3',
                'regex:/^[a-zA-Z]+$/',
                Rule::unique('languages', 'code')
                    ->where(function ($query) {
                        return $query->where('tenant_id', tenant_id());
                    })
                    ->ignore($this->language->id ?? null),
                new PurifiedInput(t('sql_injection_error')),
            ],
        ];
    }

    public function editLanguage($languageCode)
    {
        if ($languageCode === 'en') {
            return $this->notify([
                'type' => 'danger',
                'message' => t('edit_english_language_not_allowed'),
            ]);
        }

        $language = Language::query()
            ->where('code', $languageCode)
            ->when(current_tenant(), function ($query) {
                $query->where('tenant_id', tenant_id());
            }, function ($query) {
                $query->whereNull('tenant_id');
            })
            ->firstOrFail();
        $this->language_id = $language->id;
        $this->name = $language->name;
        $this->code = $language->code;

        $this->resetValidation();
        $this->showLanguageModal = true;
    }

    public function save()
    {
        $isUpdate = isset($this->language_id);
        $code = strtolower($this->code);

        if ($isUpdate) {
            $this->language = Language::findOrFail($this->language_id);
        }

        $this->validate();

        if ($isUpdate) {
            $originalName = $this->language->name;
            $originalCode = $this->language->code;

            if ($this->name === $originalName && $code === $originalCode) {
                $this->showLanguageModal = false;

                return;
            }

            // Handle file operations for updates
            if ($originalCode !== $code) {
                $oldPath = resource_path("lang/translations/{$originalCode}.json");
                $newPath = resource_path("lang/translations/{$code}.json");

                if (File::exists($oldPath)) {
                    File::move($oldPath, $newPath);
                }
            }
        } else {
            // Handle file operations for new languages
            $this->language = new Language;

            $source = resource_path('lang/en.json');
            $destination = resource_path("lang/translations/{$code}.json");

            try {
                if (File::exists($source)) {
                    File::ensureDirectoryExists(dirname($destination));
                    File::copy($source, $destination);
                } else {
                    // Create empty JSON file if source doesn't exist
                    File::ensureDirectoryExists(dirname($destination));
                    File::put($destination, '{}');
                }
            } catch (\Exception $e) {
                $this->notify([
                    'type' => 'danger',
                    'message' => t('failed_to_create_language_file').$e->getMessage(),
                ]);

                return;
            }
        }

        // Set language properties
        $this->language->name = $this->name;
        $this->language->code = $code;

        // For admin languages, ensure tenant_id is null
        if (current_tenant()) {
            $this->language->tenant_id = null;
        }

        try {
            $this->language->save();
            // Cache will be automatically cleared by the Language model's boot() method
        } catch (\Exception $e) {
            $this->notify([
                'type' => 'danger',
                'message' => t('failed_to_save_language').$e->getMessage(),
            ]);

            return;
        }

        $this->showLanguageModal = false;
        $this->dispatch('language-table-refresh');

        $this->notify([
            'type' => 'success',
            'message' => $isUpdate ? t('language_update_successfully') : t('language_added_successfully'),
            'timeout' => 5000, // Extended timeout to 5 seconds
        ]);
    }

    public function confirmDelete($languageId)
    {
        $this->language_id = $languageId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        try {
            $language = Language::findOrFail($this->language_id);

            // Prevent deletion of English language
            if ($language->code === 'en') {
                $this->notify([
                    'type' => 'danger',
                    'message' => t('edit_english_language_not_allowed'),
                ]);

                return;
            }

            // Delete language files if they exist
            $filePath = resource_path("lang/translations/{$language->code}.json");
            $filePathvendor = resource_path("lang/translations/vendor_{$language->code}.json");

            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            if (File::exists($filePathvendor)) {
                File::delete($filePathvendor);
            }

            // Delete language record
            $language->delete();
            // Cache will be automatically cleared by the Language model's boot() method

            $this->confirmingDeletion = false;
            $this->dispatch('language-table-refresh');

            $this->notify([
                'type' => 'success',
                'message' => t('language_delete_successfully'),
                'timeout' => 5000, // Extended timeout to 5 seconds
            ]);
        } catch (\Exception $e) {
            $this->notify([
                'type' => 'danger',
                'message' => t('failed_to_delete_language').' '.$e->getMessage(),
            ]);
        }
    }

    public function downloadLanguage($languageId)
    {
        try {
            $language = Language::findOrFail($languageId);
            $filePath = $language->getFilePathAttribute();

            if (! File::exists($filePath)) {
                $this->notify([
                    'type' => 'danger',
                    'message' => t('language_file_not_found'),
                ]);

                return;
            }

            return response()->download($filePath);
        } catch (\Exception $e) {
            $this->notify([
                'type' => 'danger',
                'message' => t('download_failed').': '.$e->getMessage(),
            ]);
        }
    }

    public function updatedEditUploadFile()
    {
        if ($this->editUploadFile) {
            $this->generateEditFilePreview();
            $this->validateEditFile();
        }
    }

    private function validateEditFile()
    {
        try {
            $this->validate([
                'editUploadFile' => [
                    'required',
                    'file',
                    'mimes:json',
                    'max:2048',
                    function ($attribute, $value, $fail) {
                        if (strtolower($value->getClientOriginalExtension()) !== 'json') {
                            $fail(t('file_must_be_valid_json'));
                        }

                        $allowedMimes = ['application/json', 'text/json', 'text/plain'];
                        if (! in_array($value->getMimeType(), $allowedMimes)) {
                            $fail(t('file_must_be_valid_json'));
                        }

                        // Validate file name pattern: tenant_{code}.json
                        $expectedFileName = "{$this->code}.json";
                        $actualFileName = $value->getClientOriginalName();

                        if (strtolower($actualFileName) !== strtolower($expectedFileName)) {
                            $fail("The file name must be exactly '{$expectedFileName}'. Current file name: '{$actualFileName}'");
                        }
                    },
                ],
            ]);

            $content = file_get_contents($this->editUploadFile->getRealPath());
            $validation = $this->validateJsonFile($content);
            $this->editUploadResults = $validation;

        } catch (\Exception $e) {
            $this->editUploadResults = [
                'valid' => false,
                'errors' => ['File validation failed: '.$e->getMessage()],
                'warnings' => [],
                'key_count' => 0,
            ];
        }
    }

    private function validateJsonFile(string $jsonContent): array
    {
        $results = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'key_count' => 0,
            'file_size' => strlen($jsonContent),
            'structure_info' => [],
        ];

        try {
            $jsonContent = trim($jsonContent);
            if ($jsonContent === '') {
                $results['errors'][] = t('empty_json_file');

                return $results;
            }

            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $results['errors'][] = t('invalid_json_format').': '.json_last_error_msg();

                return $results;
            }

            if (! is_array($data)) {
                $results['errors'][] = t('json_must_be_object_or_array');

                return $results;
            }

            // Count only string values in the translation file
            $keyCount = 0;
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $keyCount++;
                }
            }

            $results['key_count'] = $keyCount;
            $results['structure_info'] = [
                'sample_keys' => array_slice(array_keys($data), 0, 5, true),
                'total_keys' => $keyCount,
                'raw_content' => substr($jsonContent, 0, 100).'...', // First 100 chars for debugging
                'decoded_data' => array_slice($data, 0, 3, true), // First 3 items for debugging
            ];

            if ($keyCount > 0) {
                $results['valid'] = true;
            } else {
                $results['warnings'][] = t('no_valid_translation_strings_found');
                $results['errors'][] = t('file_structure').': '.gettype($data).', '.t('keys_found').': '.count($data);
            }

        } catch (\Exception $e) {
            $results['errors'][] = t('processing_error').': '.$e->getMessage();
        }

        return $results;
    }

    public function resetEditUploadState()
    {
        $this->editUploadFile = null;
        $this->editUploadResults = null;
        $this->editUploadProgress = 0;
        $this->editFilePreview = null;
        $this->editIsUploading = false;
    }

    public function processEditUpload()
    {
        if (! $this->editUploadFile) {
            return true; // No file to process, continue with update
        }

        if (! $this->editUploadResults || ! $this->editUploadResults['valid']) {
            $this->notify([
                'type' => 'danger',
                'message' => t('file_must_be_valid_json'),
            ]);

            return false;
        }

        $this->editIsUploading = true;
        $this->editUploadProgress = 25;

        try {
            $uploadedContent = file_get_contents($this->editUploadFile->getRealPath());

            if (! $uploadedContent) {
                return false;
            }
            $this->editUploadProgress = 50;

            $language = Language::findOrFail($this->language_id);
            $targetPath = resource_path("lang/translations/{$language->code}.json");

            // Create directory if it doesn't exist
            if (! File::exists(dirname($targetPath))) {
                File::makeDirectory(dirname($targetPath), 0755, true);
            }

            $this->editUploadProgress = 75;

            // Directly write the file content to the target path
            if (! File::put($targetPath, $uploadedContent)) {
                $this->notify([
                    'type' => 'danger',
                    'message' => t('language_file_not_found'),
                ]);

                return false;
            }

            $this->editUploadProgress = 100;
            $this->editIsUploading = false;

            $this->notify([
                'type' => 'success',
                'message' => t('language_file_updated_successfully'),
            ]);

            $this->resetEditUploadState();

            return true;

        } catch (\Exception $e) {
            $this->editIsUploading = false;
            $this->editUploadProgress = 0;

            $this->notify([
                'type' => 'danger',
                'message' => t('upload_failed').': '.$e->getMessage(),
            ]);
        }
    }

    private function generateEditFilePreview()
    {
        try {
            $content = file_get_contents($this->editUploadFile->getRealPath());
            $size = $this->editUploadFile->getSize();

            $this->editFilePreview = [
                'name' => $this->editUploadFile->getClientOriginalName(),
                'size' => $this->formatBytes($size),
                'is_valid_json' => false,
                'key_count' => 0,
                'sample_keys' => [],
                'error' => null,
                'debug_info' => null,
            ];

            // Check file name pattern first
            $expectedFileName = "{$this->code}.json";
            $actualFileName = $this->editUploadFile->getClientOriginalName();

            if (strtolower($actualFileName) !== strtolower($expectedFileName)) {
                $this->editFilePreview['error'] = "File name must be '{$expectedFileName}', but got '{$actualFileName}'";

                return;
            }

            // Try to parse JSON
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $this->editFilePreview['is_valid_json'] = true;

                // Count only string values as valid translations
                $keyCount = 0;
                $sampleKeys = [];
                foreach ($data as $key => $value) {
                    if (is_string($value)) {
                        $keyCount++;
                        if (count($sampleKeys) < 3) {
                            $sampleKeys[] = $key;
                        }
                    }
                }

                $this->editFilePreview['key_count'] = $keyCount;
                $this->editFilePreview['sample_keys'] = $sampleKeys;
                $this->editFilePreview['debug_info'] = [
                    'content_preview' => substr($content, 0, 100).'...',
                    'total_array_keys' => count($data),
                    'valid_string_keys' => $keyCount,
                    'first_few_items' => array_slice($data, 0, 3, true),
                ];
            } else {
                $this->editFilePreview['error'] = t('invalid_json_format').': '.json_last_error_msg();
                $this->editFilePreview['debug_info'] = [
                    'content_preview' => substr($content, 0, 100).'...',
                ];
            }
        } catch (\Exception $e) {
            $this->editFilePreview = [
                'name' => $this->editUploadFile->getClientOriginalName(),
                'size' => 'Unknown',
                'is_valid_json' => false,
                'key_count' => 0,
                'sample_keys' => [],
                'error' => $e->getMessage(),
                'debug_info' => ['exception_trace' => $e->getTraceAsString()],
            ];
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    public function syncLanguage()
    {
        Artisan::call('languages:reset --admin');
        $this->notify([
            'type' => 'success',
            'message' => t('language_synchronized_successfully'),
        ]);
    }

    public function refreshTable()
    {
        $this->dispatch('language-table-refresh');
    }

    public function render()
    {
        return view('livewire.admin.settings.language.language-manager');
    }
}
