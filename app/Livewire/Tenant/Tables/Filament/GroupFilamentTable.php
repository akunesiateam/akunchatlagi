<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\Group;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class GroupFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = tenant_id();

        return Group::query()
            ->selectRaw('*, (SELECT COUNT(*) FROM `groups` i2 WHERE i2.id <= groups.id AND i2.tenant_id = ?) as row_num', [$tenantId])
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
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('edit')
                ->label(t('edit'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center rounded-md',
                ])
                ->hidden(fn () => ! checkPermission('tenant.group.edit'))
                ->action(fn (Group $record) => $this->dispatch('editGroup', groupId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->hidden(fn () => ! checkPermission('tenant.group.delete'))
                ->action(function (Group $record) {
                    $isGroupUsed = DB::table(tenant_subdomain().'_contacts')
                        ->where('group_id', $record->id)
                        ->exists();

                    if ($isGroupUsed) {
                        $this->dispatch('notify', ['message' => t('group_in_use_notify'), 'type' => 'warning']);

                        return;
                    }

                    $this->dispatch('confirmDelete', groupId: $record->id);
                }),
        ];
    }

    #[On('group-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
