<?php

namespace Modules\EmbeddedSignup\Livewire\Tenant;

use App\Models\Tenant\Chat;
use App\Models\Tenant\ChatMessage;
use App\Models\Tenant\Contact;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\EmbeddedSignup\Services\CoexistenceService;

class CoexistenceManagement extends Component
{
    use WithPagination;

    public $activeTab = 'status';

    public $searchContacts = '';

    public $searchMessages = '';

    public $selectedThread = '';

    protected $coexistenceService;

    public function mount()
    {
        $this->coexistenceService = app(CoexistenceService::class);

        // Check if coexistence is enabled for this tenant
        if (! $this->coexistenceService->isCoexistenceEnabled(tenant_id())) {
            $this->notify(['type' => 'info', 'message' => 'Coexistence is not enabled for this account. Please complete embedded signup with coexistence option.'], true);
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function searchContactsUpdated()
    {
        $this->resetPage();
    }

    public function searchMessagesUpdated()
    {
        $this->resetPage();
    }

    public function getSyncStatus()
    {
        return $this->coexistenceService->getSyncStatus(tenant_id());
    }

    public function getSyncedContacts()
    {
        $tenant_id = tenant_id();
        $subdomain = tenant_subdomain_by_tenant_id($tenant_id);

        // Get coexistence source ID
        $coexistenceSourceId = $this->getCoexistenceSourceId($tenant_id, $subdomain);

        if (! $coexistenceSourceId) {
            return collect();
        }

        $query = Contact::fromTenant($subdomain)
            ->where('source_id', $coexistenceSourceId);

        if ($this->searchContacts) {
            $query->where(function ($q) {
                $q->where('firstname', 'like', '%'.$this->searchContacts.'%')
                    ->orWhere('lastname', 'like', '%'.$this->searchContacts.'%')
                    ->orWhere('phone', 'like', '%'.$this->searchContacts.'%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function getSyncedMessages()
    {
        $tenant_id = tenant_id();
        $subdomain = tenant_subdomain_by_tenant_id($tenant_id);

        $query = ChatMessage::fromTenant($subdomain)
            ->whereIn('type', ['coexistence_history', 'coexistence_echo'])
            ->with(['chat']);

        if ($this->searchMessages) {
            $query->where('message', 'like', '%'.$this->searchMessages.'%');
        }

        return $query->orderBy('time_sent', 'desc')->paginate(20);
    }

    public function getConversationThreads()
    {
        $tenant_id = tenant_id();
        $subdomain = tenant_subdomain_by_tenant_id($tenant_id);

        // Get chats that have coexistence messages
        $chats = Chat::fromTenant($subdomain)
            ->whereHas('messages', function ($query) {
                $query->whereIn('type', ['coexistence_history', 'coexistence_echo']);
            })
            ->with(['messages' => function ($query) {
                $query->whereIn('type', ['coexistence_history', 'coexistence_echo'])
                    ->orderBy('time_sent', 'desc')
                    ->limit(1);
            }])
            ->orderBy('last_msg_time', 'desc')
            ->paginate(20);

        return $chats;
    }

    public function getThreadMessages($chatId)
    {
        $tenant_id = tenant_id();
        $subdomain = tenant_subdomain_by_tenant_id($tenant_id);

        return ChatMessage::fromTenant($subdomain)
            ->where('interaction_id', $chatId)
            ->whereIn('type', ['coexistence_history', 'coexistence_echo'])
            ->orderBy('time_sent', 'asc')
            ->get();
    }

    public function selectThread($chatId)
    {
        $this->selectedThread = $chatId;
    }

    public function retrySync($type)
    {
        // This would trigger a retry of the sync process
        $this->notify(['type' => 'info', 'message' => "Retrying {$type} sync... This may take a few minutes."]);

        // You could dispatch a job or call API to retry sync here
        // For now, just log the retry attempt
        Log::info("Coexistence {$type} sync retry requested", [
            'tenant_id' => tenant_id(),
            'type' => $type,
        ]);
    }

    protected function getCoexistenceSourceId($tenant_id, $subdomain)
    {
        $source = \App\Models\Tenant\Source::fromTenant($subdomain)
            ->where('name', 'WhatsApp Business App Coexistence')
            ->first();

        return $source ? $source->id : null;
    }

    public function render()
    {
        $syncStatus = $this->getSyncStatus();
        $contacts = $this->activeTab === 'contacts' ? $this->getSyncedContacts() : collect();
        $messages = $this->activeTab === 'messages' ? $this->getSyncedMessages() : collect();
        $conversations = $this->activeTab === 'conversations' ? $this->getConversationThreads() : collect();
        $threadMessages = $this->selectedThread ? $this->getThreadMessages($this->selectedThread) : collect();

        // Get counts for the status tab
        $contactsCount = $this->coexistenceService->getSyncedContactsCount(tenant_id(), tenant_subdomain_by_tenant_id(tenant_id()));
        $messagesCount = $this->coexistenceService->getSyncedMessagesCount(tenant_id(), tenant_subdomain_by_tenant_id(tenant_id()));

        return view('embeddedsignup::livewire.tenant.coexistence-management', [
            'syncStatus' => $syncStatus,
            'contacts' => $contacts,
            'messages' => $messages,
            'conversations' => $conversations,
            'threadMessages' => $threadMessages,
            'contactsCount' => $contactsCount,
            'messagesCount' => $messagesCount,
        ]);
    }
}
