<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class PageFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Page::query();
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('id')
                ->label(t('id'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('title')
                ->label('Title')
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('slug')
                ->label('Slug')
                ->sortable()
                ->toggleable()
                ->searchable(),

            ToggleColumn::make('status')
                ->label(t('active'))
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.canned_reply.edit')) {
                        return;
                    }

                    $record->status = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = $record->status
                        ? t('page_active')
                        : t('page_deactive');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                    Cache::forget('menu_items');
                }),

            TextColumn::make('order')
                ->label('Order')
                ->sortable()
                ->toggleable()
                ->searchable(),

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
                ->hidden(fn () => ! checkPermission('admin.pages.edit'))
                ->action(fn (Page $record) => $this->dispatch('editPage', pageId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->hidden(fn () => ! checkPermission('admin.pages.delete'))
                ->action(fn (Page $record) => $this->dispatch('confirmDelete', pageId: $record->id)),
        ];
    }

    #[On('page-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
