<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\Currency;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\CurrencyCache;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Livewire\Attributes\On;

class CurrencyFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Currency::query();
    }

    protected function getTableColumns(): array
    {
        return [

            TextColumn::make('id')
                ->label(t('id'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('name')
                ->label(t('name'))
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('symbol')
                ->label('Symbol')
                ->sortable()
                ->toggleable()
                ->searchable(),

            ToggleColumn::make('is_default')
                ->label('Base Currency')
                ->toggleable()
                ->sortable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (checkPermission('admin.currency.edit')) {
                        $existingCurrency = Currency::query()->where('is_default', '1')->firstOrFail();

                        $plans = Plan::where('currency_id', $existingCurrency->id)->get();

                        if ($plans->isNotEmpty()) {
                            // Check if any of these plans have a subscription
                            $planIds = $plans->pluck('id');

                            $hasSubscription = Subscription::whereIn('plan_id', $planIds)->exists();

                            if ($hasSubscription) {

                                $record->is_default = 0;
                                $record->save();

                                $this->notify([
                                    'type' => 'danger',
                                    'message' => t('cannot_change_base_currency_subscription_exists'),
                                ]);

                                // Re-enable the switch by refreshing the table
                                $this->dispatch('currency-table-refresh');

                                return;
                            }
                        }

                        // If setting to true/1/on
                        if ($state === 'true') {
                            Currency::query()->update(['is_default' => 0]);

                            $currency = Currency::query()->where('id', $record->id)->firstOrFail();
                            $currency->is_default = $state ? 1 : 0;
                            $currency->save();

                            Plan::where('currency_id', $existingCurrency->id)
                                ->update(['currency_id' => $currency->id]);

                            $this->notify([
                                'message' => t('update_base_currency'),
                                'type' => 'success',
                            ]);

                            $this->dispatch('currency-table-refresh');
                        } else {
                            // Don't allow turning off the base currency without selecting another one
                            $currentDefaults = Currency::query()->where('is_default', 1)->count();

                            if ($currentDefaults <= 1) {
                                $this->notify([
                                    'message' => t('must_one_base_currency'),
                                    'type' => 'danger',
                                ]);

                                // Re-enable the switch by refreshing the table
                                $this->dispatch('currency-table-refresh');

                                return;
                            }
                        }
                        CurrencyCache::clearCache();
                    } else {
                        $this->notify([
                            'message' => t('no_permission_to_perform_action'),
                            'type' => 'warning',
                        ]);
                    }
                }),

        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('edit')
                ->label(t('edit'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600  shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center rounded-md',
                ])
                ->hidden(fn () => ! checkPermission('admin.currency.edit'))
                ->action(fn (Currency $record) => $this->dispatch('editCurrency', id: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600  shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->hidden(fn () => ! checkPermission('admin.currency.delete'))
                ->action(fn (Currency $record) => $this->dispatch('confirmDelete', id: $record->id)),
        ];
    }

    #[On('currency-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
