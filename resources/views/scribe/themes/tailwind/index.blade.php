<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{{ $metadata['title'] }}</title>
    <meta name="description" content="{{ $metadata['description'] ?? '' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                        'mono': ['JetBrains Mono', 'Monaco', 'Consolas', 'monospace'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        whatsapp: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-10px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        },
                    }
                }
            }
        }
    </script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/heroicons@2.0.18/outline/style.css">

    <!-- Highlight.js for code syntax highlighting -->
    <link rel="stylesheet" href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/github-dark.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <!-- Search functionality -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .whatsapp-gradient {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
        }

        .code-block {
            background: #1e1e1e;
            border-radius: 0.75rem;
            position: relative;
        }

        .code-block::before {
            content: '';
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 12px;
            height: 12px;
            background: #ff5f56;
            border-radius: 50%;
            box-shadow: 20px 0 #ffbd2e, 40px 0 #27ca3f;
        }

        .prose pre {
            background: transparent !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
            border-radius: 3px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.8);
        }

        /* Language tab styles */
        .language-tab {
            transition: all 0.2s ease;
        }

        .language-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Try it out form styles */
        .try-it-form input[type="text"],
        .try-it-form input[type="email"],
        .try-it-form input[type="password"],
        .try-it-form textarea,
        .try-it-form select {
            @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors;
        }

        .try-it-form button[type="submit"] {
            @apply bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 transform hover:scale-105;
        }

        /* Hide all language examples by default */
        .bash-example code { display: none; }
        .javascript-example code { display: none; }
        .php-example code { display: none; }
        .python-example code { display: none; }
    </style>

    <script>
        var tryItOutBaseUrl = "{!! $tryItOut['base_url'] ?? $baseUrl !!}";
        var useCsrf = Boolean({!! $tryItOut['use_csrf'] ?? null !!});
        var csrfUrl = "{!! $tryItOut['csrf_url'] ?? null !!}";
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 font-sans" data-languages='["bash", "javascript", "php", "python"]'>
    <!-- Mobile menu button -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobile-menu-button" class="bg-white dark:bg-gray-800 p-2 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <!-- Sidebar Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2">
                @if($metadata['logo'] ?? false)
                    <img src="{{ $metadata['logo'] }}" alt="Logo" class="w-6 h-6 rounded">
                @else
                    <div class="w-6 h-6 whatsapp-gradient rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.690"/>
                        </svg>
                    </div>
                @endif
                <div>
                    <h1 class="text-base font-bold text-gray-900 dark:text-white">{{ $metadata['title'] }}</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">API Documentation</p>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <input type="text" id="input-search" placeholder="Search endpoints..."
                       class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <svg class="absolute left-2.5 top-2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto sidebar-scroll p-3" id="toc">
            @include("scribe::themes.tailwind.sidebar")
        </nav>

        <!-- Footer -->
        <div class="p-3 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                Generated with ❤️ by
                <a href="https://scribe.knuckles.wtf" target="_blank" class="text-primary-600 hover:text-primary-700">Scribe</a>
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 text-center mt-1">
                Last updated: {{ $metadata['last_updated'] ?? date('F j, Y') }}
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-72">
        <!-- Top Header -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30">
            <div class="px-4 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $metadata['title'] }}</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ $metadata['description'] ?? '' }}</p>
                    </div>

                    <!-- Dark mode toggle -->
                    <button id="theme-toggle" class="p-1.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg id="theme-toggle-dark-icon" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                        <svg id="theme-toggle-light-icon" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="min-h-screen">
            <div id="content" class="max-w-none prose prose-lg dark:prose-invert px-4 py-6">
                {!! $intro !!}

                {!! $auth !!}

                @include("scribe::themes.tailwind.groups")

                {!! $append !!}
            </div>
        </main>
    </div>

    <!-- Backdrop for mobile menu -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

    <!-- Scripts -->
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.2.1.js") }}"></script>

    <script>
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');

        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                darkIcon.classList.add('hidden');
                lightIcon.classList.remove('hidden');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                darkIcon.classList.remove('hidden');
                lightIcon.classList.add('hidden');
                localStorage.setItem('theme', 'light');
            }
        }

        // Initialize theme
        const savedTheme = localStorage.getItem('theme') ||
                          (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        setTheme(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // Mobile menu functionality
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        function toggleMobileMenu() {
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        mobileMenuButton.addEventListener('click', toggleMobileMenu);
        backdrop.addEventListener('click', toggleMobileMenu);

        // Initialize syntax highlighting
        hljs.highlightAll();

        // Language switching functionality
        const languages = ["bash", "javascript", "php", "python"];
        let currentLanguage = languages[0] || 'bash';

        function showLanguage(language) {
            // Hide all language examples
            languages.forEach(lang => {
                document.querySelectorAll(`.${lang}-example code`).forEach(el => {
                    el.style.display = 'none';
                });
                document.querySelectorAll(`.${lang}-example`).forEach(el => {
                    el.style.display = 'none';
                });
            });

            // Show selected language examples
            document.querySelectorAll(`.${language}-example code`).forEach(el => {
                el.style.display = 'block';
            });
            document.querySelectorAll(`.${language}-example`).forEach(el => {
                el.style.display = 'block';
            });

            // Update active tab
            document.querySelectorAll('.language-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll(`.language-tab[data-language="${language}"]`).forEach(tab => {
                tab.classList.add('active');
            });

            currentLanguage = language;
            localStorage.setItem('preferred-language', language);
        }

        // Initialize language from localStorage or default
        const savedLanguage = localStorage.getItem('preferred-language') || languages[0];
        if (languages.includes(savedLanguage)) {
            showLanguage(savedLanguage);
        }

        // Add language tabs to code blocks
        document.addEventListener('DOMContentLoaded', function() {
            const codeBlocks = document.querySelectorAll('pre code');

            codeBlocks.forEach(block => {
                const pre = block.parentElement;
                if (!pre.querySelector('.language-tabs')) {
                    const tabs = document.createElement('div');
                    tabs.className = 'language-tabs flex space-x-1 mb-4';

                    languages.forEach(lang => {
                        const tab = document.createElement('button');
                        tab.className = `language-tab px-3 py-1 text-sm rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors ${lang === currentLanguage ? 'active' : ''}`;
                        tab.textContent = lang.charAt(0).toUpperCase() + lang.slice(1);
                        tab.setAttribute('data-language', lang);
                        tab.addEventListener('click', () => showLanguage(lang));
                        tabs.appendChild(tab);
                    });

                    pre.parentElement.insertBefore(tabs, pre);
                }
            });

            // Initialize search functionality
            const searchInput = document.getElementById('input-search');
            if (searchInput && typeof Jets !== 'undefined') {
                new Jets({
                    searchTag: '#input-search',
                    contentTag: '#toc'
                });
            }

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });

        // Add animation classes to elements as they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        });

        document.querySelectorAll('h1, h2, h3, .code-block').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
