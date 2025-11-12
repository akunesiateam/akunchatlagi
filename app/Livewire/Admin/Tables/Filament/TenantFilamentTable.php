<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Enum\TenantStatus;
use App\Events\Tenant\TenantStatusChanged;
use App\Models\Tenant;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class TenantFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Tenant::query()
            ->join('users', 'tenants.id', '=', 'users.tenant_id')
            ->join(DB::raw('(
                    SELECT MIN(id) as id
                    FROM users
                    WHERE is_admin = 1
                    GROUP BY tenant_id
                ) as oldest_admins'), 'users.id', '=', 'oldest_admins.id')
            ->select([
                'tenants.id',
                'tenants.company_name',
                'tenants.status',
                'tenants.domain',
                'tenants.created_at',
                'tenants.deleted_date',
                'users.id as user_id',
                'users.firstname',
                'users.lastname',
                'users.email',
                'users.is_admin',
                'users.email_verified_at',
            ]);
    }

    protected function getTableColumns(): array
    {
        $options = $this->statusSelectOptions();

        return [

            TextColumn::make('id')
                ->label(t('id'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('firstname')
                ->label(t('name'))
                ->sortable()
                ->toggleable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    $name = e($record->firstname.' '.$record->lastname);

                    // Conditional class based on deleted_date
                    $class = $record->deleted_date
                        ? 'text-red-600 opacity-75'
                        : '';

                    return <<<HTML
            <span class="{$class}">
                {$name}
            </span>
        HTML;
                }),

            TextColumn::make('email', 'user.email')
                ->label('Email')
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('company_name')
                ->label('Company Name')
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('status')
                ->label('Status')
                ->sortable()
                ->toggleable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    if ($record->deleted_date) {
                        $tooltipContent = t('will_be_deleted');

                        return <<<HTML
                <div class="bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400 px-2.5 py-0.5 rounded-full text-xs font-medium inline-flex items-center cursor-help"
                    x-data>
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                    {$tooltipContent}
                </div>
            HTML;
                    }
                    $colorMap = \App\Enum\TenantStatus::colorMap();
                    $default = \App\Enum\TenantStatus::defaultColors();
                    $color = $colorMap[$state] ?? $default;
                    $state = t($state);

                    return <<<HTML
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {$color['bg']} {$color['text']} border {$color['border']} cursor-pointer hover:opacity-80 transition">
                <span class="w-2 h-2 mr-2 rounded-full {$color['dot']}"></span>
                {$state}
            </span>
        HTML;
                })
                ->action(
                    Action::make('view_status')
                        ->label('View Status')
                        ->modal() // enables modal behavior
                        ->modalHeading('Tenant Status Details')
                        ->modalDescription('Static info about this tenant’s status.')
                        ->modalSubmitAction(false) // hides the Save button
                        ->modalCancelActionLabel('Close') // only show a Close button
                        ->modalWidth('sm')
                        ->modalContent(fn ($record) => view('components.select-status', [
                            'options' => \App\Enum\TenantStatus::labels(),
                            'selected' => $record->status,
                            'userId' => $record->id,
                        ]))
                        ->hidden(fn ($record) => filled($record->deleted_date))

                ),

            TextColumn::make('created_at')
                ->label(t('created_at'))
                ->sortable()
                ->toggleable()
                ->dateTime()
                ->since()
                ->tooltip(function ($record) {
                    return format_date_time($record->created_at);
                }),

            TextColumn::make('visit_tenant')
                ->label('Visit Tenant')
                ->default('Visit Tenant')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    // Only show if permission granted
                    if (! checkPermission('admin.tenants.login')) {
                        return '<span class="text-gray-400 text-xs">No Access</span>';
                    }

                    $url = route('admin.login.as', ['id' => $record->user_id]);

                    // ✅ Button UI same as your action
                    return <<<HTML
            <a href="{$url}"
               class="inline-flex items-center justify-center px-3 py-1 text-sm border border-success-300 rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-success-100 text-success-800 hover:bg-success-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success-300 dark:bg-slate-700 dark:border-slate-500 dark:text-success-400 dark:hover:border-success-600 dark:hover:bg-success-600 dark:hover:text-white dark:focus:ring-offset-slate-800">
               Login as Tenant
            </a>
        HTML;
                })

                ->sortable(false)
                ->searchable(false),
        ];
    }

    public function getTableActions(): array
    {
        return [

            // Confirm registration (for Tenant model)
            Action::make('confirm_registration')
                ->label('')
                ->visible(function (Tenant $record) {
                    return $record->is_admin == 1 &&
                        empty($record->email_verified_at) &&
                        get_setting('tenant.isEmailConfirmationEnabled');
                })
                ->extraAttributes([
                    'class' => 'inline-flex items-center px-2 py-1 text-xs font-medium text-primary-600 bg-primary-100 rounded hover:bg-primary-200 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:bg-primary-900 dark:text-primary-200 border border-primary-300 dark:hover:border-primary-600',
                ])
                ->dispatch('confirmTenantRegistration', fn (Tenant $record) => [
                    'tenantId' => $record->id,
                ])->icon('heroicon-o-envelope'),

            ActionGroup::make([
                Action::make('view')
                    ->label('View')
                    ->action(fn (Tenant $record) => $this->dispatch('viewTenant', tenantId: $record->id))
                    ->hidden(fn () => ! checkPermission('admin.tenants.view')),

                Action::make('edit')
                    ->label('Edit')
                    ->action(fn (Tenant $record) => $this->dispatch('editTenant', tenantId: $record->id))
                    ->hidden(fn () => ! checkPermission('admin.tenants.edit')),

                Action::make('delete')
                    ->label(fn (Tenant $record) => $record->deleted_date ? 'Restore' : 'Delete')
                    ->action(function (Tenant $record) {
                        if ($record->deleted_date) {
                            $this->dispatch('restoreTenant', tenantId: $record->id);
                        } else {
                            $this->dispatch('confirmDelete', tenantId: $record->id);
                        }
                    })
                    ->hidden(fn () => ! checkPermission('admin.tenants.delete')),

            ])->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    public function statusSelectOptions()
    {
        $labels = TenantStatus::labels();

        if (empty($labels)) {
            // Fallback in case the enum method fails
            return [
                'active' => 'Active',
                'deactive' => 'Deactive',
                'suspended' => 'Suspended',
                'expired' => 'Expired',
            ];
        }

        return $labels;
    }

    public function statusChanged($statusId, $userId)
    {
        if (checkPermission('admin.tenants.edit')) {
            // Find the tenant by ID
            $tenant = Tenant::find($userId);

            if ($tenant) {
                // Store original status for comparison
                $originalStatus = $tenant->status;

                // Update tenant status directly with the string value
                $tenant->status = $statusId;

                // Save the tenant
                $tenant->save();

                Cache::forget("tenant_{$tenant->id}");

                event(new TenantStatusChanged($tenant, $originalStatus, $statusId));

                // Show success notification
                $this->notify([
                    'message' => t('tenant_status_updated', ['status' => ucfirst($statusId)]),
                    'type' => 'success',
                ]);
            } else {
                // Tenant not found - show error
                $this->notify([
                    'message' => t('tenant_not_found'),
                    'type' => 'error',
                ]);
            }
        } else {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.tenants.list'));
        }
    }

    #[On('tenant-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
