<?php

namespace App\Livewire\Tenant\Tables\Filament;

use App\Models\Tenant\WhatsappTemplate;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class WhatsppTemplateFilamentTable extends BaseFilamentTable
{
    protected bool $hasBulkActions = false;

    protected ?string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected function getTableQuery(): Builder
    {
        $tenantId = tenant_id();

        return WhatsappTemplate::query()
            ->selectRaw('whatsapp_templates.*, (SELECT COUNT(*) FROM whatsapp_templates i2 WHERE i2.id <= whatsapp_templates.id AND i2.tenant_id = ?) as row_num', [$tenantId])
            ->where('tenant_id', $tenantId);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('row_num')
                ->label(t('SR.NO'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('template_name')
                ->label(t('template_name'))
                ->toggleable()
                ->searchable()
                ->sortable(),

            TextColumn::make('language')
                ->label(t('languages'))
                ->toggleable()
                ->searchable()
                ->sortable(),

            TextColumn::make('category')
                ->label(t('category'))
                ->toggleable()
                ->searchable()
                ->sortable(),

            TextColumn::make('header_data_format')
                ->label(t('template_type'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->placeholder('-'),

            TextColumn::make('status')
                ->label(t('status'))
                ->toggleable()
                ->sortable()
                ->html()
                ->formatStateUsing(function ($state, $record) {
                    $class = match ($state) {
                        'APPROVED' => 'bg-success-100 text-success-800 dark:text-success-400 dark:bg-success-900/20',
                        'REJECTED' => 'bg-danger-100 text-danger-800 dark:text-danger-400 dark:bg-danger-900/20',
                        'PENDING' => 'bg-warning-100 text-warning-800 dark:text-warning-400 dark:bg-warning-900/20',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    };

                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium '.$class.'">'.($state ?? 'N/A').'</span>';
                }),

            TextColumn::make('body_data')
                ->label(t('body_data'))
                ->toggleable()
                ->searchable()
                ->sortable()
                ->wrap()
                ->limit(100)
                ->tooltip(function (TextColumn $column): ?string {
                    $state = $column->getState();

                    return strlen($state) > 100 ? $state : null;
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('edit')
                ->label('')
                ->action(fn (WhatsappTemplate $record) => $this->dispatch('showEditPage', templateId: $record->id))
                ->visible(fn (WhatsappTemplate $record) => in_array($record->status, ['APPROVED', 'PENDING']))
                ->extraAttributes([
                    'class' => 'inline-flex items-center px-2 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded shadow-sm hover:bg-blue-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 justify-center',
                ])
                ->icon('heroicon-o-pencil-square')
                ->hidden(fn () => ! checkPermission('tenant.template.edit')),

            Action::make('delete')
                ->label('')
                ->extraAttributes([
                    'class' => 'inline-flex items-center px-2 py-1 text-sm font-medium text-danger-500 bg-danger-100 rounded shadow-sm hover:bg-danger-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 justify-center',
                ])
                ->action(fn (WhatsappTemplate $record) => $this->dispatch(
                    'showDeleteConfirmation',
                    templateId: $record->id,
                    templateName: $record->template_name,
                    templateMetaId: $record->template_id,
                ))
                ->icon('heroicon-o-trash')
                ->hidden(fn () => ! checkPermission('tenant.template.delete')),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('template_name')
                ->label(t('template_name'))
                ->options($this->getDistinctValues('template_name')),

            SelectFilter::make('language')
                ->label(t('language'))
                ->options($this->getDistinctValues('language')),

            SelectFilter::make('category')
                ->label(t('category'))
                ->options($this->getDistinctValues('category')),

            SelectFilter::make('header_data_format')
                ->label(t('template_type'))
                ->options($this->getDistinctValues('header_data_format', true)), // true to exclude nulls

            SelectFilter::make('status')
                ->label(t('status'))
                ->options($this->getDistinctValues('status')),
        ];
    }

    private function getDistinctValues(string $field, bool $excludeNulls = false): array
    {
        $query = WhatsappTemplate::select($field)
            ->distinct()
            ->where('tenant_id', tenant_id())
            ->whereNotNull($field)
            ->where($field, '!=', '');

        if ($excludeNulls) {
            $query->whereNotNull($field);
        }

        return $query->pluck($field)
            ->filter()
            ->mapWithKeys(fn ($value) => [$value => $value])
            ->toArray();
    }

    #[On('deleteTemplate')]
    public function deleteTemplate($templateId, $templateName): void
    {
        if (! checkPermission('tenant.template.delete')) {
            $this->notification([
                'message' => t('access_denied_note'),
                'type' => 'error',
            ]);

            return;
        }

        try {
            $template = WhatsappTemplate::where('id', $templateId)
                ->where('tenant_id', tenant_id())
                ->first();

            if (! $template) {
                $this->notification([
                    'message' => t('template_not_found'),
                    'type' => 'error',
                ]);

                return;
            }

            // Use the WhatsApp trait to delete from Meta and database
            $whatsappTrait = new class
            {
                use \App\Traits\WhatsApp;

                public function getWaTenantId()
                {
                    return tenant_id();
                }
            };

            $result = $whatsappTrait->deleteTemplate($template->template_name, $template->template_id);

            if ($result['status']) {
                $this->notification([
                    'message' => $result['message'],
                    'type' => 'success',
                ]);

                // Refresh the table
                $this->dispatch('whatspp-template-table-refresh');
            } else {
                $this->notification([
                    'message' => $result['message'],
                    'type' => 'error',
                ]);
            }
        } catch (\Exception $e) {
            whatsapp_log('Template deletion error in table', 'error', [
                'template_id' => $templateId,
                'template_name' => $templateName,
                'error' => $e->getMessage(),
                'tenant_id' => tenant_id(),
            ], $e);

            $this->notification([
                'message' => t('template_deletion_failed').': '.$e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    #[On('whatspp-template-table-refresh')]
    public function refresh(): void
    {
        $this->resetTable();
    }
}
