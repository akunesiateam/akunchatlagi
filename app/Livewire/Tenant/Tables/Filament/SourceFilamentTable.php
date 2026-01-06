<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\Source;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class SourceFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false; // Enable bulk actions for this table

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = tenant_id();

        return Source::query()
            ->selectRaw('sources.*, (SELECT COUNT(*) FROM sources i2 WHERE i2.id <= sources.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('tenant_id', $tenantId);
    }

    protected function getTableBulkActions(): array
    {
        if (! checkPermission('tenant.source.delete')) {
            return [];
        }

        return [
            \Filament\Tables\Actions\BulkAction::make('delete')
                ->label('Delete Selected')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function ($records) {
                    $cantDelete = [];
                    $deleted = 0;

                    foreach ($records as $record) {
                        $isSourceUsed = DB::table(tenant_subdomain().'_contacts')
                            ->where('source_id', $record->id)
                            ->exists();

                        if ($isSourceUsed) {
                            $cantDelete[] = $record->name;

                            continue;
                        }

                        $record->delete();
                        $deleted++;
                    }

                    if (! empty($cantDelete)) {
                        $this->dispatch('notify', [
                            'message' => count($cantDelete).' sources are in use and cannot be deleted: '.implode(', ', $cantDelete),
                            'type' => 'warning',
                        ]);
                    }

                    if ($deleted > 0) {
                        $this->dispatch('notify', [
                            'message' => t('source_delete_successfully'),
                            'type' => 'success',
                        ]);
                    }
                }),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label('SR.NO')
                ->toggleable(),
            TextColumn::make('name')
                ->label('Name')
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
                ->hidden(fn () => ! checkPermission('tenant.source.edit'))
                ->action(fn (Source $record) => $this->dispatch('editSource', sourceId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600  shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->hidden(fn () => ! checkPermission('tenant.source.delete'))
                ->action(function (Source $record) {
                    $isSourceUsed = DB::table(tenant_subdomain().'_contacts')
                        ->where('source_id', $record->id)
                        ->exists();

                    if ($isSourceUsed) {
                        $this->dispatch('notify', ['message' => t('source_in_use_notify'), 'type' => 'warning']);

                        return;
                    }

                    $this->dispatch('confirmDelete', sourceId: $record->id);
                }),
        ];
    }

    #[On('source-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
