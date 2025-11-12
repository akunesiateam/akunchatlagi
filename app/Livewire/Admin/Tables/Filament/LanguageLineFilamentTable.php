<?php

namespace App\Livewire\Admin\Tables\Filament;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class LanguageLineFilamentTable extends Component implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    #[Url]
    public string $languageCode = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortColumn = 'key';

    #[Url]
    public string $sortDirection = 'asc';

    #[Url]
    public bool $showMissingOnly = false;

    #[Url]
    public bool $showTranslatedOnly = false;

    public bool $isTenantMode = false;

    public $editingKey = null;

    public $editingValue = '';

    protected $paginationTheme = 'bootstrap';

    public function mount(?string $languageCode = null): void
    {
        if ($languageCode) {
            $this->languageCode = $languageCode;
        } else {
            // Try to get from route or default to 'en'
            $this->languageCode = request()->route('code') ?? 'en';
        }

        // Detect if we're in tenant mode based on current context
        $this->isTenantMode = current_tenant() !== null;
    }

    public function render()
    {
        $records = $this->getFilteredRecords();
        $paginatedRecords = $this->paginateCollection($records, 25);

        return view('livewire.admin.tables.filament.language-line-filament-table', [
            'records' => $paginatedRecords,
            'languageName' => $this->getLanguageDisplayName(),
        ]);
    }

    protected function paginateCollection(Collection $collection, int $perPage = 25): LengthAwarePaginator
    {
        $currentPage = Paginator::resolveCurrentPage();
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' => Request::url(),
                'pageName' => 'page',
            ]
        );
    }

    public function getFilteredRecords(): Collection
    {
        $records = $this->loadLanguageData();

        // Apply search filter
        if (! empty($this->search)) {
            $records = $records->filter(function ($record) {
                return str_contains(strtolower($record['key']), strtolower($this->search)) ||
                       str_contains(strtolower($record['value']), strtolower($this->search)) ||
                       str_contains(strtolower($record['english_value']), strtolower($this->search));
            });
        }

        // Apply status filters
        if ($this->showMissingOnly) {
            $records = $records->filter(fn ($record) => empty($record['value']));
        }
        if ($this->showTranslatedOnly) {
            $records = $records->filter(fn ($record) => ! empty($record['value']));
        }

        // Apply sorting
        if ($this->sortColumn) {
            $records = $this->sortDirection === 'asc'
                ? $records->sortBy($this->sortColumn)
                : $records->sortByDesc($this->sortColumn);
        }

        return $records;
    }

    public function loadLanguageData(): Collection
    {
        // Get English data first
        $englishFilePath = $this->isTenantMode
            ? resource_path('lang/tenant_en.json')
            : resource_path('lang/en.json');

        $englishData = [];
        if (File::exists($englishFilePath)) {
            $englishData = json_decode(File::get($englishFilePath), true) ?? [];
        }

        // Get current language data
        $languageFilePath = $this->getLanguageFilePath($this->languageCode);
        $languageData = [];
        if (File::exists($languageFilePath)) {
            $languageData = json_decode(File::get($languageFilePath), true) ?? [];
        }

        // Convert to array format suitable for table
        return collect($englishData)->map(function ($englishValue, $key) use ($languageData) {
            return [
                'key' => (string) $key,
                'english_value' => $englishValue,
                'value' => $languageData[$key] ?? '',
                'language_code' => $this->languageCode,
            ];
        })->values();
    }

    protected function getLanguageFilePath(string $languageCode): string
    {
        if ($languageCode === 'en') {
            return $this->isTenantMode
                ? resource_path('lang/tenant_en.json')
                : resource_path('lang/en.json');
        }

        if ($this->isTenantMode) {
            // For tenant translations
            $tenantId = tenant_id();
            if ($tenantId) {
                return resource_path("lang/translations/tenant/{$tenantId}/tenant_{$languageCode}.json");
            } else {
                return public_path("lang/tenant_{$languageCode}.json");
            }
        } else {
            return resource_path("lang/translations/{$languageCode}.json");
        }
    }

    public function sortBy($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function toggleMissingFilter()
    {
        $this->showMissingOnly = ! $this->showMissingOnly;
        if ($this->showMissingOnly) {
            $this->showTranslatedOnly = false;
        }
        $this->resetPage();
    }

    public function toggleTranslatedFilter()
    {
        $this->showTranslatedOnly = ! $this->showTranslatedOnly;
        if ($this->showTranslatedOnly) {
            $this->showMissingOnly = false;
        }
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->showMissingOnly = false;
        $this->showTranslatedOnly = false;
        $this->resetPage();
    }

    public function editTranslation($key, $currentValue)
    {
        $this->editingKey = $key;
        $this->editingValue = $currentValue;
    }

    public function saveTranslation()
    {
        $this->updateTranslation($this->editingKey, $this->editingValue);
        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->editingKey = null;
        $this->editingValue = '';
    }

    protected function updateTranslation(string $key, string $value): void
    {
        try {
            // Validate and sanitize the input value
            $value = strip_tags($value);
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            // Prevent JSON injection
            if (preg_match('/^[\[\{].*[\]\}]$/', trim($value))) {
                session()->flash('error', t('invalid_json_format'));

                return;
            }

            // Get language file path
            $languageFilePath = $this->getLanguageFilePath($this->languageCode);

            // Read current data
            $languageData = [];
            if (File::exists($languageFilePath)) {
                $languageData = json_decode(File::get($languageFilePath), true) ?? [];
            }

            // Update the value
            $languageData[$key] = $value;

            // Ensure directory exists
            $dir = dirname($languageFilePath);
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            // Save back to file
            File::put($languageFilePath, json_encode($languageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Clear translation cache
            $locale = Session::get('locale', config('app.locale'));
            if ($this->isTenantMode && tenant_id()) {
                Cache::forget('translations.'.tenant_id()."_tenant_{$locale}");
            } else {
                Cache::forget("translations.{$locale}");
            }

            // Success message
            session()->flash('success', t('updated_successfully'));

        } catch (\Exception $e) {
            session()->flash('error', t('update_failed').': '.$e->getMessage());
        }
    }

    protected function getLanguageDisplayName(): string
    {
        try {
            if ($this->isTenantMode) {
                $language = \App\Models\Language::where('code', $this->languageCode)
                    ->where('tenant_id', tenant_id())
                    ->first();
            } else {
                $language = \App\Models\Language::where('code', $this->languageCode)
                    ->whereNull('tenant_id')
                    ->first();
            }

            return $language ? $language->name : ucfirst($this->languageCode);
        } catch (\Exception $e) {
            return ucfirst($this->languageCode);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
