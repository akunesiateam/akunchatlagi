<?php

namespace App\Livewire\Admin\Tables\Filament;

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
        $query = Role::query()
            ->where('tenant_id', null);

        return $query;
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('id')
                ->label(t('id'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('name')
                ->label(t('name'))
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('created_at')
                ->label(t('created_at'))
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
                ->hidden(fn () => ! checkPermission('admin.roles.edit'))
                ->action(fn (Role $record) => $this->dispatch('editRole', roleId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->hidden(fn () => ! checkPermission('admin.roles.delete'))
                ->extraAttributes([
                    'class' => 'inline-flex rounded-md items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->action(function (Role $record) {
                    $isUserAssigned = $record->users()->exists();
                    $this->dispatch(
                        $isUserAssigned ? 'notify' : 'confirmDelete',
                        $isUserAssigned
                            ? ['message' => t('role_in_use_notify'), 'type' => 'warning']
                            : ['roleId' => $record->id]
                    );

                }),
        ];
    }

    #[On('role-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
