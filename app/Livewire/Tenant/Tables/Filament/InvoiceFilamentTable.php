<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Invoice\Invoice;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class InvoiceFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): Builder
    {
        $tenantId = tenant_id();

        return Invoice::query()
            ->selectRaw('invoices.*, (SELECT COUNT(*) FROM invoices i2 WHERE i2.id <= invoices.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('tenant_id', $tenantId)
            ->with(['items', 'tenant', 'taxes'])
            ->withSum('items as total_amount', 'amount');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label(t('SR.NO'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('invoice_number')
                ->label('Invoice #')
                ->toggleable()
                ->searchable()
                ->sortable()
                ->default(format_draft_invoice_number())
                ->formatStateUsing(function ($record) {
                    return $record->invoice_number ?? format_draft_invoice_number();
                }),

            TextColumn::make('created_at')
                ->label('Date')
                ->toggleable()
                ->date('M j, Y')
                ->sortable()
                ->tooltip(function (TextColumn $column): ?string {
                    return format_date_time($column->getState());
                }),

            TextColumn::make('title')
                ->label(t('title'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->description(function ($record) {
                    return $record->description ? truncate_text($record->description, 30) : null;
                }),

            TextColumn::make('status')
                ->label(t('status'))
                ->html() // Enable raw HTML rendering
                ->toggleable()
                ->sortable()
                ->formatStateUsing(function (string $state) {

                    $bg = match ($state) {
                        'paid' => 'bg-success-100 dark:bg-success-900/50 text-success-800 dark:text-success-400',
                        'new' => 'bg-warning-100 dark:bg-warning-900/50 text-warning-800 dark:text-warning-400',
                        default => 'bg-gray-100 dark:bg-gray-800/50 text-gray-800 dark:text-gray-300',
                    };

                    $dot = match ($state) {
                        'paid' => 'bg-success-500',
                        'new' => 'bg-warning-500',
                        default => 'bg-gray-400',
                    };

                    // Display 'unpaid' if state is 'new', otherwise show the actual state
                    $label = $state === 'new' ? 'unpaid' : $state;

                    return <<<HTML
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$bg}">
                <span class="h-1.5 w-1.5 {$dot} rounded-full mr-1.5 inline-block"></span>
                {$label}
            </span>
        HTML;
                }),

            TextColumn::make('total_amount')
                ->label('Total')
                ->toggleable()
                ->money(get_base_currency()->code)
                ->sortable(),

            TextColumn::make('total_with_tax')
                ->label('Total (With Tax)')
                ->toggleable()
                ->sortable(false)
                ->getStateUsing(fn ($record) => $this->totalAmount($record)),
            TextColumn::make('view_invoice')
                ->label('View Details')
                ->default('View Details')
                ->html()
                ->formatStateUsing(function () {
                    return <<<'HTML'
            <span class="inline-flex items-center justify-center px-3 py-1 text-sm border border-info-300 rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-info-100 text-info-700 hover:bg-info-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-info-300 dark:bg-slate-700 dark:border-slate-500 dark:text-info-400 dark:hover:border-info-600 dark:hover:bg-info-600 dark:hover:text-white dark:focus:ring-offset-slate-800 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 mr-1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                View Details
            </span>
        HTML;
                })
                ->action(
                    Action::make('view_invoice_action')
                        ->visible(fn () => checkPermission('tenant.invoices.show'))
                        ->action(function (Invoice $record, $livewire) {
                            // ✅ Dispatch Livewire event
                            $livewire->dispatch('viewInvoice', invoiceId: $record->id);
                        })
                )
                ->sortable(false)
                ->searchable(false)
                ->toggleable(),

        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(t('status'))
                ->options([
                    'paid' => 'Paid',
                    'new' => 'Unpaid',
                ]),

        ];
    }

    public function totalAmount($invoice)
    {
        // Ensure we calculate and display the correct total with tax

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

    #[On('invoice-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
