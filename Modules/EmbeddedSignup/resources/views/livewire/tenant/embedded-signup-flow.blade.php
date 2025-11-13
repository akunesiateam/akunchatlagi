<div>
    {{-- Initial State --}}
    <div class="text-center py-16">
        <x-card class="max-w-md mx-auto">
            <x-slot name="content">
                <div class="text-center">
                    @if ($availability['available'])
                        @if ($currentStep === 'initial')
                    {{-- TAMBAHAN BARU: Radio Button Connection Type --}}
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                            Pilih Opsi Koneksi
                        </h3>
                        
                        <div class="space-y-3">
                            {{-- Option 1: New Number --}}
                            <label wire:click="$set('connectionType', 'new')"
                                class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition
                                @if($connectionType === 'new') border-blue-500 bg-blue-50 dark:bg-blue-900/20 @else border-slate-200 dark:border-slate-700 hover:border-blue-300 @endif">
                                <input type="radio" 
                                    wire:model.live="connectionType" 
                                    value="new" 
                                    class="mt-1 mr-3 text-blue-600">
                                <div class="text-left flex-1">
                                    <div class="font-medium text-slate-900 dark:text-white">
                                        Hubungkan Nomor Baru
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                        Pastikan nomor belum terdaftar di produk whatsapp bisnis maupun whatsapp biasa
                                    </div>
                                </div>
                            </label>
                
                            {{-- Option 2: Existing Business App (Co-Existence) --}}
                          <label wire:click="$set('connectionType', 'coexistence')"
                                class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition
                                @if($connectionType === 'coexistence') border-blue-500 bg-blue-50 dark:bg-blue-900/20 @else border-slate-200 dark:border-slate-700 hover:border-blue-300 @endif">
                                <input type="radio" 
                                    wire:model.live="connectionType" 
                                    value="coexistence" 
                                    class="mt-1 mr-3 text-blue-600">
                                <div class="text-left flex-1">
                                    <div class="font-medium text-slate-900 dark:text-white">
                                        Hubungkan Nomor WhatsApp Bisnis App
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                        Nomor WhatsApp Bisnis WAJIB lebih dari 7 Hari dan sudah ada aktifitas bisnis/chat.
                                    </div>
                                    <div class="text-xs text-amber-600 dark:text-amber-400 mt-1 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        Nomor yang sudah memiliki Whatsapp Bisnis App
                                    </div>
                                </div>
                            </label>
                            {{-- Option 2: Existing Business App (DISABLED) --}}
                          <!--  <label class="flex items-start p-4 border-2 rounded-lg opacity-50 cursor-not-allowed border-slate-200 dark:border-slate-700">
                                <input type="radio" 
                                    disabled
                                    class="mt-1 mr-3 text-blue-600 cursor-not-allowed">
                                <div class="text-left flex-1">
                                    <div class="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                        Nomor WhatsApp Bisnis App
                                        <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded dark:bg-amber-900 dark:text-amber-200">
                                            Soon
                                        </span>
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                        Nomor WhatsApp Bisnis WAJIB lebih dari 7 Hari dan sudah ada aktifitas bisnis/chat.
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-500 mt-1 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fitur dalam pengembangan - akan segera tersedia
                                    </div>
                                </div>
                            </label>-->
                        </div>
                    </div>
                
                    {{-- EXISTING CODE: Facebook Logo & Button --}}
                    <div
                        class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-gradient-to-r from-info-100 to-sky-100 dark:from-info-900/50 dark:to-primary-900/50 mb-8">
                        
                                <svg class="h-10 w-10 text-[#1877F2] dark:text-info-400" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">
                                {{ t('sign_in_with_facebook') }}
                            </h3>
                            <p class="text-slate-600 dark:text-slate-400 mb-8">
                                {{ t('emb_signup_info') }}
                            </p>
                            <button wire:click="launchEmbeddedSignup"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-[#1877F2]  hover:bg-[#1e66c5] text-white"
                                @if ($isProcessing) disabled @endif>
                                <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                @if ($isProcessing)
                                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4">
                                        </circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ t('connecting') }}
                                @else
                                    {{ t('connect_with_facebook') }}
                                @endif
                            </button>
                            {{-- Processing State --}}
                        @elseif ($currentStep === 'launching')
                            <div class="text-center py-4">
                                <div
                                    class="inline-flex items-center px-4 py-2 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-lg">
                                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ t('opening_facebook_window') }}
                                </div>
                            </div>

                            {{-- Processing Signup --}}
                        @elseif ($currentStep === 'processing')
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <div
                                        class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg">
                                        <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        {{ t('setting_up_whatsapp') }}
                                    </div>
                                </div>

                                <div class="max-w-md mx-auto">
                                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                            {{ t('authenticating_with_facebook') }}
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-blue-500 animate-spin" fill="none"
                                                viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4">
                                                </circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            {{ t('configuring_whatsapp_business') }}
                                        </div>
                                        <div class="flex items-center text-gray-400">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke-width="2"></circle>
                                            </svg>
                                            {{ t('setting_up_webhooks') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Success State --}}
                        @elseif ($currentStep === 'success')
                            <div class="text-center py-4">
                                <div
                                    class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg">
                                    <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ t('whatsapp_connected_successfully') }}
                                </div>
                            </div>

                            {{-- Error State --}}
                        @elseif ($currentStep === 'error')
                            <div class="py-6">
                                <div class="text-center mb-6">
                                    <div
                                        class="inline-flex items-center px-4 py-2 {{ $this->getErrorClasses() }} rounded-lg mb-4 border">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            @if ($errorSeverity === 'critical')
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                    clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        @if ($errorSeverity === 'critical')
                                            {{ t('critical_error_occurred') }}
                                        @else
                                            {{ t('connection_failed') }}
                                        @endif
                                    </div>

                                    @if ($errorCode)
                                        <div class="flex justify-center space-x-3 mb-4">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                Error: {{ $errorCode }}
                                            </span>
                                            @if ($errorType)
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if ($errorType === 'configuration') bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200
                                    @elseif($errorType === 'authentication') bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200
                                    @elseif($errorType === 'waba') bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200
                                    @else bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 @endif">
                                                    Type: {{ ucfirst($errorType) }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mb-6">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                            {{ t('what_went_wrong') }}
                                        </h3>
                                        <p class="text-gray-700 dark:text-gray-300">
                                            {{ $errorMessage }}
                                        </p>
                                    </div>

                                    @if (!empty($suggestedActions))
                                        <div class="mb-4">
                                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">
                                                {{ t('suggested_actions') }}
                                            </h4>
                                            <ul class="space-y-2">
                                                @foreach ($suggestedActions as $action)
                                                    <li class="flex items-start">
                                                        <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500 flex-shrink-0"
                                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                                            </path>
                                                        </svg>
                                                        <span
                                                            class="text-sm text-gray-700 dark:text-gray-300">{{ $action }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>

                                <div class="text-center space-x-3">
                                    @if ($this->canRetry())
                                        <button wire:click="resetFlow"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                </path>
                                            </svg>
                                            {{ t('try_again') }}
                                        </button>
                                    @endif

                                    @if ($this->shouldShowContactAdmin())
                                        <a href="{{ tenant_route('tenant.dashboard') }}"
                                            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md shadow-sm transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                </path>
                                            </svg>
                                            {{ t('contact_administrator') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <div
                                class="inline-flex items-center px-4 py-2 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-lg mb-4">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                {{ t('service_unavailable') }}
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                {{ t('embedded_signup_not_configured') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                {{ t('contact_administrator_to_configure') }}
                            </p>
                        </div>
                    @endif
                </div>
            </x-slot>
        </x-card>
    </div>
</div>

    @push('scripts')
    <script>
        let embeddedSignupData = {
            phoneNumberId: '',
            waBaId: '',
            businessId: '',
            isCoexistence: false
        };

        if (!window.fbEmbeddedSignupLoaded) {
            window.fbEmbeddedSignupLoaded = true;

            if (window.FB) {
                delete window.FB;
            }

            const existingScript = document.getElementById('facebook-jssdk');
            if (existingScript) {
                existingScript.remove();
            }

            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s);
                js.id = id;
                js.src = "https://connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        }

        window.addEventListener('message', function(event) {
            if (event.origin !== "https://www.facebook.com") return;

            try {
                let data;

                if (typeof event.data === 'string') {
                    try {
                        data = JSON.parse(event.data);
                    } catch (e) {
                        return;
                    }
                } else if (typeof event.data === 'object') {
                    data = event.data;
                } else {
                    return;
                }

                if (data && data.type === 'WA_EMBEDDED_SIGNUP') {
                    // Regular signup finish
                    if (data.event === 'FINISH' && data.data) {
                        embeddedSignupData = {
                            phoneNumberId: data.data.phone_number_id || '',
                            waBaId: data.data.waba_id || '',
                            businessId: data.data.business_id || '',
                            isCoexistence: false
                        };
                    } 
                    // Co-existence signup finish
                    else if (data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING' && data.data) {
                        embeddedSignupData = {
                            phoneNumberId: data.data.phone_number_id || '',
                            waBaId: data.data.waba_id || '',
                            businessId: data.data.business_id || '',
                            isCoexistence: true
                        };
                        console.log('Co-Existence Signup Data:', embeddedSignupData);
                    } 
                    // Cancel event
                    else if (data.event === 'CANCEL') {
                        embeddedSignupData = {
                            phoneNumberId: '',
                            waBaId: '',
                            businessId: '',
                            isCoexistence: false
                        };
                    }
                }
            } catch (error) {
                // Continue silently
            }
        });

        window.fbAsyncInit = function() {
            FB.init({
                appId: '{{ $availability['app_id'] ?? '' }}',
                cookie: true,
                xfbml: true,
                version: 'v24.0'
            });
        };

        Livewire.on('launch-facebook-dialog', (eventData) => {
            const data = Array.isArray(eventData) ? eventData[0] : eventData;

            embeddedSignupData = {
                phoneNumberId: '',
                waBaId: '',
                businessId: '',
                isCoexistence: false
            };

            try {
                // Build extras based on connection type
                let extras = {
                    "sessionInfoVersion": 3,
                    "features": ["marketing_messages_lite"],
                    setup: {
                        external_business_id: '{{ tenant_id() }}'
                    }
                };

                // If co-existence mode, use different extras
                if (data.connection_type === 'coexistence') {
                    extras = {
                        "version": "v3",
                        "featureType": "whatsapp_business_app_onboarding"
                    };
                }

                FB.login(function(response) {
                    if (response.authResponse) {
                        setTimeout(() => {
                            const responseData = {
                                authResponse: response.authResponse,
                                phoneNumberId: embeddedSignupData.phoneNumberId,
                                waBaId: embeddedSignupData.waBaId,
                                businessId: embeddedSignupData.businessId,
                                connectionType: data.connection_type,
                                isCoexistence: embeddedSignupData.isCoexistence || false
                            };

                            @this.call('processSignupResponse', responseData);
                        }, 3000);
                    } else {
                        @this.set('isProcessing', false);
                        @this.set('currentStep', 'initial');
                        @this.set('errorMessage', 'Facebook authentication failed');
                    }
                }, {
                    config_id: data.config_id,
                    redirect_uri: data.redirect_url,
                    response_type: 'code',
                    override_default_response_type: true,
                    extras: extras
                });
            } catch (error) {
                @this.set('isProcessing', false);
                @this.set('currentStep', 'error');
                @this.set('errorMessage', 'Error launching Facebook login');
            }
        });

        Livewire.on('signup-completed', (eventData) => {
            const data = Array.isArray(eventData) ? eventData[0] : eventData;
            if (data.redirect_url) {
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, data.delay || 2000);
            }
        });
    </script>
@endpush
</div>