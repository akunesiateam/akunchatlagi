<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\Invoice\Invoice;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class InvoicesFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Invoice::query()
            ->selectRaw('invoices.*, (SELECT COUNT(*) FROM invoices i2 WHERE i2.id <= invoices.id ) as row_num')
            ->with(['items', 'tenant', 'taxes'])
            ->withSum('items as total_amount', 'amount');
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('invoice_number')
                ->label('Invoice #')
                ->default('INV-DRAFT')
                ->searchable()
                ->sortable()
                ->toggleable(),

            TextColumn::make('tenant.company_name')
                ->label(t('tenant'))
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('status')
                ->label('Status')
                ->sortable()
                ->toggleable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    if ($state === 'paid') {
                        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-success-100 text-success-800">
                        <svg class="mr-1.5 h-2 w-2 text-success-400" fill="currentColor" viewBox="0 0 8 8">
                            <circle cx="4" cy="4" r="3" />
                        </svg>'
                            .t('paid').
                            '</span>';
                    }

                    if ($state === 'new') {
                        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-warning-100 text-warning-800">'
                            .t('unpaid').
                            '</span>';
                    }

                    return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800/50 text-gray-800 dark:text-gray-300">
                    <span class="h-1.5 w-1.5 bg-gray-400 rounded-full mr-1.5 inline-block"></span>'
                        .e(ucfirst($state)).
                        '</span>';
                }),

            TextColumn::make('total_amount')
                ->label(t('amount'))
                ->sortable()
                ->toggleable()
                ->formatStateUsing(function ($record) {
                    return $record->formatAmount($record->total_amount);
                }),

            TextColumn::make('formatted_amount')
                ->label('Total (With Tax)')
                ->toggleable()
                ->default('-')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return $this->totalAmount($record);
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
                    if (! checkPermission('admin.invoices.view')) {
                        return '<span class="text-gray-400 text-xs">No Access</span>';
                    }

                    $url = route('admin.invoices.show', [$record->id]);

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

    public function totalAmount($invoice)
    {
        $subtotal = $invoice->subTotal();
        $taxDetails = $invoice->getTaxDetails();

        $taxAmount = 0;

        // Calculate actual tax amount if needed
        foreach ($taxDetails as $tax) {
            $amount = $tax['amount'];
            if ($amount <= 0 && $tax['rate'] > 0) {
                $amount = $subtotal * ($tax['rate'] / 100);
            }
            $taxAmount += $amount;
        }

        $fee = $invoice->fee ?: 0;
        $calculatedTotal = $subtotal + $taxAmount + $fee;

        // Use calculated total if different from invoice total
        if (abs($calculatedTotal - $invoice->total()) > 0.01) {
            $totalDisplay = $invoice->formatAmount($calculatedTotal);
        } else {
            $totalDisplay = $invoice->formattedTotal();
        }

        return $totalDisplay;
    }

    #[On('invoices-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
