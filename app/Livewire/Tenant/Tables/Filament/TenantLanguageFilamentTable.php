<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Language;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class TenantLanguageFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = tenant_id();

        return Language::query()
            ->selectRaw('languages.*, (SELECT COUNT(*) FROM languages i2 WHERE i2.id <= languages.id AND i2.tenant_id = ?) as row_num', [$tenantId])
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
            TextColumn::make('code')
                ->label(t('code'))
                ->sortable()
                ->searchable()
                ->toggleable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('translate')
                ->label(t('translate'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-success-600 rounded shadow-sm hover:bg-success-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-success-600',
                ])
                ->action(fn (Language $record) => $this->dispatch('translateLanguage', code: $record->code)),

            Action::make('edit')
                ->label(t('edit'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 rounded shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center',
                ])
                ->action(fn (Language $record) => $this->dispatch('editLanguage', languageCode: $record->code)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 rounded shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->action(fn (Language $record) => $this->dispatch('confirmDelete', languageId: $record->id)),
        ];
    }

    #[On('tenant-language-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
