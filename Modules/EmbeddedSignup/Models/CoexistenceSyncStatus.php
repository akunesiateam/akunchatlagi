<?php

namespace Modules\EmbeddedSignup\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoexistenceSyncStatus extends Model
{
    use HasFactory;

    protected $table = 'embedded_signup_coexistence_sync_status';

    protected $fillable = [
        'tenant_id',
        'phone_number_id',
        'waba_id',
        'contacts_sync_status',
        'history_sync_status',
        'contacts_sync_started_at',
        'history_sync_started_at',
        'contacts_sync_completed_at',
        'history_sync_completed_at',
        'contacts_sync_request_id',
        'history_sync_request_id',
        'last_sync_error',
        'total_contacts_synced',
        'total_messages_synced',
        'history_sync_progress',
        'last_contact_sync_at',
        'last_message_echo_at',
    ];

    protected $casts = [
        'contacts_sync_started_at' => 'datetime',
        'history_sync_started_at' => 'datetime',
        'contacts_sync_completed_at' => 'datetime',
        'history_sync_completed_at' => 'datetime',
        'last_contact_sync_at' => 'datetime',
        'last_message_echo_at' => 'datetime',
        'total_contacts_synced' => 'integer',
        'total_messages_synced' => 'integer',
        'history_sync_progress' => 'integer',
    ];

    /**
     * Check if contacts sync is completed
     */
    public function isContactsSyncCompleted(): bool
    {
        return $this->contacts_sync_status === 'completed';
    }

    /**
     * Check if history sync is completed
     */
    public function isHistorySyncCompleted(): bool
    {
        return $this->history_sync_status === 'completed';
    }

    /**
     * Check if all sync processes are completed
     */
    public function isFullySynced(): bool
    {
        return $this->isContactsSyncCompleted() && $this->isHistorySyncCompleted();
    }

    /**
     * Get sync status percentage
     */
    public function getSyncPercentage(): int
    {
        $contactsWeight = 30; // 30% for contacts
        $historyWeight = 70;  // 70% for history

        $contactsProgress = $this->contacts_sync_status === 'completed' ? 100 :
                           ($this->contacts_sync_status === 'in_progress' ? 50 : 0);

        $historyProgress = $this->history_sync_progress ?? 0;

        return (int) (($contactsProgress * $contactsWeight / 100) + ($historyProgress * $historyWeight / 100));
    }

    /**
     * Update contacts sync progress
     */
    public function updateContactsSync(string $status, ?int $totalSynced = null): void
    {
        $updateData = ['contacts_sync_status' => $status];

        if ($totalSynced !== null) {
            $updateData['total_contacts_synced'] = $totalSynced;
        }

        if ($status === 'completed') {
            $updateData['contacts_sync_completed_at'] = now();
        }

        $this->update($updateData);
    }

    /**
     * Update history sync progress
     */
    public function updateHistorySync(string $status, ?int $progress = null, ?int $totalMessages = null): void
    {
        $updateData = ['history_sync_status' => $status];

        if ($progress !== null) {
            $updateData['history_sync_progress'] = $progress;
        }

        if ($totalMessages !== null) {
            $updateData['total_messages_synced'] = $totalMessages;
        }

        if ($status === 'completed') {
            $updateData['history_sync_completed_at'] = now();
            $updateData['history_sync_progress'] = 100;
        }

        $this->update($updateData);
    }

    /**
     * Record sync error
     */
    public function recordSyncError(string $error): void
    {
        $this->update([
            'last_sync_error' => $error,
            'contacts_sync_status' => 'failed',
            'history_sync_status' => 'failed',
        ]);
    }
}
