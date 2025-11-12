<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\Tax;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class TaxFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Tax::query();
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

            TextColumn::make('rate')
                ->label('Rate (%)')
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('description')
                ->label('Description')
                ->toggleable()
                ->searchable()
                ->sortable()
                ->html()
                ->limit(50)
                ->tooltip(function ($record) {
                    return $record->description;
                }),

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
                ->action(fn (Tax $record) => $this->dispatch('editTax', id: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->action(fn (Tax $record) => $this->dispatch('confirmDelete', id: $record->id)),
        ];
    }

    #[On('tax-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
