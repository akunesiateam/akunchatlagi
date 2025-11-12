<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\TenantCreditBalance;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class CreditFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return TenantCreditBalance::query();
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('id')
                ->label(t('id'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('customer_name')
                ->label('Customer')
                ->toggleable()
                ->default('N/A')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    $user = getUserByTenantId($record->tenant_id);

                    return $user->firstname.' '.$user->lastname;
                }),

            TextColumn::make('balance')
                ->label('Balance')
                ->toggleable()
                ->sortable()
                ->searchable()
                ->default('-')
                ->formatStateUsing(function ($state, $record) {
                    $subtotal = $record->balance;

                    return $subtotal ? get_base_currency()->format($subtotal) : '-';
                }),

            TextColumn::make('updated_at')
                ->label('Updated at')
                ->sortable()
                ->toggleable()
                ->dateTime()
                ->since()
                ->tooltip(function ($record) {
                    return format_date_time($record->updated_at);
                }),
            TextColumn::make('view_details')
                ->label('View Details')
                ->default('View Details')
                ->html()
                ->formatStateUsing(function ($state, $record) {

                    $url = route('admin.credit-management.details', [$record->id]);

                    return <<<HTML
            <a href="{$url}"
               class="inline-flex items-center justify-center px-3 py-1 text-sm border border-info-300 rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-info-100 text-info-700 hover:bg-info-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-info-300 dark:bg-slate-700 dark:border-slate-500 dark:text-info-400 dark:hover:border-info-600 dark:hover:bg-info-600 dark:hover:text-white dark:focus:ring-offset-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 mr-1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                View Details
            </a>
        HTML;
                })
                ->toggleable()
                ->sortable(false)
                ->searchable(false),

        ];
    }

    #[On('credit-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
