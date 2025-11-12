<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\Transaction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class TransactionFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Transaction::query()
            ->with(['invoice', 'currency'])
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 0
                    WHEN status = 'success' THEN 1
                    WHEN status = 'failed' THEN 2
                    ELSE 3
                END
            ");
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
                    if (! $record->invoice?->tenant_id) {
                        return 'N/A';
                    }

                    $user = getUserByTenantId($record->invoice->tenant_id);

                    if (! $user) {
                        return 'N/A';
                    }

                    return ($user->firstname ?? '').' '.($user->lastname ?? '');
                }),

            TextColumn::make('type')
                ->label('Payment Gateway')
                ->sortable()
                ->toggleable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state) {
                    $color = match ($state) {
                        'stripe' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
                        'offline' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                    };

                    return '<span class="'.$color.' px-2.5 py-0.5 rounded-full text-xs font-medium">'.ucfirst($state).'</span>';
                }),

            TextColumn::make('status')
                ->label('Status')
                ->sortable()
                ->toggleable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state) {
                    return match ($state) {
                        Transaction::STATUS_SUCCESS => '<span class="bg-success-100 text-success-800 dark:text-success-400 dark:bg-success-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">Success</span>',
                        Transaction::STATUS_FAILED => '<span class="bg-danger-100 text-danger-800 dark:text-danger-400 dark:bg-danger-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">Failed</span>',
                        Transaction::STATUS_PENDING => '<span class="bg-warning-100 text-warning-800 dark:text-warning-400 dark:bg-warning-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">Pending</span>',
                        default => '<span class="bg-gray-100 text-gray-800 dark:text-gray-400 dark:bg-gray-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">'.ucfirst($state).'</span>',
                    };
                }),

            TextColumn::make('amount')
                ->label('Amount')
                ->searchable()
                ->toggleable()
                ->default('-')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    $subtotal = $record->invoice?->subTotal();

                    return $subtotal ? get_base_currency()->format($subtotal) : '-';
                }),

            TextColumn::make('amount_with_tax')
                ->label('Amount (With Tax)')
                ->toggleable()
                ->default('N/A')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return $this->getInvoiceTotalWithTax($record);
                }),

            TextColumn::make('created_at')
                ->label(t('created_at'))
                ->sortable()
                ->toggleable()
                ->dateTime()
                ->since()
                ->tooltip(function ($record) {
                    return format_date_time($record->created_at);
                }),
            TextColumn::make('view_details')
                ->label('View Details')
                ->default('View Details')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    if (! checkPermission('admin.transactions.view')) {
                        return '<span class="text-gray-400 text-xs">No Access</span>';
                    }

                    $url = route('admin.transactions.show', [$record->id]);

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

    protected function getTableFilters(): array
    {
        return [

            SelectFilter::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'success' => 'Success',
                    'failed' => 'Failed',
                ]),

            Filter::make('created_at')
                ->form([
                    DatePicker::make('created_from')
                        ->label(t('created_from'))
                        ->maxDate(now()),
                    DatePicker::make('created_until')
                        ->label(t('created_until'))
                        ->maxDate(now()),
                ])
                ->indicateUsing(function (array $data): ?string {
                    if (! $data['created_from'] && ! $data['created_until']) {
                        return null;
                    }

                    $indicator = [];

                    if ($data['created_from']) {
                        $indicator[] = t('created_from').': '.$data['created_from'];
                    }

                    if ($data['created_until']) {
                        $indicator[] = t('created_until').': '.$data['created_until'];
                    }

                    return implode(', ', $indicator);
                })
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('transactions.created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('transactions.created_at', '<=', $date),
                        );
                }),

        ];
    }

    public function getInvoiceTotalWithTax($transaction): string
    {
        $invoice = $transaction->invoice;

        if (! $invoice) {
            return get_base_currency()->format($transaction->amount);
        }

        $subtotal = $invoice->subTotal();
        $taxDetails = $invoice->getTaxDetails();

        $taxAmount = 0;

        foreach ($taxDetails as $tax) {
            $amount = $tax['amount'];
            if ($amount <= 0 && $tax['rate'] > 0) {
                $amount = $subtotal * ($tax['rate'] / 100);
            }
            $taxAmount += $amount;
        }

        $fee = $invoice->fee ?: 0;
        $calculatedTotal = $subtotal + $taxAmount + $fee;

        if (abs($calculatedTotal - $invoice->total()) > 0.01) {
            return get_base_currency()->format($calculatedTotal);
        }

        return $invoice->formattedTotal(); // This already includes tax if precomputed
    }

    #[On('transaction-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
