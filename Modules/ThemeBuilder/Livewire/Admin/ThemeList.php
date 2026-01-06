<?php

namespace Modules\ThemeBuilder\Livewire\Admin;

use App\Models\Theme;
use App\Rules\PurifiedInput;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ThemeList extends Component
{
    use WithFileUploads;

    public $showThemeModal = false;

    public $isEditing = false;

    public $editingThemeId = null;

    // #[Rule('required|unique:themes|min:3|max:50')]
    public $name = '';

    // #[Rule('nullable|image|mimes:jpeg,png,jpg|max:1024')] // 1MB Max
    public $image;

    // Holds the current stored image URL for edit preview (relative disk path)
    public $existingImageUrl = null;

    public $themes;

    public $confirmingDeletion = false;

    public $themeToDelete = null;

    protected $listeners = [
        'confirmDelete' => 'confirmDelete',

    ];

    public function rules()
    {
        return [
            'name' => [
                'required',
                'min:3',
                'max:50',
                Rule::unique('themes', 'name')->ignore($this->editingThemeId),
                new PurifiedInput(t('sql_injection_error')),
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:1024', // 1MB
            ],
        ];
    }

    public function mount()
    {
        $this->loadThemes();
    }

    public function loadThemes()
    {
        $this->themes = Theme::all();
    }

    public function create()
    {
        $this->reset([
            'name',
            'image',
            'editingThemeId',
            'existingImageUrl',
        ]);

        $this->isEditing = false;
        $this->showThemeModal = true;
    }

    public function edit(Theme $theme)
    {
        $this->editingThemeId = $theme->id;
        $this->name = $theme->name;
        // expose existing image for immediate preview when editing
        $this->existingImageUrl = $theme->theme_url ? Storage::url($theme->theme_url) : null;
        $this->isEditing = true;
        $this->showThemeModal = true;
    }

    public function confirmDelete($id)
    {
        // store the id of the theme pending deletion and open the confirmation modal
        $this->themeToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function save()
    {
        $this->validate();

        // Determine if editing and find existing theme
        $theme = $this->isEditing
            ? Theme::findOrFail($this->editingThemeId)
            : new Theme;

        $theme->name = $this->name;

        // Determine folder slug from theme name and ensure uniqueness
        $desiredFolder = Str::slug($this->name, '') ?: 'theme';
        $candidate = $desiredFolder;

        $suffix = 1;

        // When editing, we should not collide with other themes; ignore current theme id when checking
        while (Theme::where('folder', $candidate)
            ->when($this->isEditing && isset($theme->id), fn ($q) => $q->where('id', '!=', $theme->id))
            ->exists()
        ) {
            $candidate = $desiredFolder.$suffix;
            $suffix++;
        }

        if (! $this->isEditing) {
            $theme->folder = $candidate;
        } else {
            // If editing and folder is empty or set to legacy 'default', update to a clean slug
            if (empty($theme->folder) || $theme->folder === 'default') {
                $theme->folder = $candidate;
            }
        }

        // ✅ Handle image upload if provided (store under the computed folder)
        if ($this->image) {
            // Define custom path using the theme folder
            $folderPath = "themes/{$theme->folder}";

            // Generate clean filename
            $filename = 'theme.'.$this->image->getClientOriginalExtension();

            // Delete old image if editing
            if ($this->isEditing && $theme->theme_url) {
                Storage::disk('public')->delete($theme->theme_url);
            }

            // Store the image manually in the same folder structure
            $path = $this->image->storeAs($folderPath, $filename, 'public');

            // Save relative path
            $theme->theme_url = $path; // e.g. "themes/thecore/theme.jpg"
        }

        // ✅ Set defaults if not already set
        $theme->version = $theme->version ?? '1.0';
        $theme->active = $theme->active ?? false;
        $theme->type = 'custom';

        $theme->save();

        // prepare message before resetting
        $message = $this->isEditing
            ? 'Theme updated successfully!'
            : 'Theme created successfully!';

        // ✅ Cleanup + reload
        $this->reset(['name', 'image', 'showThemeModal', 'isEditing', 'editingThemeId', 'existingImageUrl']);
        $this->loadThemes();

        $this->dispatch('notify', ['message' => $message, 'type' => 'success']);
    }

    public function delete()
    {

        if (! $this->themeToDelete) {
            // nothing to delete
            $this->confirmingDeletion = false;

            return;
        }

        $theme = Theme::findOrFail($this->themeToDelete);

        // Attempt to delete stored image if present. The DB stores the relative path (e.g. "themes/foo/theme.jpg").
        if (! empty($theme->theme_url)) {
            $storedPath = $theme->theme_url;

            // If the value looks like a Storage::url() (starts with '/storage' or contains '://'), normalize to the relative path
            if (str_starts_with($storedPath, '/storage/')) {
                $storedPath = ltrim(substr($storedPath, strlen('/storage/')), '/');
            } elseif (preg_match('#https?://#', $storedPath)) {
                // Try to strip the app url and leading /storage/ if present
                $parsed = parse_url($storedPath);
                $pathOnly = $parsed['path'] ?? $storedPath;
                if (str_starts_with($pathOnly, '/storage/')) {
                    $storedPath = ltrim(substr($pathOnly, strlen('/storage/')), '/');
                } else {
                    // fallback: take basename
                    $storedPath = basename($pathOnly);
                }
            }

            if (Storage::disk('public')->exists($storedPath)) {
                Storage::disk('public')->delete($storedPath);

                // If folder is now empty, attempt to remove it (safe operation)
                $folder = dirname($storedPath);
                if ($folder && $folder !== '.' && empty(Storage::disk('public')->allFiles($folder))) {
                    Storage::disk('public')->deleteDirectory($folder);
                }
            }
        }

        // Now delete the DB record
        $theme->delete();
        $this->themeToDelete = null;
        $this->confirmingDeletion = false;
        $this->loadThemes();
        $this->notify(['type' => 'success', 'message' => t('theme_delete_successfully')]);

        return redirect()->route('admin.theme.list');
    }

    public function activate($theme_folder)
    {
        $theme = Theme::where('folder', '=', $theme_folder)->first();

        if (isset($theme->id)) {
            $this->deactivateThemes();
            $theme->active = 1;
            $theme->save();
        }

        $this->loadThemes();
    }

    private function deactivateThemes()
    {
        Theme::query()->update(['active' => 0]);
    }

    public function customizeRedirect()
    {
        return redirect()->route('admin.theme.customize'); // define this route in web.php
    }

    public function render()
    {
        return view('ThemeBuilder::livewire.admin.theme-list');
    }
}
