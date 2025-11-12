<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\Status;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class StatusFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = tenant_id();

        return Status::query()
            ->selectRaw('statuses.*, (SELECT COUNT(*) FROM statuses i2 WHERE i2.id <= statuses.id AND i2.tenant_id = ?) as row_num', [$tenantId])
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
            ColorColumn::make('color')
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
                ->hidden(fn () => ! checkPermission('tenant.status.edit'))
                ->action(fn (Status $record) => $this->dispatch('editStatus', statusId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->hidden(fn () => ! checkPermission('tenant.status.delete'))
                ->action(function (Status $record) {
                    $isStatusUsed = DB::table(tenant_subdomain().'_contacts')
                        ->where('status_id', $record->id)
                        ->exists();

                    if ($isStatusUsed) {
                        $this->dispatch('notify', ['message' => t('status_delete_in_use_notify'), 'type' => 'warning']);

                        return;
                    }

                    $this->dispatch('confirmDelete', statusId: $record->id);
                }),
        ];
    }

    #[On('status-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
