<?php

namespace App\Livewire\Tenant\Tables\Filament;

use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;

class RoleFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = tenant_id();

        return Role::query()
            ->selectRaw('*, (SELECT COUNT(*) FROM `roles` i2 WHERE i2.id <= roles.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('tenant_id', $tenantId);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label('SR.NO')
                ->toggleable(),
            TextColumn::make('name')
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->sortable()
                ->toggleable()
                ->dateTime()
                ->since()
                ->tooltip(function ($record) {
                    return format_date_time($record->created_at);
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('edit')
                ->label(t('edit'))
                ->extraAttributes([
                    'class' => 'inline-flex rounded-md items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center',
                ])
                ->hidden(fn () => ! checkPermission('tenant.role.edit'))
                ->action(fn (Role $record) => $this->dispatch('editRole', roleId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex rounded-md items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->hidden(fn () => ! checkPermission('tenant.role.delete'))
                ->action(function (Role $record) {
                    $isUserAssigned = $record->users()->exists();

                    if ($isUserAssigned) {
                        // Notify the admin that this role is in use and cannot be deleted
                        $this->notify([
                            'message' => t('role_in_use_notify'),
                            'type' => 'warning',
                        ]);
                    } else {
                        // Dispatch event to confirm deletion
                        $this->dispatch('confirmDelete', roleId: $record->id);
                    }
                }),

        ];
    }

    #[On('tenant-role-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
