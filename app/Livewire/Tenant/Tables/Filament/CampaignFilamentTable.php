<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\Campaign;
use App\Models\Tenant\Contact;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class CampaignFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    /**
     * Set default sorting to created_at in descending order
     */
    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): Builder
    {
        $tenantId = tenant_id();
        $query = Campaign::query()
            ->select([
                'campaigns.*',
                'whatsapp_templates.template_name',
                DB::raw('(SELECT COUNT(*) FROM campaign_details
        WHERE campaign_details.campaign_id = campaigns.id
        AND campaign_details.tenant_id = '.$tenantId.'
        AND (message_status = "delivered" OR message_status = "read")) as delivered'),
                DB::raw('(SELECT COUNT(*) FROM campaign_details
        WHERE campaign_details.campaign_id = campaigns.id
        AND campaign_details.tenant_id = '.$tenantId.'
        AND message_status = "read") as read_by'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY campaigns.created_at DESC) as row_num'),
                DB::raw('(SELECT COUNT(*) FROM campaign_details
        WHERE campaign_details.campaign_id = campaigns.id
        AND campaign_details.tenant_id = '.$tenantId.') as total_details'),
                DB::raw('(SELECT COUNT(*) FROM campaign_details
        WHERE campaign_details.campaign_id = campaigns.id
        AND campaign_details.tenant_id = '.$tenantId.'
        AND status = 1) as pending_count'),
                DB::raw('(SELECT COUNT(*) FROM campaign_details
        WHERE campaign_details.campaign_id = campaigns.id
        AND campaign_details.tenant_id = '.$tenantId.'
        AND status = 1) as in_queue_count'),
                DB::raw('(SELECT COUNT(*) FROM campaign_details
        WHERE campaign_details.campaign_id = campaigns.id
        AND campaign_details.tenant_id = '.$tenantId.'
        AND message_status = "sent") as executed_count'),
                DB::raw('(SELECT COUNT(*) FROM campaign_details
        WHERE campaign_details.campaign_id = campaigns.id
        AND campaign_details.tenant_id = '.$tenantId.'
        AND message_status = "failed") as failed_count'),
            ])
            ->leftJoin('whatsapp_templates', function ($join) use ($tenantId) {
                $join->on('campaigns.template_id', '=', 'whatsapp_templates.template_id')
                    ->where('whatsapp_templates.tenant_id', '=', $tenantId);
            })
            ->where('campaigns.tenant_id', '=', tenant_id());

        if (checkPermission('tenant.contact.view')) {
            return $query;
        } elseif (checkPermission('tenant.contact.view_own')) {
            $user = Auth::user();

            // Ensure this only applies to tenant staff
            if ($user->user_type === 'tenant' && $user->tenant_id === tenant_id() && $user->is_admin === false) {
                $staffId = $user->id;
                $tenantSubdomain = tenant_subdomain_by_tenant_id($user->tenant_id);
                $contactTable = Contact::fromTenant($tenantSubdomain)->getModel()->getTable();

                return $query->whereExists(function ($subquery) use ($staffId, $contactTable) {
                    $subquery->select(DB::raw(1))
                        ->from('campaign_details')
                        ->join($contactTable, 'campaign_details.rel_id', '=', $contactTable.'.id')
                        ->whereColumn('campaign_details.campaign_id', 'campaigns.id')
                        ->where($contactTable.'.assigned_id', $staffId);
                });
            }
        }

        // Default return if no conditions match
        return $query;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label(t('SR.NO'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('name')
                ->label(t('campaign_name'))
                ->toggleable()
                ->searchable()
                ->html() // <-- Enable HTML output
                ->formatStateUsing(function ($state, $record) {
                    return '<div class="group relative inline-block ">
                <a class="dark:text-gray-200 text-primary-600 dark:hover:text-primary-400" href="'.tenant_route('tenant.campaigns.details', ['campaignId' => $record->id]).'">'.$record->name.'</a>
               </div>';
                })
                ->sortable(),

            TextColumn::make('template_name')
                ->label(t('template'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->placeholder('N/A'),

            TextColumn::make('rel_type')
                ->label(t('relation_type'))
                ->html() // <-- Enable HTML output
                ->formatStateUsing(function ($state) {
                    $class = $state === 'lead'
                        ? 'bg-primary-100 text-primary-800 dark:text-primary-400 dark:bg-primary-900/20'
                        : 'bg-success-100 text-success-800 dark:text-success-400 dark:bg-success-900/20';

                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium '.$class.'">'.ucfirst($state).'</span>';
                })
                ->sortable()
                ->toggleable(),

            TextColumn::make('status_badge')
                ->label(t('status'))
                ->toggleable()
                ->default('N/A')
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    $total = $record->total_details ?? 0;
                    $executed = $record->executed_count ?? 0;
                    $failed = $record->failed_count ?? 0;
                    $pending = $record->pending_count ?? 0;
                    $inQueue = $record->in_queue_count ?? 0;
                    $isSent = $record->is_sent ?? 0;

                    // Case 1: No data
                    if ($total == 0) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">'
                            .t('no_data').
                            '</span>';
                    }

                    // Case 2: No items left in queue
                    if ($inQueue == 0) {
                        if ($failed == $total) {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900/20 dark:text-danger-400">'
                                .t('failed').
                                '</span>';
                        } else {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400">'
                                .t('executed').
                                '</span>';
                        }
                    }

                    // Case 3: Campaign still in queue
                    if ($inQueue > 0) {
                        if (($executed + $pending) == 0) {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-400">'
                                .t('pending').
                                '</span>';
                        } else {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info-100 text-info-800 dark:bg-info-900/20 dark:text-info-400">'
                                .t('in_process').
                                '</span>';
                        }
                    }

                    // Case 4: Processing done
                    if ($executed > 0) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400">'
                            .t('executed').
                            '</span>';
                    }

                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900/20 dark:text-danger-400">'
                        .t('failed').
                        '</span>';
                }),

            TextColumn::make('sending_count')
                ->label(t('total'))
                ->html()
                ->formatStateUsing(fn ($state) => "<span class='text-sm text-center mx-2'>{$state}</span>")
                ->sortable()
                ->toggleable(),

            TextColumn::make('delivered')
                ->label(t('delivered_to'))
                ->html()
                ->formatStateUsing(fn ($state) => "<span class='text-sm text-center mx-5'>{$state}</span>")
                ->sortable()
                ->toggleable(),

            TextColumn::make('read_by')
                ->label(t('ready_by'))
                ->html()
                ->formatStateUsing(fn ($state) => "<span class='text-sm text-center mx-4'>{$state}</span>")
                ->sortable()
                ->toggleable(),

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

    protected function getTableFilters(): array
    {
        return [

            SelectFilter::make('campaigns.template_id') // <-- fully qualified
                ->label(t('template'))
                ->options(function () {
                    $tenantId = tenant_id();

                    return \App\Models\Tenant\WhatsappTemplate::where('tenant_id', $tenantId)
                        ->pluck('template_name', 'template_id')
                        ->toArray();
                })
                ->searchable()
                ->preload(),

            SelectFilter::make('rel_type')
                ->label(t('relation_type'))
                ->options([
                    'lead' => 'lead',
                    'customer' => 'customer',
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
                            fn (Builder $query, $date): Builder => $query->whereDate('campaigns.created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('campaigns.created_at', '<=', $date),
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
                    ->url(fn (Campaign $record) => tenant_route('tenant.campaigns.details', ['campaignId' => $record->id]))
                    ->hidden(fn () => ! checkPermission('tenant.campaigns.show_campaign')),

                Action::make('edit')
                    ->label('Edit')
                    ->url(fn (Campaign $record) => tenant_route('tenant.campaign.edit', ['id' => $record->id]))
                    ->hidden(fn () => ! checkPermission('tenant.campaigns.edit')),

                Action::make('delete')
                    ->label('Delete')
                    ->action(fn (Campaign $record) => $this->dispatch('confirmDelete', campaignId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.campaigns.delete')),
            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    #[On('campaign-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
