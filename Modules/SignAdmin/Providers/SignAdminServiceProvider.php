<?php

namespace Modules\SignAdmin\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SignAdmin\Services\SignatureService; // Pastikan ini ada

class SignAdminServiceProvider extends ServiceProvider
{
    protected $moduleName = 'SignAdmin';

    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->manageMenuItem();
        
        // Register chat message filters
        $this->registerChatFilters();
    }

    // --- GUNAKAN KODE BARU YANG LEBIH BAIK INI ---
    private function registerChatFilters()
    {
        // Hook into chat message sending (biarkan yang ini jika masih dipakai)
        add_filter('chat.message.before_send', function($message, $userId, $tenantId) {
            return $this->addSignatureToMessage($message, $userId, $tenantId);
        }, 10, 3);
    
        // Hook into WhatsApp message sending (ini yang kita ubah)
        add_filter('whatsapp.message.before_send', function($data) {
            // "Bongkar" kembali data dari array
            $message  = $data['message'];
            $userId   = $data['user_id'];
            $tenantId = $data['tenant_id'];
    
            // Panggil fungsi untuk menambahkan tanda tangan
            $data['message'] = $this->addSignatureToMessage($message, $userId, $tenantId);
    
            // Kembalikan seluruh array data
            return $data;
        }, 10, 1); // Perhatikan, angka terakhir diubah menjadi 1
}

    // INI ADALAH SATU-SATUNYA FUNGSI addSignatureToMessage
    private function addSignatureToMessage($message, $userId, $tenantId)
    {
        \Illuminate\Support\Facades\Log::info('SignAdmin: Filter addSignatureToMessage dipanggil untuk WhatsApp.');
        $settings = tenant_settings_by_group('sign_admin', $tenantId);
        
        if (!isset($settings['enabled']) || !$settings['enabled']) {
            return $message;
        }

        // Memanggil service yang sudah kita buat
        $signatureService = new SignatureService();
        $signature = $signatureService->getUserSignature($userId, $tenantId);
        
        if ($signature) {
            return $signature . "\n" . $message;
        }
        
        return $message;
    }

    // FUNGSI getUserSignature SUDAH DIHAPUS DARI SINI

    private function manageMenuItem()
    {
        add_filter('tenant_settings_navigation', function ($menu) {
            $menu['sign_admin'] = [
                'label' => 'Admin Signature',
                'route' => 'tenant.sign-admin.settings.view',
                'icon' => 'heroicon-m-chat-bubble-left-ellipsis',
                'condition' => 'module_exists("SignAdmin") && module_enabled("SignAdmin")',
            ];

            return $menu;
        });
    }

    protected function registerTranslations()
    {
        $langPath = resource_path('lang/modules/'.strtolower($this->moduleName));

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleName);
        } else {
            $this->loadTranslationsFrom(base_path('Modules/'.$this->moduleName.'/resources/lang'), $this->moduleName);
        }
    }

    protected function registerConfig()
    {
        $this->publishes([
            base_path('Modules/'.$this->moduleName.'/Config/config.php') => config_path($this->moduleName.'.php'),
        ], 'config');
        
        $this->mergeConfigFrom(
            base_path('Modules/'.$this->moduleName.'/Config/config.php'), $this->moduleName
        );
    }

    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/'.strtolower($this->moduleName));
        $sourcePath = base_path('Modules/'.$this->moduleName.'/resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge([$sourcePath], [$viewPath]), $this->moduleName);
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }
}