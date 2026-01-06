<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\ContactImport;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ImportContactsLogsFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): Builder
    {
        $tenantId = tenant_id();

        return ContactImport::query()
            ->where('tenant_id', $tenantId);
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('id')
                ->label(('ID'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('status')
                ->label('Status')
                ->sortable()
                ->searchable()
                ->formatStateUsing(function (string $state) {
                    $statusColors = [
                        ContactImport::STATUS_PROCESSING => 'yellow',
                        ContactImport::STATUS_COMPLETED => 'green',
                        ContactImport::STATUS_FAILED => 'red',
                    ];

                    $color = $statusColors[$state] ?? 'gray';
                    $statusText = ucfirst($state);

                    return <<<HTML
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{$color}-100 text-{$color}-800 dark:bg-{$color}-900 dark:text-{$color}-200">
                {$statusText}
            </span>
        HTML;
                })
                ->html(),

            TextColumn::make('file_path')
                ->label('File')
                ->toggleable()
                ->sortable()
                ->searchable()
                ->formatStateUsing(function ($state, $record) {
                    $fileName = basename($state);
                    $importId = $record->id;

                    return <<<HTML
            <button
                x-data
                x-on:click="\$wire.dispatch('downloadFile', { importId: {$importId} })"
                class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 hover:underline"
                title="Download File"
            >
                <div class="inline-flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    <span>{$fileName}</span>
                </div>
            </button>
        HTML;
                })
                ->html(),

            TextColumn::make('processed_records') // custom key — no real DB column
                ->label('Progress')
                ->formatStateUsing(function ($state, $record) {
                    if ($record->total_records > 0) {
                        $percentage = ($record->processed_records / $record->total_records) * 100;
                        $colorClass = match ($record->status) {
                            ContactImport::STATUS_COMPLETED => 'bg-green-600',
                            ContactImport::STATUS_FAILED => 'bg-red-600',
                            default => 'bg-blue-600',
                        };

                        return <<<HTML
                                        <div class="flex items-center space-x-2 w-full">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                <div class="h-2 {$colorClass}" style="width: {$percentage}%;" >
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                </div>
                            </div>
                            <span class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {$record->processed_records}/{$record->total_records}
                            </span>
                        </div>
                       HTML;
                    }

                    return '<span class="text-xs text-gray-500">-</span>';
                })
                ->html()
                ->toggleable(),

            TextColumn::make('valid_records')
                ->label('Records')
                ->formatStateUsing(function ($state, $record) {
                    $valid = "<span class='inline-flex items-center px-1.5 py-0.5 rounded text-green-700 bg-green-100 dark:bg-green-900/20 dark:text-green-400'>
                    ✓ {$record->valid_records}
                 </span>";

                    $invalid = $record->invalid_records > 0
                        ? "<span class='inline-flex items-center px-1.5 py-0.5 rounded text-red-700 bg-red-100 dark:bg-red-900/20 dark:text-red-400'>
                   ✗ {$record->invalid_records}
               </span>"
                        : '';

                    $skipped = $record->skipped_records > 0
                        ? "<span class='inline-flex items-center px-1.5 py-0.5 rounded text-yellow-700 bg-yellow-100 dark:bg-yellow-900/20 dark:text-yellow-400'>
                   ⚠ {$record->skipped_records}
               </span>"
                        : '';

                    return <<<HTML
            <div class="flex flex-wrap gap-1 text-xs">
                {$valid}
                {$invalid}
                {$skipped}
            </div>
        HTML;
                })
                ->html()
                ->toggleable(),

            TextColumn::make('created_at')
                ->label(t('created_at'))
                ->toggleable()
                ->dateTime()
                ->sortable()
                ->since()
                ->tooltip(function (TextColumn $column): ?string {
                    return format_date_time($column->getState());
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [

            Action::make('view')
                ->label(t('view'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center',
                ])
                ->action(fn (ContactImport $record) => $this->dispatch('showImportDetails', importId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->action(fn (ContactImport $record) => $this->dispatch('confirmDeleteImport', importId: $record->id)),

        ];
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_records > 0) {
            return round(($this->processed_records / $this->total_records) * 100, 1);
        }

        return 0;
    }

    #[On('import-contact-logs-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
