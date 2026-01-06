<?php

namespace App\Livewire\Tenant\Bot;

use App\Models\Tenant\TemplateBot;
use App\Services\FeatureService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class TemplateBotList extends Component
{
    public $confirmingDeletion = false;

    public $templatebotId = null;

    protected $featureLimitChecker;

    protected $listeners = [
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission(['tenant.template_bot.view'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }
    }

    public function boot(FeatureService $featureLimitChecker)
    {
        $this->featureLimitChecker = $featureLimitChecker;
    }

    public function confirmDelete($templatebotId)
    {
        $this->templatebotId = $templatebotId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (! checkPermission(['tenant.template_bot.delete'])) {
            return;
        }

        // Find bot
        $bot = TemplateBot::findOrFail($this->templatebotId);

        /**
         * 1. Delete main file (image/video/document/audio)
         */
        if (! empty($bot->filename) && Storage::disk('public')->exists($bot->filename)) {
            Storage::disk('public')->delete($bot->filename);
        }

        /**
         * 2. Delete carousel media if it exists
         *    (these are stored via your upload logic inside:
         *     storage/app/public/tenant/{id}/template-bot/carousel/...)
         */
        if (! empty($bot->cards_params)) {
            // Handle both JSON string and array cases
            $cards = is_string($bot->cards_params)
                ? json_decode($bot->cards_params, true) ?? []
                : (is_array($bot->cards_params) ? $bot->cards_params : []);

            foreach ($cards as $card) {
                if (! empty($card['components']) && is_array($card['components'])) {
                    foreach ($card['components'] as $component) {
                        // Handle HEADER components with media
                        if ($component['type'] === 'HEADER' &&
                            isset($component['example']['header_handle']) &&
                            is_array($component['example']['header_handle'])) {

                            foreach ($component['example']['header_handle'] as $mediaUrl) {
                                if (! empty($mediaUrl)) {
                                    // Convert full URL to storage path
                                    $relativePath = str_replace([url('storage').'/', url('/storage/'), asset('storage').'/'], '', $mediaUrl);

                                    if (Storage::disk('public')->exists($relativePath)) {
                                        Storage::disk('public')->delete($relativePath);
                                    }
                                }
                            }
                        }
                    }
                }

                // Also check for legacy media_url format (backward compatibility)
                if (! empty($card['media_url'])) {
                    $relativePath = str_replace([url('storage').'/', url('/storage/'), asset('storage').'/'], '', $card['media_url']);

                    if (Storage::disk('public')->exists($relativePath)) {
                        Storage::disk('public')->delete($relativePath);
                    }
                }
            }
        }

        /**
         * 3. Delete the entire template bot directory if it exists
         *    This ensures all associated files are removed
         */
        $botDirectory = 'tenant/'.tenant_id().'/template-bot/'.$bot->id;
        if (Storage::disk('public')->exists($botDirectory)) {
            Storage::disk('public')->deleteDirectory($botDirectory);
        }

        /**
         * 4. Also clean up any carousel directory that might exist
         */
        $carouselDirectory = 'tenant/'.tenant_id().'/template-bot/carousel';
        if (Storage::disk('public')->exists($carouselDirectory)) {
            // Get all files in carousel directory
            $carouselFiles = Storage::disk('public')->files($carouselDirectory);

            // Delete files that contain this bot's ID in the filename (if any naming convention is used)
            foreach ($carouselFiles as $file) {
                $filename = basename($file);
                // If files are named with bot ID, delete them
                if (strpos($filename, (string) $bot->id) !== false) {
                    Storage::disk('public')->delete($file);
                }
            }
        }

        /**
         * 5. Delete DB record
         */
        $bot->delete();

        /**
         * 6. Notify and refresh table
         */
        $this->confirmingDeletion = false;
        $this->notify(['type' => 'success', 'message' => t('template_bot_delete_successfully')]);
        $this->dispatch('pg:eventRefresh-template-bot-table');
    }

    public function getRemainingLimitProperty()
    {
        return $this->featureLimitChecker->getRemainingLimit('template_bots', TemplateBot::class);
    }

    public function getIsUnlimitedProperty()
    {
        return $this->remainingLimit === null;
    }

    public function getHasReachedLimitProperty()
    {
        return $this->featureLimitChecker->hasReachedLimit('template_bots', TemplateBot::class);
    }

    public function getTotalLimitProperty()
    {
        return $this->featureLimitChecker->getLimit('template_bots');
    }

    public function refreshTable()
    {
        $this->dispatch('template-bot-table-refresh');
    }

    public function render()
    {
        return view('livewire.tenant.bot.template-bot-list');
    }
}
