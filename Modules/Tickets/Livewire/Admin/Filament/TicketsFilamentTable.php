<?php

namespace Modules\Tickets\Livewire\Admin\Filament;

use App\Livewire\Admin\Tables\Filament\BaseFilamentTable;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Modules\Tickets\Events\TicketStatusChanged;
use Modules\Tickets\Models\Ticket;

class TicketsFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = true;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public bool $showBulkActions = false;

    public string $bulkActionType = '';

    public string $bulkActionValue = '';

    public array $selectedTickets = [];

    // Filter properties
    public $selectedDepartment = null;

    public $selectedStatus = null;

    public $selectedPriority = null;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $userId = (string) $user->id;

        $query = Ticket::query()
            ->with([
                'tenant',
                'department',
                'replies' => fn ($q) => $q->latest()->limit(1),
            ])
            ->select('tickets.*');

        if (! $user->is_admin) {
            $query->where(function ($subQuery) use ($userId) {
                // Check in tickets.assignee_id (JSON field)
                $subQuery->where(function ($q) use ($userId) {
                    $q->whereNotNull('tickets.assignee_id')
                        ->where('tickets.assignee_id', '!=', '')
                        ->whereJsonContains('tickets.assignee_id', $userId);
                });

                // Check in department.assignee_id (stringified JSON with integers)
                $subQuery->orWhereHas('department', function ($q) use ($userId) {
                    $pattern = '[[:<:]]'.$userId.'[[:>:]]'; // word boundary

                    $q->whereNotNull('assignee_id')
                        ->where('assignee_id', '!=', '')
                        ->whereRaw('assignee_id REGEXP ?', [
                            '\\['.$userId.'\\]'     // exactly [5]
                                .'|\\['.$userId.','
                                .'|,'.$userId.','
                                .'|,'.$userId.'\\]',
                        ]);
                });
            });
        }

        if ($this->selectedDepartment) {
            $query->where('department_id', $this->selectedDepartment);
        }

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->selectedPriority) {
            $query->where('priority', $this->selectedPriority);
        }

        return $query;
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('ticket_id')
                ->label(t('id'))
                ->sortable()
                ->toggleable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return '<a href="'.route('admin.tickets.show', $record).'" class="font-mono text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300">'
                        .$record->ticket_id.
                        '</a>';
                }),

            TextColumn::make('subject')
                ->label(t('subject'))
                ->toggleable()
                ->searchable()
                ->sortable(),

            TextColumn::make('tenant_id')
                ->label(t('tenant'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->default('N/A')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    return $record->tenant->company_name ?? 'N/A';
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
        return [
            SelectFilter::make('status')
                ->label('Status')
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
            ActionGroup::make([

                Action::make('view')
                    ->label('View')
                    ->url(fn ($record) => route('admin.tickets.show', ['ticket' => $record->id])),

                Action::make('quick-status')
                    ->label('Quick Status Change')
                    ->action(
                        fn (Ticket $record, $livewire) => $livewire->dispatch('quickStatusChange', data: $record)
                    )
                    ->visible(fn ($record) => $record->status !== 'closed'),

                Action::make('delete')
                    ->label('Delete')
                    ->action(fn ($record) => $this->dispatch('confirmDelete', ticketId: $record->id)),

            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('bulk_delete')
                ->label(t('bulk_delete'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 rounded shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->action(function (Collection $records) {
                    $this->dispatch('bulkDelete', [
                        'ids' => $records->pluck('id')->toArray(),
                    ]);
                }),

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
            ->with([
                'tenant',
                'department',
                'replies' => fn ($q) => $q->latest()->limit(1),
            ])
            ->select('tickets.*')->get();
    }

    protected function getExportHeaders()
    {
        $headers = [
            t('id'),
            t('subject'),
            t('tenant'),
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
            $tickets->tenant->company_name ?? t('no_tenant'),
            ucfirst($tickets->priority),
            ucfirst($tickets->status),
            $tickets->created_at->diffForHumans(),
        ];
    }

    #[On('quickStatusChange')]
    public function quickStatusChange(array $data): void
    {
        $id = $data['id'];
        $ticket = Ticket::findOrFail($id);
        $oldStatus = $ticket->status;

        $statusFlow = [
            'open' => 'answered',
            'answered' => 'closed',
            'on_hold' => 'open',
            'closed' => 'open',
        ];

        $newStatus = $statusFlow[$ticket->status] ?? 'open';

        $ticket->update([
            'status' => $newStatus,
            'admin_viewed' => true,
        ]);

        // Dispatch the TicketStatusChanged event
        event(new TicketStatusChanged($ticket, $oldStatus, true));

        $this->notify([
            'type' => 'success',
            'message' => 'Ticket #'.$ticket->ticket_id.' status changed to '.ucfirst($newStatus),
        ]);
    }

    #[On('bulkDelete')]
    public function handleBulkDelete(array $ids): void
    {
        if (! empty($ids) && count($ids) !== 0) {
            $this->dispatch('confirmDelete', $ids['ids']);
        } else {
            $this->notify(['type' => 'danger', 'message' => t('no_tickets_selected')]);
        }
    }

    #[On('admin-tickets-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
