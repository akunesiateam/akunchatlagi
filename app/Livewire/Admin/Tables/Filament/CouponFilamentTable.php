<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\Coupon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class CouponFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): Builder
    {
        return Coupon::query();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('code')
                ->label(t('code'))
                ->searchable()
                ->toggleable()
                ->sortable()
                ->copyable()
                ->weight('medium'),

            TextColumn::make('name')
                ->label(t('name'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->limit(30),

            TextColumn::make('type')
                ->label(t('type'))
                ->toggleable()
                ->sortable()
                ->html()
                ->formatStateUsing(function ($state) {
                    if ($state === 'percentage') {
                        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-info-100 text-info-800">'
                            .t('percentage').
                            '</span>';
                    }

                    if ($state === 'fixed_amount') {
                        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-success-100 text-success-800">'
                            .t('fixed_amount').
                            '</span>';
                    }

                    return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800/50 text-gray-800 dark:text-gray-300">
						<span class="h-1.5 w-1.5 bg-gray-400 rounded-full mr-1.5 inline-block"></span>'
                        .e(ucfirst($state)).
                        '</span>';
                }),

            TextColumn::make('value')
                ->label(t('value'))
                ->toggleable()
                ->formatStateUsing(function (Coupon $record): string {
                    return $record->type === 'percentage'
                        ? $record->value.'%'
                        : get_base_currency()->format($record->value);
                })
                ->sortable(),

            TextColumn::make('is_active')
                ->label(t('status'))
                ->sortable()
                ->toggleable()
                ->toggleable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state) {
                    if ($state) {
                        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-success-100 text-success-800">'
                            .t('active').
                            '</span>';
                    }

                    return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-danger-100 text-danger-800">'
                        .t('inactive').
                        '</span>';
                }),

            TextColumn::make('usage_count')
                ->label(t('usage'))
                ->toggleable()
                ->formatStateUsing(function (Coupon $record): string {
                    $usageText = $record->usage_limit
                        ? $record->usage_count.' / '.$record->usage_limit
                        : $record->usage_count.' / ∞';

                    return "<span class='text-blue-600 hover:text-blue-800 font-medium'>{$usageText}</span>";
                })
                ->html()
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->orderBy('usage_count', $direction);
                }),

            TextColumn::make('expires_at')
                ->label(t('expires'))
                ->toggleable()
                ->default(false)
                ->html()
                ->formatStateUsing(fn (?string $state): string => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d') : '-')
                ->sortable(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('type')
                ->label(t('type'))
                ->options([
                    'percentage' => t('percentage'),
                    'fixed_amount' => t('fixed_amount'),
                ]),

            SelectFilter::make('is_active')
                ->label(t('status'))
                ->options([
                    1 => t('active'),
                    0 => t('inactive'),
                ]),

            Filter::make('expired')
                ->label(t('expired'))
                ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now()))
                ->toggle(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('view')
                    ->label(t('view'))
                    ->action(fn (Coupon $record) => $this->dispatch('showUsageDetails', couponId: $record->id)),

                Action::make('toggle_status')
                    ->label(fn (Coupon $record): string => $record->is_active ? t('deactivate') : t('activate'))
                    ->action(fn (Coupon $record) => $this->dispatch('toggleStatus', id: $record->id)),

                Action::make('edit')
                    ->label(t('edit'))
                    ->url(fn (Coupon $record): string => route('admin.coupons.edit', ['id' => $record->id])),

                Action::make('delete')
                    ->label(t('delete'))
                    ->action(fn (Coupon $record) => $this->dispatch('confirmDelete', id: $record->id)),
            ])->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    #[On('coupon-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
