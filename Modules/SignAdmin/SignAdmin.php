<?php

namespace Modules\SignAdmin;

use Corbital\ModuleManager\Support\Module;

class SignAdmin extends Module
{
    /**
     * Register event listeners for this module.
     */
    /*public function registerHooks()
    {
        // Register chat message hooks
        add_filter('chat.message.before_send', [$this, 'addSignatureToMessage'], 10, 3);
        add_filter('whatsapp.message.before_send', [$this, 'addSignatureToMessage'], 10, 3);
    }*/

    /**
     * Add signature to message
     */
    public function addSignatureToMessage($message, $userId, $tenantId)
    {
        $settings = tenant_settings_by_group('sign_admin', $tenantId);
        
        if (!isset($settings['enabled']) || !$settings['enabled']) {
            return $message;
        }

        $signature = $this->getUserSignature($userId, $tenantId);
        
        if ($signature) {
            return $signature . ' ' . $message;
        }
        
        return $message;
    }

    /**
     * Get user signature based on their role
     */
    private function getUserSignature($userId, $tenantId)
    {
        $user = \App\Models\User::where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$user || !$user->role_id) {
            return null;
        }

        // Get role name from roles table
        $role = \App\Models\Role::where('id', $user->role_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$role) {
            return null;
        }

        $fullName = trim($user->firstname . ' ' . $user->lastname);
        $roleName = $role->name;
        
        return "*{$fullName} ({$roleName})*:";
    }

    /**
     * Called when the module is activated.
     */
    public function activate()
    {
        parent::activate();
        
        // Set default settings
        $defaultSettings = [
            'enabled' => false,
            'show_in_admin_chat' => true,
            'show_in_whatsapp' => true
        ];

        foreach ($defaultSettings as $key => $value) {
            if (!get_tenant_setting('sign_admin', $key)) {
                save_tenant_setting('sign_admin', $key, $value);
            }
        }
    }

    /**
     * Called when the module is deactivated.
     */
    public function deactivate()
    {
        // Clean up if needed
    }
}