<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\AiPrompt;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;

class AiPromptFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = tenant_id();

        return AiPrompt::query()
            ->selectRaw('ai_prompts.*, (SELECT COUNT(*) FROM ai_prompts i2 WHERE i2.id <= ai_prompts.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('tenant_id', $tenantId);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label('SR.NO')
                ->toggleable(),
            TextColumn::make('name')
                ->sortable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('action')
                ->label(t('prompt_action'))
                ->sortable()
                ->searchable()
                ->toggleable(),
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
                ->hidden(fn () => ! checkPermission('tenant.ai_prompt.edit'))
                ->action(fn (AiPrompt $record) => $this->dispatch('editAiPrompt', promptId: $record->id)),

            Action::make('delete')
                ->label(t('delete'))
                ->extraAttributes([
                    'class' => 'inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-danger-600 shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center rounded-md',
                ])
                ->hidden(fn () => ! checkPermission('tenant.ai_prompt.delete'))
                ->action(fn (AiPrompt $record) => $this->dispatch('confirmDelete', promptId: $record->id)),
        ];
    }

    #[On('ai-prompt-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
