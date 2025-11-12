<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\Language;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class LanguageFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Language::query();
        if (! tenant_check()) {
            $query->where('tenant_id', tenant_id());
        }

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

            TextColumn::make('code')
                ->toggleable()
                ->label('Code')
                ->sortable()
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
                ->url(function (Language $record) {
                    if ($record->code === 'en' || strcasecmp($record->name, 'English') === 0) {
                        return null;
                    }

                    return route('admin.languages.translations', ['code' => $record->code]);
                }, shouldOpenInNewTab: false)
                ->visible(fn (Language $record) => ! ($record->code === 'en' || strcasecmp($record->name, 'English') === 0)),

            Action::make('edit')
                ->label(t('edit'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center rounded-md',
                ])
                ->action(fn (Language $record) => $this->dispatch('editLanguage', languageCode: $record->code))
                ->visible(fn (Language $record) => ! ($record->code === 'en' || strcasecmp($record->name, 'English') === 0)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->action(fn (Language $record) => $this->dispatch('confirmDelete', languageId: $record->id))
                ->visible(fn (Language $record) => ! ($record->code === 'en' || strcasecmp($record->name, 'English') === 0)),
        ];
    }

    #[On('language-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
