<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\CustomField;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class CustomFieldFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): Builder
    {
        $tenantId = tenant_id();

        return CustomField::query()
            ->selectRaw('custom_fields.*, (SELECT COUNT(*) FROM custom_fields i2 WHERE i2.id <= custom_fields.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('tenant_id', $tenantId);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label(t('SR.NO'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('field_label')
                ->label(t('custom_field_name'))
                ->sortable()
                ->searchable()
                ->toggleable(),

            TextColumn::make('field_name')
                ->label(t('Field Name'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'text' => 'gray',
                    'textarea' => 'info',
                    'select' => 'warning',
                    'radio' => 'success',
                    'checkbox' => 'primary',
                    'date' => 'secondary',
                    'email' => 'danger',
                    'number' => 'indigo',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => ucfirst($state)),

            ToggleColumn::make('is_active')
                ->label(t('active'))
                ->toggleable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.custom_fields.edit')) {
                        return;
                    }

                    $record->is_active = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = t('custom_field_updated_successfully');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);

                    Cache::forget('contacts_table_custom_fields'.tenant_id());
                }),

            TextColumn::make('field_type_label')
                ->label(t('custom_field_type'))
                ->toggleable()

                ->searchable()
                ->sortable(),

            ToggleColumn::make('is_required')
                ->label(t('custom_field_required'))
                ->toggleable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.custom_fields.edit')) {
                        return;
                    }

                    $record->is_required = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = t('custom_field_updated_successfully');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                }),

            ToggleColumn::make('show_on_table')
                ->label(t('custom_field_show_on_table'))
                ->toggleable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.custom_fields.edit')) {
                        return;
                    }

                    $record->show_on_table = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = t('custom_field_updated_successfully');

                    Cache::forget('contacts_table_custom_fields'.tenant_id());

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                }),

            TextColumn::make('created_at')
                ->label(t('created_at'))
                ->toggleable()
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
            ActionGroup::make([

                Action::make('edit')
                    ->label('Edit')
                    ->url(fn (CustomField $record) => route('tenant.custom-fields.edit', ['subdomain' => tenant_subdomain(), 'customFieldId' => $record->id]))
                    ->hidden(fn () => ! checkPermission('tenant.custom_field.edit')),

                Action::make('delete')
                    ->label('Delete')
                    ->action(fn (CustomField $record) => $this->dispatch('confirmDelete', fieldId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.custom_field.delete')),
            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    #[On('custom-field-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
