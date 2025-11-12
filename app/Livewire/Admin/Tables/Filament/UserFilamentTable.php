<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class UserFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()
            ->where('id', '!=', auth()->id())
            ->where('user_type', '=', 'admin');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label(t('id'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('firstname')
                ->label(t('name'))
                ->sortable()
                ->toggleable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    $fullname = $record->firstname.' '.$record->lastname;

                    $profileImg = $record->avatar && Storage::disk('public')->exists($record->avatar)
                        ? asset('storage/'.$record->avatar)
                        : asset('img/user-placeholder.jpg');

                    return <<<HTML
                        <div class="group relative inline-block ">
                            <div class="flex items-center gap-3 w-auto min-w-0 max-w-full ">
                                <img src="{$profileImg}" class="inline-block object-cover h-7 w-7 rounded-full">
                                <p class="dark:text-gray-200 text-primary-600 dark:hover:text-primary-400 text-sm break-words truncate">{$fullname}</p>
                            </div>
                        </div>
                        HTML;
                }),

            TextColumn::make('phone')
                ->label(t('phone'))
                ->sortable()
                ->toggleable()
                ->searchable(),

            TextColumn::make('email')
                ->label(t('email'))
                ->sortable()
                ->toggleable()
                ->searchable(),

            ToggleColumn::make('active')
                ->label(t('active'))
                ->toggleable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('admin.user.edit')) {
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

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([

                Action::make('view')
                    ->label('View')
                    ->action(fn (User $record) => $this->dispatch('viewUser', userId: $record->id))
                    ->hidden(fn () => ! checkPermission('admin.user.view')),

                Action::make('edit')
                    ->label('Edit')
                    ->action(fn (User $record) => $this->dispatch('editUser', userId: $record->id))
                    ->hidden(fn () => ! checkPermission('admin.user.edit')),

                Action::make('delete')
                    ->label('Delete')
                    ->action(fn (User $record) => $this->dispatch('confirmDelete', userId: $record->id))
                    ->hidden(function (User $record) {
                        $loggedInUser = Auth::user();

                        // 1. Permission check
                        if (! checkPermission('admin.users.delete')) {
                            return true;
                        }

                        // 2. Prevent deletion of admin users or self
                        if ($record->is_admin === true || $record->id === $loggedInUser->id) {
                            return true;
                        }

                        return false; // show button
                    }),
            ])
                ->icon('heroicon-m-ellipsis-vertical'),

        ];
    }

    #[On('user-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
