<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class TenantRoleAssigneeFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public $role_id;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = tenant_id();

        return $this->role_id
        ? User::where('role_id', $this->role_id)->where('tenant_id', $tenantId)
        : User::whereRaw('1 = 0');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('Name', 'firstname')
                ->searchable()
                ->sortable(),
        ];
    }

    #[On('tenant-role-assignee-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
