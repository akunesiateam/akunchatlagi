<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\CannedReply;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class CannedReplyFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): Builder
    {
        $tenantId = tenant_id();
        $query = CannedReply::query()
            ->selectRaw('*, (SELECT COUNT(*) FROM canned_replies i2 WHERE i2.id <= canned_replies.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('tenant_id', tenant_id());

        // Additional filtering for non-admin users
        if (! auth()->user()->is_admin) {
            $query->where(function ($q) {
                $q->where('is_public', 1)
                    ->orWhere('added_from', auth()->user()->id);
            });
        }

        return $query;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label(t('SR.NO'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('title')
                ->label(t('title'))
                ->searchable()
                ->sortable(),

            TextColumn::make('description')
                ->label(t('description'))
                ->searchable()
                ->sortable()
                ->limit(100)
                ->tooltip(function (TextColumn $column): ?string {
                    $state = $column->getState();

                    return strlen($state) > 100 ? $state : null;
                }),

            ToggleColumn::make('is_public')
                ->label(t('public'))
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.canned_reply.edit')) {
                        return;
                    }

                    $record->is_public = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = $record->is_public
                        ? t('canned_reply_activate')
                        : t('canned_reply_deactivate');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                }),

            TextColumn::make('created_at')
                ->label(t('created_at'))
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
            Action::make('edit')
                ->label(t('edit'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center rounded-md',
                ])
                ->action(fn (CannedReply $record) => $this->dispatch('editCannedPage', cannedId: $record->id))
                ->hidden(fn () => ! checkPermission('tenant.canned_reply.edit')),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->action(fn (CannedReply $record) => $this->dispatch('confirmDelete', cannedId: $record->id))
                ->hidden(fn () => ! checkPermission('tenant.canned_reply.delete')),

        ];
    }

    #[On('canned-reply-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
