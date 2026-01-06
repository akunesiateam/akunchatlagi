<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\TenantLanguage;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class TenantLanguageFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return TenantLanguage::query();
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

            TextColumn::make('code')
                ->label('Code')
                ->sortable()
                ->toggleable()
                ->searchable(),

        ];
    }

    protected function getTableActions(): array
    {

        return [
            Action::make('translate')
                ->label(t('translate'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-success-600 shadow-sm hover:bg-success-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-success-600 rounded-md',
                ])
                ->visible(fn (TenantLanguage $record) => ! ($record->code === 'en' || strcasecmp($record->name, 'English') === 0))
                ->action(fn (TenantLanguage $record) => $this->dispatch('translateLanguage', code: $record->code)),

            Action::make('download')
                ->label(t('download'))
                ->extraAttributes([
                    'class' => 'iinline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-info-600 shadow-sm hover:bg-info-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-info-600 rounded-md',
                ])
                ->visible(fn (TenantLanguage $record) => ! ($record->code === 'en' || strcasecmp($record->name, 'English') === 0))
                ->action(fn (TenantLanguage $record) => $this->dispatch('downloadLanguage', languageId: $record->id)),

            Action::make('edit')
                ->label(t('edit'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center rounded-md',
                ])
                ->visible(fn (TenantLanguage $record) => ! ($record->code === 'en' || strcasecmp($record->name, 'English') === 0))
                ->action(fn (TenantLanguage $record) => $this->dispatch('editLanguage', languageCode: $record->code)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->visible(fn (TenantLanguage $record) => ! ($record->code === 'en' || strcasecmp($record->name, 'English') === 0))
                ->action(fn (TenantLanguage $record) => $this->dispatch('confirmDelete', languageId: $record->id)),
        ];
    }

    #[On('tenant-language-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
