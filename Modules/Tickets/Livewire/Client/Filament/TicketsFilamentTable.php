<?php

namespace Modules\Tickets\Livewire\Client\Filament;

use App\Livewire\Admin\Tables\Filament\BaseFilamentTable;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Modules\Tickets\Models\Department;
use Modules\Tickets\Models\Ticket;

class TicketsFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Ticket::query()
            ->select([
                'tickets.*',
                'departments.name as department_name',
            ])
            ->leftJoin('departments', 'tickets.department_id', '=', 'departments.id')
            ->where('tickets.tenant_id', Auth::user()->tenant_id)
            ->with(['assignedUsers', 'department'])
            ->withCount('replies');
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('ticket_id')
                ->label('Id')
                ->sortable()
                ->toggleable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return '<a href="'.tenant_route('tenant.tickets.show', ['ticket' => $record->id]).'" class="text-primary-600 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400">'
                        .$record->ticket_id.
                        '</a>';
                }),

            TextColumn::make('subject')
                ->label(t('subject'))
                ->toggleable()
                ->searchable()
                ->sortable(),

            TextColumn::make('department_id')
                ->label(t('department'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->default('N/A')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return $record->department->name ?? 'N/A';
                }),

            TextColumn::make('priority')
                ->label(t('priority'))
                ->sortable()
                ->toggleable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return view('Tickets::components.ticket-priority-badge', [
                        'priority' => $record->priority,
                    ])->render();
                }),

            TextColumn::make('status')
                ->label(t('status'))
                ->sortable()
                ->toggleable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return view('Tickets::components.ticket-status-badge', [
                        'status' => $record->status,
                    ])->render();
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

        ];
    }

    protected function getTableFilters(): array
    {
        static $departments = null;
        if ($departments === null) {
            // Cache departments for the request to avoid duplicate queries
            $departments = Department::where('status', true)->get(['id', 'name'])->map(function ($dept) {
                return ['id' => $dept->id, 'name' => $dept->name];
            })->toArray();
        }

        return [
            SelectFilter::make('status')
                ->label('Status')
                ->attribute('tickets.status') // Specify the table
                ->options([
                    'open' => 'Open',
                    'answered' => 'Answered',
                    'on_hold' => 'On Hold',
                    'closed' => 'Closed',
                ]),

            // Priority filter
            SelectFilter::make('priority')
                ->label('Priority')
                ->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'urgent' => 'Urgent',
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
                            fn (Builder $query, $date): Builder => $query->whereDate('tickets.created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('tickets.created_at', '<=', $date),
                        );
                }),

        ];
    }

    protected function getTableActions(): array
    {
        return [

            Action::make('view')
                ->label('')
                ->icon('heroicon-o-eye')
                ->tooltip('View Ticket')
                ->extraAttributes([
                    'class' => 'inline-flex items-center px-2 py-1 text-xs font-medium text-primary-600 bg-primary-100 rounded hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-primary-900 dark:text-primary-200',
                ])
                ->action(fn ($record) => $this->dispatch('viewTicket', $record->id)),

        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-green-600 rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2',
                ])
                ->action(fn () => $this->exportTicketsAsCsv()),

        ];
    }

    public function exportTicketsAsCsv()
    {
        try {
            $tickets = $this->getTicketsForExport();
            $headers = $this->getExportHeaders();

            $filename = 'tickets-export-'.date('Y-m-d-H-i-s').'.csv';

            return response()->streamDownload(function () use ($tickets, $headers) {
                $handle = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                // Write headers
                fputcsv($handle, $headers);

                // Write data
                foreach ($tickets as $contact) {
                    fputcsv($handle, $this->getTicketsRowData($contact));
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            $this->notify([
                'message' => 'Export failed: '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    protected function getTicketsForExport()
    {
        return Ticket::query()
            ->select([
                'tickets.*',
                'departments.name as department_name',
            ])
            ->leftJoin('departments', 'tickets.department_id', '=', 'departments.id')
            ->where('tickets.tenant_id', Auth::user()->tenant_id)
            ->with(['assignedUsers', 'department'])
            ->withCount('replies')->get();
    }

    protected function getExportHeaders()
    {
        $headers = [
            t('id'),
            t('subject'),
            t('department'),
            t('priority'),
            t('status'),
            t('created_at'),

        ];

        return $headers;
    }

    protected function getTicketsRowData($tickets): array
    {
        return [
            $tickets->ticket_id,
            $tickets->subject,
            $tickets->department->name ?? 'N/A',
            $tickets->tenant->company_name ?? t('no_tenant'),
            ucfirst($tickets->priority),
            ucfirst($tickets->status),
            $tickets->created_at->diffForHumans(),
        ];
    }

    #[On('tenant-tickets-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
