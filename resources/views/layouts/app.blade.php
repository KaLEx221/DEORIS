<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $user = auth()->user();
        $userName = $user?->name ?? 'DEORIS User';
        $userEmail = $user?->email ?? '';
        $userRole = $user?->role ? ucfirst($user->role) : 'User';
        $initials = collect(explode(' ', $userName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('') ?: 'DU';
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="deoris-user-id" content="{{ $user?->id }}">
        <meta name="deoris-reverb-enabled" content="{{ config('broadcasting.default') === 'reverb' ? 'true' : 'false' }}">
        <meta name="deoris-reverb-app-key" content="{{ config('broadcasting.connections.reverb.key', '') }}">

        <title>{{ config('app.name', 'DEORIS Portal') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6">
        <link rel="shortcut icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('homepage_ui/homepage.css') }}?v={{ filemtime(public_path('homepage_ui/homepage.css')) }}">

        @livewireStyles
    </head>
    <body>
        <x-banner />

        <div class="portal" id="portalShell">
            <aside class="sidebar" id="sidebar" aria-label="Account navigation">
                <a class="brand" href="{{ route('homepage') }}" aria-label="DEORIS Home">
                    <div class="brand__logo">
                        <img src="{{ asset('login_ui/assets/logo.png') }}" alt="DEORIS Portal logo" class="brand__logo-img" />
                    </div>
                    <div class="brand__copy">
                        <div class="brand__title">DEORIS Portal</div>
                        <div class="brand__subtitle">Deor &amp; Dune Academe Inc.<br />Information System</div>
                    </div>
                </a>

                <button class="sidebar__collapse" id="collapseSidebar" type="button" aria-label="Collapse sidebar">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </button>

                <nav class="sidebar__nav">
                    <a class="navItem {{ request()->routeIs('homepage', 'dashboard') ? 'is-active' : '' }}"
                       href="{{ route('homepage') }}"
                       data-native-link="true">
                        <span class="navItem__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        </span>
                        <span class="navItem__label">Dashboard</span>
                    </a>

                    <a class="navItem {{ request()->routeIs('profile.show') ? 'is-active' : '' }}" href="{{ route('profile.show') }}" data-native-link="true">
                        <span class="navItem__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0" /><circle cx="12" cy="7" r="4" /></svg>
                        </span>
                        <span class="navItem__label">Profile</span>
                    </a>

                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <a class="navItem {{ request()->routeIs('api-tokens.index') ? 'is-active' : '' }}" href="{{ route('api-tokens.index') }}" data-native-link="true">
                            <span class="navItem__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M15 7h.01" /><path d="M10 13 8 15l-2-2-3 3 3 3 7-7" /><path d="M14 4a6 6 0 1 1-4.24 10.24" /></svg>
                            </span>
                            <span class="navItem__label">API Tokens</span>
                        </a>
                    @endif
                </nav>
            </aside>

            <div class="sidebarBackdrop" id="sidebarBackdrop" hidden aria-hidden="true"></div>

            <header class="topbar">
                <button class="mobileMenu" id="openSidebar" type="button" aria-label="Open menu">
                    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
                        <path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h16" />
                    </svg>
                </button>

                <div class="accountTitle">
                    @if (isset($header))
                        {{ $header }}
                    @else
                        <h2>Account</h2>
                    @endif
                </div>

                <x-portal-notifications />

                <div class="profile" id="profileMenu">
                    <button class="profile__button" id="profileButton" type="button" aria-haspopup="true" aria-expanded="false">
                        <span class="profile__avatar" aria-hidden="true">{{ $initials }}</span>
                        <span class="profile__text">
                            <span class="profile__name">{{ $userName }}</span>
                            <span class="profile__meta">{{ $userRole }}</span>
                        </span>
                        <svg class="profile__chevron" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>

                    <div class="profile__dropdown" id="profileDropdown" role="menu" aria-label="Profile menu" hidden>
                        <a class="profile__menuItem" href="{{ route('homepage') }}" role="menuitem">Dashboard</a>
                        <a class="profile__menuItem" href="{{ route('profile.show') }}" role="menuitem">Profile</a>
                        @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                            <a class="profile__menuItem" href="{{ route('api-tokens.index') }}" role="menuitem">API Tokens</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="profile__menuItem profile__menuItem--danger" type="submit" role="menuitem">Sign out</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="moduleArea accountArea">
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
        <script src="{{ asset('homepage_ui/portal-notifications.js') }}?v={{ filemtime(public_path('homepage_ui/portal-notifications.js')) }}"></script>
        <script src="{{ asset('homepage_ui/homepage.js') }}?v={{ filemtime(public_path('homepage_ui/homepage.js')) }}"></script>
        @stack('scripts')
    </body>
</html>
