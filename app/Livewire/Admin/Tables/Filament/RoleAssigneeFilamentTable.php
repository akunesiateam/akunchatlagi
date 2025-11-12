<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class RoleAssigneeFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public $role_id;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->role_id
            ? User::where('role_id', $this->role_id)->where('user_type', 'admin')
            : User::whereRaw('1 = 0');
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('firstname')
                ->label(t('name'))
                ->sortable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return $record->firstname.' '.$record->lastname;
                }),

        ];
    }

    #[On('role-assignee-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
