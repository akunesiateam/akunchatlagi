<?php

namespace App\Livewire\Tenant\Tables\Filament;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

abstract class BaseFilamentTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * Whether to enable bulk actions in the table
     */
    protected bool $hasBulkActions = false;

    /**
     * The default column to sort by
     */
    protected ?string $defaultSortColumn = null;

    /**
     * The default sort direction ('asc' or 'desc')
     */
    protected string $defaultSortDirection = 'desc';

    /**
     * Mount the component with optional sort parameters
     */
    public function mount(?string $sortColumn = null, ?string $sortDirection = null): void
    {
        if ($sortColumn) {
            $this->defaultSortColumn = $sortColumn;
        }
        if ($sortDirection && in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $this->defaultSortDirection = strtolower($sortDirection);
        }
    }

    /**
     * Get the table query
     */
    abstract protected function getTableQuery();

    /**
     * Get table columns
     */
    abstract protected function getTableColumns(): array;

    /**
     * Get table filters
     */
    protected function getTableFilters(): array
    {
        return [];
    }

    /**
     * Get table actions
     */
    protected function getTableActions(): array
    {
        return [];
    }

    /**
     * Get bulk actions
     */
    protected function getTableBulkActions(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        $table = $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->filters($this->getTableFilters())
            ->actions($this->getTableActions())
            ->headerActions($this->getTableHeaderActions())
            ->emptyStateActions($this->getTableEmptyStateActions())
            ->paginated(table_pagination_settings()['options'])
            ->defaultPaginationPageOption(table_pagination_settings()['current'])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();

        if ($this->defaultSortColumn) {
            $table->defaultSort($this->defaultSortColumn, $this->defaultSortDirection);
        }

        if ($this->hasBulkActions) {
            $table->bulkActions($this->getTableBulkActions())
                ->selectable();
        }

        return $table;
    }

    /**
     * Get table header actions
     */
    protected function getTableHeaderActions(): array
    {
        return [];
    }

    /**
     * Get table empty state actions
     */
    protected function getTableEmptyStateActions(): array
    {
        return [];
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.tenant.tables.filament.base-filament-table');
    }
}
