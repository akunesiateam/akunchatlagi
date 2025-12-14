<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Enum\Tenant\WhatsAppTemplateRelationType;
use App\Facades\TenantCache;
use App\Models\Tenant\Contact;
use App\Models\Tenant\CustomField;
use App\Models\Tenant\Group;
use App\Models\Tenant\Source;
use App\Models\Tenant\Status;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class ContactFilamentTable extends BaseFilamentTable
{
    public $tenant_id;

    public $tenant_subdomain;

    protected bool $hasBulkActions = true;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected ?array $customFields = null;

    protected const CACHE_KEY_CUSTOM_FIELDS = 'contacts_table_custom_fields';

    protected const CACHE_KEY_USERS = 'contacts_table_users_for_filter';

    protected const CACHE_KEY_STATUSES = 'contacts_table_statuses_for_filter';

    protected const CACHE_KEY_SOURCES = 'contacts_table_sources_for_filter';

    protected const CACHE_KEY_GROUPS = 'contacts_table_groups_for_filter';

    protected const CACHE_DURATION = 600;

    public bool $showInitiateChatModal = false;

    public function boot(): void
    {
        $this->tenant_id = tenant_id();
        $this->tenant_subdomain = tenant_subdomain_by_tenant_id($this->tenant_id);
    }

    protected function getTableQuery(): Builder
    {
        $table_name = $this->tenant_subdomain.'_contacts';
        $query = Contact::fromTenant($this->tenant_subdomain)
            ->where('tenant_id', $this->tenant_id)
            ->selectRaw('*, (SELECT COUNT(*) FROM '.$table_name.' i2 WHERE i2.id <= '.$table_name.'.id AND i2.tenant_id = ?) as row_num', [$this->tenant_id])
            ->with([
                'user:id,firstname,lastname,avatar',
                'status:id,name,color',
                'source:id,name',
            ]);

        // Filter contacts based on permissions
        if (checkPermission('tenant.contact.view')) {
            return $query; // all contacts
        } elseif (checkPermission('tenant.contact.view_own')) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user->user_type === 'tenant' && $user->tenant_id === tenant_id() && $user->is_admin === false) {
                $staffId = $user->id;

                return $query->where('assigned_id', $staffId);
            }
        }

        return $query;
    }

    protected function getTableColumns(): array
    {
        if ($this->customFields === null) {
            $this->loadCustomFields();
        }

        $columns = [
            TextColumn::make('row_num')
                ->label(t('SR.NO'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('firstname')
                ->label(t('name'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->extraAttributes(['class' => 'w-[300px]']),

            TextColumn::make('type')
                ->label(t('type'))
                ->toggleable()
                ->sortable()
                ->searchable()
                ->formatStateUsing(fn ($state) => t($state)),

            TextColumn::make('phone')
                ->label(t('phone'))
                ->toggleable()
                ->searchable()
                ->sortable(),

            TextColumn::make('assigned_id')
                ->label(t('assigned'))
                ->toggleable()
                ->sortable()
                ->searchable()
                ->default(t('not_assigned'))
                ->formatStateUsing(function ($record) {
                    if (! $record->user) {
                        return '<span class="text-gray-400">Not assigned</span>';
                    }

                    $profileImage = ! empty($record->user->avatar) && Storage::disk('public')->exists($record->user->avatar)
                        ? Storage::url($record->user->avatar)
                        : asset('img/user-placeholder.jpg');

                    $fullName = e($record->user->firstname.' '.$record->user->lastname);

                    return '<div class="relative group flex items-center cursor-pointer">
                    <a href="'.tenant_route('tenant.staff.details', ['staffId' => $record->assigned_id]).'">
                        <img src="'.$profileImage.'"
                            class="w-9 h-9 rounded-full mx-3 object-cover"
                            data-tippy-content="'.$fullName.'">
                    </a>
                </div>';
                })
                ->html(),

            TextColumn::make('initiate_chat')
                ->label(t('initiate_chat'))
                ->default('initiate_chat')

                ->hidden(fn () => ! checkPermission('tenant.contact.create'))
                ->formatStateUsing(function ($record) {
                    $profileImage = asset('img/whatsapp.png');
                    $tooltip = e('Click to initiate chat');

                    // Return clickable avatar-style image
                    return '
            <div class="relative group flex items-center justify-center cursor-pointer">

                    <img src="'.$profileImage.'"
                         class="w-6 h-6 rounded-full object-cover "
                         data-tippy-content="'.$tooltip.'">

            </div>';
                })
                ->html()
                ->action(
                    Action::make('initiate_chat')
                        ->action(function ($record, $livewire) {
                            // ✅ Dispatch your event here
                            $livewire->dispatch('initiateChat', id: $record->id);
                        })
                ),

            TextColumn::make('status_id')
                ->label(t('status'))
                ->toggleable()
                ->sortable()
                ->searchable()
                ->formatStateUsing(function ($record) {
                    if (! empty($record->status->color)) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        style="background-color: '.e($record->status->color).'20; color: '.e($record->status->color).';">
                        '.e($record->status->name).'</span>';
                    }

                    return '<span class="text-gray-400">No Status</span>';
                })
                ->html(),

            TextColumn::make('source_id')
                ->label(t('source'))
                ->toggleable()
                ->sortable()
                ->searchable()
                ->formatStateUsing(fn ($record) => $record->source->name ?? 'N/A'),

            TextColumn::make('group_id')
                ->label(t('group'))
                ->toggleable()
                ->default('Groups not found')
                ->formatStateUsing(function ($state, $record) {
                    $groupIds = $record->getGroupIds() ?: [];

                    if (empty($groupIds)) {
                        return new HtmlString('<span class="text-orange-400 text-sm">Groups not found</span>');
                    }

                    $displayLimit = 3;
                    $displayGroupIds = array_slice($groupIds, 0, $displayLimit);

                    $groups = Group::whereIn('id', $displayGroupIds)
                        ->where('tenant_id', $record->tenant_id)
                        ->select('id', 'name')
                        ->get();

                    if ($groups->isEmpty()) {
                        return new HtmlString('<span class="text-orange-500 text-sm">Groups not found</span>');
                    }

                    $html = '<div class="flex flex-wrap gap-1">';
                    foreach ($groups as $group) {
                        $html .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-info-100 text-info-800">'
                            .e($group->name).'</span>';
                    }

                    $total = count($groupIds);
                    if ($total > $displayLimit) {
                        $html .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">'
                            .'+'.($total - $displayLimit).' more</span>';
                    }

                    return new HtmlString($html.'</div>');
                })
                ->wrap()
                ->html(),

            ToggleColumn::make('is_opted_out')
                ->label(t('opted_out_status'))
                ->toggleable()
                ->inline(false)
                ->extraAttributes(fn () => [
                    'style' => 'transform: scale(0.7); transform-origin: center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.contact.edit')) {
                        return;
                    }

                    $record->is_opted_out = $state ? 1 : 0;
                    $record->save();

                    $this->notify([
                        'message' => $state
                            ? t('contact_added_to_opted_out')
                            : t('contact_removed_from_opted_out'),
                        'type' => 'success',
                    ]);
                }),

            ToggleColumn::make('is_enabled')
                ->label(t('active'))
                ->toggleable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.contact.edit')) {
                        return;
                    }

                    $record->is_enabled = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = t('status_updated_successfully');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                }),

        ];

        // Add custom field columns
        foreach ($this->customFields as $field) {
            $col = TextColumn::make('custom_field_'.$field['field_name'])
                ->label($field['field_label'])
                ->toggleable()
                ->searchable(false) // Disable search for JSON fields as it's complex
                ->getStateUsing(function ($record) use ($field) {
                    $rawValue = $this->getCustomFieldRawValue($record, $field);
                    $formattedValue = $this->getCustomFieldValue($record, $field);

                    // Return just the formatted value for basic display
                    return $formattedValue;
                })
                ->formatStateUsing(function ($state) use ($field) {
                    // If no value, return empty
                    if (empty($state)) {
                        return new HtmlString('<span class="text-gray-400">-</span>');
                    }

                    // For number fields, right-align and format
                    if ($field['field_type'] === 'number' && is_numeric($state)) {
                        return new HtmlString(sprintf(
                            '<span class="block text-right">%s</span>',
                            number_format($state)
                        ));
                    }

                    // Default formatting for text and other fields
                    return new HtmlString('<div class="max-w-xs break-words">'.e($state).'</div>');
                })
                ->html();

            // Add appropriate CSS classes based on field type
            $classes = match ($field['field_type']) {
                'number' => 'text-right',
                'date' => 'whitespace-nowrap',
                default => ''
            };

            if ($classes) {
                $col->extraAttributes(['class' => $classes]);
            }

            $columns[] = $col;
        }

        $columns[] =
            TextColumn::make('created_at')
                ->label(t('created_at'))
                ->toggleable()
                ->dateTime()
                ->sortable()
                ->since()
                ->tooltip(function (TextColumn $column): ?string {
                    return format_date_time($column->getState());
                });

        return $columns;
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('bulk_delete')
                ->label(t('bulk_delete'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 rounded shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->action(fn (Collection $records) => $this->dispatch('bulkDelete', contactIds: $records->pluck('id')->toArray()))
                ->hidden(fn () => ! checkPermission('tenant.contact.delete')),

            BulkAction::make('initiate_chat')
                ->label(t('initiate_chat'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 justify-center',
                ])
                ->action(fn (Collection $records) => $this->dispatch('bulkInitiateChat', contactIds: $records->pluck('id')->toArray()))
                ->hidden(fn () => ! checkPermission('tenant.contact.create')),
        ];
    }

    #[On('bulkDelete')]
    public function handleBulkDelete(array $contactIds): void
    {
        if (! empty($contactIds) && count($contactIds) !== 0) {
            $this->dispatch('confirmDelete', $contactIds);
        } else {
            $this->notify(['type' => 'danger', 'message' => t('no_contact_selected')]);
        }
    }

    #[On('bulkInitiateChat')]
    public function bulkInitiateChat(array $contactIds): void
    {
        if (! empty($contactIds) && count($contactIds) !== 0) {
            $this->dispatch('bulkInitiateChatSending', $contactIds);
        } else {
            $this->notify(['type' => 'danger', 'message' => t('no_contact_selected')]);
        }
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-green-600 rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2',
                ])
                ->action(fn () => $this->exportContactsAsCsv())
                ->hidden(fn () => ! checkPermission('tenant.contact.view')),
        ];
    }

    protected function loadCustomFields(): void
    {
        $key = self::CACHE_KEY_CUSTOM_FIELDS.$this->tenant_id;

        $this->customFields = Cache::remember($key, 3600, function () {
            return CustomField::where('tenant_id', $this->tenant_id)
                ->where('is_active', true)
                ->where('show_on_table', true)
                ->orderBy('field_name')
                ->get()
                ->toArray();
        });
    }

    protected function getCustomFieldValue($contact, $field): string
    {
        $customData = $contact->custom_fields_data ?? [];
        $value = $customData[$field['field_name']] ?? '';

        // Format the value based on field type
        switch ($field['field_type']) {
            case 'date':
                return $value ? Carbon::parse($value)->format('Y-m-d') : '';
            case 'checkbox':
                if (is_array($value)) {
                    return implode(', ', array_filter($value));
                }

                return '';
            case 'dropdown':
                return (string) $value;
            default:
                return (string) $value;
        }
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('type')
                ->label(t('type'))
                ->options(collect(WhatsAppTemplateRelationType::getRelationtype())
                    ->map(fn ($value, $key) => ['value' => $key, 'label' => ucfirst($value)])
                    ->pluck('label', 'value')
                    ->toArray()),

            SelectFilter::make('assigned_id')
                ->label(t('assigned'))
                ->options(function () {
                    $users = User::where('tenant_id', $this->tenant_id)
                        ->select(['id', 'firstname', 'lastname'])
                        ->get();

                    $options = [];
                    foreach ($users as $user) {
                        $options[$user->id] = $user->firstname.' '.$user->lastname;
                    }

                    return $options;
                }),

            SelectFilter::make('status_id')
                ->label(t('status'))
                ->options(TenantCache::remember(self::CACHE_KEY_STATUSES, self::CACHE_DURATION, function () {
                    return Status::where('tenant_id', $this->tenant_id)->pluck('name', 'id')->toArray();
                }, ['contact-filters', 'statuses'])),

            SelectFilter::make('source_id')
                ->label(t('source'))
                ->options(TenantCache::remember(self::CACHE_KEY_SOURCES, self::CACHE_DURATION, function () {
                    return Source::where('tenant_id', $this->tenant_id)->pluck('name', 'id')->toArray();
                }, ['contact-filters', 'sources'])),

            SelectFilter::make('group_id')
                ->label(t('groups'))
                ->options(TenantCache::remember(self::CACHE_KEY_GROUPS, self::CACHE_DURATION, function () {
                    return Group::where('tenant_id', $this->tenant_id)->pluck('name', 'id')->toArray();
                }, ['contact-filters', 'groups']))
                ->query(function (Builder $query, array $data): Builder {
                    if (! empty($data['value'])) {
                        return $query->whereJsonContains('group_id', (int) $data['value']);
                    }

                    return $query;
                }),

        ];
    }

    protected function getTableActions(): array
    {
        return [

            ActionGroup::make([

                Action::make('view')
                    ->label('View')
                    ->action(fn (Contact $record) => $this->dispatch('viewContact', contactId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.contact.view')),

                Action::make('edit')
                    ->label('Edit')
                    ->action(fn (Contact $record) => $this->dispatch('editContact', contactId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.contact.edit')),

                Action::make('delete')
                    ->label('Delete')
                    ->action(fn (Contact $record) => $this->dispatch('confirmDelete', contactId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.contact.delete')),
            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    protected function getCustomFieldRawValue($contact, $field)
    {
        $customData = $contact->custom_fields_data ?? [];

        return $customData[$field['field_name']] ?? null;
    }

    #[On('contact-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }

    public function exportContactsAsCsv()
    {
        try {
            $contacts = $this->getContactsForExport();

            if (count($contacts) == 0) {

                $this->notify(['type' => 'danger', 'message' => 'No Contacts Found']);

                return;

            }

            $headers = $this->getExportHeaders();

            $filename = 'contacts-export-'.date('Y-m-d-H-i-s').'.csv';

            return response()->streamDownload(function () use ($contacts, $headers) {
                $handle = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                // Write headers
                fputcsv($handle, $headers);

                // Write data
                foreach ($contacts as $contact) {
                    fputcsv($handle, $this->getContactRowData($contact));
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            $this->notify([
                'message' => 'Export failed: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    protected function getContactsForExport()
    {
        if ($this->customFields === null) {
            $this->loadCustomFields();
        }

        return Contact::fromTenant($this->tenant_subdomain)
            ->where('tenant_id', $this->tenant_id)
            ->with([
                'user:id,firstname,lastname',
                'status:id,name',
                'source:id,name',
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function getExportHeaders()
    {
        $headers = [
            t('sr_no'),
            t('name'),
            t('type'),
            t('phone'),
            t('assigned'),
            t('status'),
            t('source'),
            t('groups'),
            t('active'),
            t('created_at'),
            t('updated_at'),
        ];

        // Add custom field headers
        foreach ($this->customFields as $field) {
            $headers[] = $field['field_label'];
        }

        return $headers;
    }

    protected function getContactRowData($contact)
    {
        // Get group names
        $groupIds = $contact->getGroupIds() ?: [];
        $groupNames = [];
        if (! empty($groupIds)) {
            $groups = Group::whereIn('id', $groupIds)
                ->where('tenant_id', $contact->tenant_id)
                ->pluck('name')
                ->toArray();
            $groupNames = $groups;
        }

        $row = [
            $contact->id,
            $contact->firstname.' '.$contact->lastname,
            t($contact->type),
            $contact->phone,
            $contact->user ? ($contact->user->firstname.' '.$contact->user->lastname) : t('not_assigned'),
            $contact->status->name ?? t('no_status'),
            $contact->source->name ?? t('no_source'),
            implode(', ', $groupNames) ?: t('no_groups'),
            $contact->is_enabled ? 1 : 0,
            $contact->created_at->format('Y-m-d H:i:s'),
            $contact->updated_at->format('Y-m-d H:i:s'),
        ];

        // Add custom field data
        foreach ($this->customFields as $field) {
            $value = $this->getCustomFieldValue($contact, $field);
            $row[] = $value ?: '';
        }

        return $row;
    }
}
