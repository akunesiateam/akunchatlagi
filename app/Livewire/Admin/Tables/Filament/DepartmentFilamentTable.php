<?php

namespace App\Livewire\Admin\Tables\Filament;

use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Livewire\Attributes\On;
use Modules\Tickets\Models\Department;

class DepartmentFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Department::query()->withCount('tickets');
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

            TextColumn::make('description')
                ->label('Description')
                ->toggleable()
                ->searchable()
                ->sortable()
                ->html()
                ->limit(50)
                ->tooltip(function ($record) {
                    return $record->description;
                }),

            TextColumn::make('tickets_count')
                ->label('Tickets')
                ->toggleable()
                ->sortable(),

            TextColumn::make('assignee_id')
                ->label('Assignees')
                ->toggleable()
                ->html()
                ->default('--')
                ->formatStateUsing(function ($record) {
                    if (empty($record->assignee_id)) {
                        return '<span class="text-gray-400">--</span>';
                    }

                    // Decode JSON string or handle array
                    $userIds = json_decode(is_array($record->assignee_id) ? json_encode($record->assignee_id) : $record->assignee_id, true) ?: [];

                    if (empty($userIds)) {
                        return '<span class="text-gray-400">--</span>';
                    }

                    // Get the first 3 assignees
                    $assignees = User::whereIn('id', array_slice($userIds, 0, 3))
                        ->select(['id', 'firstname', 'lastname'])
                        ->get();

                    $totalAssignees = count($userIds);

                    $html = '<div class="flex flex-col">';

                    foreach ($assignees as $assignee) {
                        $html .= '<span class="text-xs">'.e($assignee->firstname.' '.$assignee->lastname).'</span>';
                    }

                    if ($totalAssignees > 3) {
                        $html .= '<span class="text-xs text-gray-500">+'.($totalAssignees - 3).' more</span>';
                    }

                    $html .= '</div>';

                    return $html;
                })
                ->toggleable(),

            ToggleColumn::make('status')
                ->label(t('active'))
                ->toggleable()
                ->sortable()
                ->inline(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'transform: scale(0.7); transform-origin: left center;',
                ])
                ->afterStateUpdated(function ($record, $state) {
                    if (! checkPermission('admin.department.edit')) {
                        return;
                    }

                    $record->status = $state ? 1 : 0;
                    $record->save();

                    $statusMessage = t('status_updated_successfully');

                    $this->notify([
                        'message' => $statusMessage,
                        'type' => 'success',
                    ]);
                }),

        ];
    }

    protected function getTableFilters(): array
    {
        return [

            SelectFilter::make('status')
                ->label('Status')
                ->options([
                    '1' => 'Active',
                    '0' => 'Inactive',
                ]),

        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('edit')
                ->label(t('edit'))
                ->extraAttributes([
                    'class' => 'inline-flex rounded-md items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-primary-600 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 justify-center',
                ])
                ->hidden(fn () => ! checkPermission('admin.department.edit'))
                ->action(fn (Department $record) => $this->dispatch('editDepartment', id: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex rounded-md items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->hidden(fn () => ! checkPermission('admin.department.delete'))
                ->action(fn (Department $record) => $this->dispatch('confirmDelete', id: $record->id)),
        ];
    }

    #[On('department-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
