<div>
    {{-- Initial State --}}
    <div class="text-center py-16">
        <x-card class="max-w-md mx-auto">
            <x-slot name="content">
                <div class="text-center">
                    @if ($availability['available'])
                        @if ($currentStep === 'initial')
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
                            <p class="text-slate-600 dark:text-slate-400 mb-6">
                                {{ t('emb_signup_info') }}
                            </p>

                            {{-- Coexistence Option --}}
                            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <div class="flex items-start space-x-3">
                                    <input type="checkbox"
                                           id="enable-coexistence"
                                           wire:model.live="enableCoexistence"
                                           class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700">
                                    <div class="flex-1">
                                        <label for="enable-coexistence" class="text-sm font-medium text-blue-900 dark:text-blue-100 cursor-pointer">
                                            Enable WhatsApp Business App Integration
                                        </label>
                                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                            Connect your existing WhatsApp Business app account and sync your contacts & message history.
                                            You'll be able to use both the app and this platform.
                                        </p>
                                    </div>
                                </div>
                            </div>

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
                businessId: ''
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
                        if (data.event === 'FINISH' || data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING') {
                            embeddedSignupData = {
                                phoneNumberId: data.data.phone_number_id || '',
                                waBaId: data.data.waba_id || '',
                                businessId: data.data.business_id || '',
                                sessionEvent: data.event || 'FINISH'
                            };
                        } else if (data.event === 'CANCEL') {
                            embeddedSignupData = {
                                phoneNumberId: '',
                                waBaId: '',
                                businessId: '',
                                sessionEvent: 'CANCEL'
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
                    sessionEvent: ''
                };

                try {
                    // Prepare extras object based on coexistence setting
                    const extras = {
                        "sessionInfoVersion": data.enable_coexistence ? 3 : 2,
                        "features":[{"name":"marketing_messages_lite"}],
                        setup: {
                            external_business_id: '{{ tenant_id() }}'
                        }
                    };

                    // Add coexistence feature type if enabled
                    if (data.enable_coexistence) {
                        extras.featureType = 'whatsapp_business_app_onboarding';
                    }

                    FB.login(function(response) {
                        if (response.authResponse) {
                            setTimeout(() => {
                                const responseData = {
                                    authResponse: response.authResponse,
                                    phoneNumberId: embeddedSignupData.phoneNumberId,
                                    waBaId: embeddedSignupData.waBaId,
                                    businessId: embeddedSignupData.businessId,
                                    sessionEvent: embeddedSignupData.sessionEvent
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
