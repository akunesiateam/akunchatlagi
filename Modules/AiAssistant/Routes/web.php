<?php

use App\Http\Middleware\EnsureTenantSecurity;
use App\Http\Middleware\SanitizeInputs;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\AiAssistant\Http\Controllers\AiAssistantController;
use Modules\AiAssistant\Http\Controllers\AssistantChatController;
use Modules\AiAssistant\Http\Controllers\AssistantDocumentController;
use Modules\AiAssistant\Livewire\Tenant\AiAssistantSettings;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the ServiceProvider.
|
*/

Route::middleware(['auth', 'web', SanitizeInputs::class, TenantMiddleware::class, EnsureTenantSecurity::class, 'assistant.token'])->group(
    function () {
        Route::prefix('/{subdomain}')->as('tenant.')->group(
            function () {
                Route::get('personal-assistants', [AiAssistantController::class, 'index'])->name('personal-assistants.index');
                Route::post('personal-assistants', [AiAssistantController::class, 'store'])->name('personal-assistants.store');
                Route::get('personal-assistants/create', [AiAssistantController::class, 'create'])->name('personal-assistants.create');
                Route::get('personal-assistants/{assistant}/edit', [AiAssistantController::class, 'edit'])->name('personal-assistants.edit');
                Route::post('personal-assistants/{assistant}/destroy', [AiAssistantController::class, 'destroy'])->name('personal-assistants.destroy');

                Route::post('personal-assistants/{assistant}', [AiAssistantController::class, 'update'])->name('personal-assistants.update');
                Route::post('personal-assistants/{id}/toggle-status', [AiAssistantController::class, 'toggleStatus'])
                    ->name('personal-assistants.toggle-status');

                // ENHANCED: Document management routes
                Route::get('personal-assistants/{assistant}/documents', [AssistantDocumentController::class, 'index'])->name('assistant.documents.index');
                Route::get('personal-assistants/{assistant}/documents/create', [AssistantDocumentController::class, 'create'])->name('assistant.documents.create');
                Route::post('personal-assistants/{assistant}/documents', [AssistantDocumentController::class, 'store'])->name('assistant.documents.store');
                Route::post('assistant-documents/{document}/destroy', [AssistantDocumentController::class, 'destroy'])->name('assistant.documents.destroy');

                // ENHANCED: Sync routes with progress tracking
                Route::get('personal-assistants/{assistant}/sync', [AssistantChatController::class, 'sync'])->name('personal-assistants.sync');

                // NEW: Sync progress tracking route
                Route::get('personal-assistants/{assistant}/sync-progress', [AssistantChatController::class, 'getSyncProgress'])->name('personal-assistants.sync-progress');

                // NEW: Assistant sync status API route
                Route::get('personal-assistants/{assistant}/sync-status', [AiAssistantController::class, 'getSyncStatus'])->name('personal-assistants.sync-status');

                // Assistant chat interface
                Route::get('personal-assistants/{assistant}/chat', [AssistantChatController::class, 'show'])->name('personal-assistants.chat');
                Route::post('personal-assistants/{assistant}/send-message', [AssistantChatController::class, 'sendMessage'])->name('personal-assistants.send-message');
                Route::get('personal-assistants/{assistant}/messages', [AssistantChatController::class, 'getMessages'])->name('personal-assistants.messages');
                Route::post('personal-assistants/{assistant}/clear-chat', [AssistantChatController::class, 'clearChat'])->name('personal-assistants.clear-chat');

                // Settings
                Route::get('personal-assistants/settings', AiAssistantSettings::class)->name('personal-assistants.settings');

                // Add these to your existing web.php routes:
                Route::post('personal-assistants/bulk-sync', [AiAssistantController::class, 'bulkSync'])->name('personal-assistants.bulk-sync');
                Route::post('personal-assistants/{assistant}/force-sync', [AiAssistantController::class, 'forceSync'])->name('personal-assistants.force-sync');
                Route::get('personal-assistants/{assistant}/sync-report', [AiAssistantController::class, 'getSyncReport'])->name('personal-assistants.sync-report');
            }
        );
    }
);
