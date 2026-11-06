<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Helpdesk') — Support Technique</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Inter"', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --color-primary: {{ $uiConfig['accent_color'] ?? '#334155' }};
            --color-sidebar: {{ $uiConfig['sidebar_color'] ?? '#ffffff' }};
            --color-header: {{ $uiConfig['header_color'] ?? '#ffffff' }};
            --color-success: {{ $uiConfig['success_color'] ?? '#16a34a' }};
            --color-warning: {{ $uiConfig['warning_color'] ?? '#d97706' }};
            --color-danger: {{ $uiConfig['danger_color'] ?? '#dc2626' }};
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: rgb(15 23 42);
        }

        .card {
            background: white;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .btn,
        .btn-primary,
        .btn-secondary,
        .btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            min-height: 2.5rem;
            border-radius: 0.625rem;
            font-size: 0.875rem;
            line-height: 1;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        .btn-primary:hover {
            filter: brightness(0.92);
        }

        .btn-secondary {
            background: white;
            color: rgb(51 65 85);
            border-color: rgb(226 232 240);
        }

        .btn-secondary:hover {
            background: rgb(248 250 252);
        }

        .btn-danger {
            background: var(--color-danger);
            color: white;
            border-color: var(--color-danger);
        }

        .btn-danger:hover {
            filter: brightness(0.92);
        }

        .form-input {
            display: block;
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid rgb(203 213 225);
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            outline: none;
            transition: all 0.2s ease;
            background: white;
        }

        .form-input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, white);
        }

        .form-label {
            display: block;
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: rgb(51 65 85);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            line-height: 1rem;
            font-weight: 500;
        }

        .status-open { background: rgb(224 231 255); color: rgb(67 56 202); }
        .status-in_progress { background: rgb(254 243 199); color: rgb(146 64 14); }
        .status-waiting_client { background: rgb(255 237 213); color: rgb(154 52 18); }
        .status-resolved { background: color-mix(in srgb, var(--color-success) 12%, white); color: var(--color-success); }
        .status-closed { background: rgb(241 245 249); color: rgb(71 85 105); }

        .priority-critical { background: color-mix(in srgb, var(--color-danger) 12%, white); color: var(--color-danger); }
        .priority-high { background: rgb(255 237 213); color: rgb(154 52 18); }
        .priority-medium { background: color-mix(in srgb, var(--color-warning) 14%, white); color: var(--color-warning); }
        .priority-low { background: color-mix(in srgb, var(--color-success) 12%, white); color: var(--color-success); }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: 0.5rem;
            color: rgb(71 85 105);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .nav-link:hover {
            background: rgb(241 245 249);
            color: rgb(15 23 42);
        }

        .nav-link.active {
            background: color-mix(in srgb, var(--color-primary) 12%, white);
            color: var(--color-primary);
            border: 1px solid color-mix(in srgb, var(--color-primary) 20%, white);
        }

        .layout-sidebar {
            background: var(--color-sidebar);
        }

        .layout-header {
            background: color-mix(in srgb, var(--color-header) 92%, white);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

    @stack('styles')
</head>
<body class="h-full font-sans antialiased" x-data="{ sidebarOpen: false }">
<div class="flex h-full">

    <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 border-r border-slate-200 layout-sidebar z-30">
        @include('layouts.sidebar')
    </aside>

    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <div @click="sidebarOpen = false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <aside class="relative w-72 h-full border-r border-slate-200 layout-sidebar flex flex-col">
            @include('layouts.sidebar')
        </aside>
    </div>

    <div class="flex-1 flex flex-col min-h-screen lg:pl-64">
        <header class="sticky top-0 z-20 backdrop-blur border-b border-slate-200 layout-header">
            <div class="flex items-center justify-between h-16 px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-base font-semibold text-slate-900">@yield('page-title', 'Tableau de bord')</h1>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('tickets.create') }}" class="btn-primary hidden sm:inline-flex">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Nouveau ticket</span>
                    </a>

                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-100 transition-all">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background: var(--color-primary);">
                                {{ Auth::user()->initials }}
                            </div>

                            <span class="hidden sm:block text-sm font-medium text-slate-700">{{ Auth::user()->name }}</span>

                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-52 bg-white rounded-xl border border-slate-200 shadow-lg py-1 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                            </div>

                            <a href="{{ route('tickets.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <span>Mes tickets</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <span>Se déconnecter</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        @if(session('success'))
            <div class="mx-6 mt-4 flex items-center gap-3 rounded-lg px-4 py-3 text-sm text-white" style="background: var(--color-success);">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 py-3 px-6 text-center text-xs text-slate-400">
            © {{ date('Y') }} {{ \App\Models\Setting::get('app.name', 'Helpdesk') }}
        </footer>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>