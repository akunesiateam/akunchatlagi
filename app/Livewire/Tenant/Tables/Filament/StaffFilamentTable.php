<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class StaffFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = tenant_id();

        return User::query()
            ->selectRaw('users.*, (SELECT COUNT(*) FROM users i2 WHERE i2.id <= users.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('id', '!=', auth()->id())
            ->where('tenant_id', $tenantId);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label('SR.NO')
                ->sortable()
                ->toggleable(),
            TextColumn::make('name')
                ->label('Name')
                ->html()
                ->toggleable()
                ->formatStateUsing(function ($state, User $record) {
                    $profile_img = $record->avatar && Storage::disk('public')->exists($record->avatar)
                        ? asset('storage/'.$record->avatar)
                        : asset('img/user-placeholder.jpg');

                    $fullName = truncate_text($record->firstname.' '.$record->lastname, 50);

                    $output = '<div class="group relative inline-block ">
            <div class="flex items-center gap-3 w-auto min-w-0 max-w-full">
                <img src="'.$profile_img.'" class="inline-block object-cover h-7 w-7 rounded-full">
                <p class="dark:text-gray-200 text-primary-600 dark:hover:text-primary-400 text-sm break-words truncate">'.$fullName.'</p>
            </div>';
                    $output .= '</div>';

                    return $output;
                }),
            TextColumn::make('phone')
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('email')
                ->sortable()
                ->searchable()
                ->toggleable(),
            ToggleColumn::make('active')
                ->label(t('active'))
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('tenant.staff.edit')) {
                        return;
                    }
                    $record->active = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = t('status_updated_successfully');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                }),

            TextColumn::make('created_at')
                ->label('Created At')
                ->sortable()
                ->toggleable()
                ->dateTime()
                ->since()
                ->tooltip(function ($record) {
                    return format_date_time($record->created_at);
                }),

        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('view')
                    ->label('View')
                    ->action(fn (User $record) => $this->dispatch('viewStaff', staffId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.staff.view')),

                Action::make('edit')
                    ->label('Edit')
                    ->action(fn (User $record) => $this->dispatch('editStaff', staffId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.staff.edit')),

                Action::make('delete')
                    ->label('Delete')
                    ->action(fn (User $record) => $this->dispatch('confirmDelete', staffId: $record->id))
                    ->hidden(fn () => ! checkPermission('tenant.staff.delete')),
            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    #[On('staff-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
