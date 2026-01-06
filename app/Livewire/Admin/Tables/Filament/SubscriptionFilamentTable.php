<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\Subscription;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class SubscriptionFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Subscription::query()->with(['plan', 'tenant']);
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('id')
                ->label(t('id'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('tenant.company_name')
                ->label('Tenant')
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('plan.name')
                ->label('Plan')
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('status')
                ->label('Status')
                ->sortable()
                ->searchable()
                ->toggleable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    if ($record->isActive()) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 dark:bg-success-900/50 text-success-800 dark:text-success-400 mr-2">'
                            .t('active').'</span>';
                    }

                    if ($record->isTrial()) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-400 mr-2">'
                            .t('trial').'</span>';
                    }

                    if ($record->isCancelled()) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 dark:bg-warning-900/50 text-warning-800 dark:text-warning-400 mr-2">'
                            .t('cancelled').'</span>';
                    }

                    if ($record->isEnded()) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-400 mr-2">'
                            .t('ended').'</span>';
                    }

                    if ($record->isPause()) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 dark:bg-warning-900/50 text-warning-800 dark:text-warning-400 mr-2">'
                            .t('paused').'</span>';
                    }

                    if ($record->isNew()) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info-100 dark:bg-info-900/50 text-info-800 dark:text-info-400 mr-2">'
                            .t('new').'</span>';
                    }

                    if ($record->isTerminated()) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 dark:bg-danger-900/50 text-danger-800 dark:text-danger-400 mr-2">'
                            .t('terminated').'</span>';
                    }

                    return '<span class="text-gray-400">--</span>';
                }),

            TextColumn::make('current_period_ends_at')
                ->label('Period Ends At')
                ->sortable()
                ->toggleable()
                ->dateTime()
                ->since()
                ->tooltip(function ($record) {
                    return format_date_time($record->current_period_ends_at);
                }),
            TextColumn::make('view_details')
                ->label('View Details')
                ->default('View Details')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    if (! checkPermission('admin.subscription.view')) {
                        return '<span class="text-gray-400 text-xs">No Access</span>';
                    }

                    $url = route('admin.subscriptions.show', [$record->id]);

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

    #[On('subscription-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
