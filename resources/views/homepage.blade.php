<!doctype html>
<html lang="en">
  @php
    $selectedModule  = $selectedModule ?? 'dashboard';
    $visibleModules  = $visibleModules ?? [];
    $electionActive  = $electionActive ?? false;
    $moduleLinks = $moduleLinks ?? [];
    $selectedModuleUrl = $selectedModule !== 'dashboard'
      ? ($moduleLinks[$selectedModule]['url'] ?? '')
      : '';
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="deoris-user-id" content="{{ $user?->id }}" />
    <meta name="deoris-reverb-enabled" content="{{ config('broadcasting.default') === 'reverb' ? 'true' : 'false' }}" />
    <meta name="deoris-reverb-app-key" content="{{ config('broadcasting.connections.reverb.key', '') }}" />
    <title>Deoris Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6" />
    <link rel="shortcut icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6" />
    @php
      $moduleOrigins = collect($visibleModules)
        ->map(fn ($moduleKey) => $moduleLinks[$moduleKey]['url'] ?? null)
        ->filter()
        ->map(function ($url) {
          $parts = parse_url($url);
          return isset($parts['scheme'], $parts['host'])
            ? $parts['scheme'] . '://' . $parts['host']
            : null;
        })
        ->filter()
        ->unique()
        ->values();
    @endphp
    @foreach ($moduleOrigins as $moduleOrigin)
      <link rel="dns-prefetch" href="{{ $moduleOrigin }}">
      <link rel="preconnect" href="{{ $moduleOrigin }}" crossorigin>
    @endforeach
    <link rel="stylesheet" href="{{ asset('homepage_ui/homepage.css') }}?v={{ filemtime(public_path('homepage_ui/homepage.css')) }}">
    @vite(['resources/js/app.js'])
  </head>
  <body>
    <div class="portal" id="portalShell">
      <aside class="sidebar" id="sidebar" aria-label="Main navigation">
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

        {{-- Role badge --}}
        @php
          $roleBadgeLabel = match($user?->role) {
            'admin'            => 'Administrator',
            'student'          => 'Student',
            'instructor'       => 'Instructor',
            'cashier'          => 'Cashier',
            'librarian'        => 'Librarian',
            'admission_officer'=> 'Admission Officer',
            'career_officer'   => 'Career Officer',
            'nurse'            => 'Nurse / Health Officer',
            'election_officer' => 'Election Officer',
            default            => 'User',
          };
          $allModuleNav = [
            'entryease'     => ['label' => 'EntryEase',     'icon' => '<path d="M8 3h8"/><path d="M12 3v18"/><path d="M6 7h12"/><path d="M7 21h10"/>'],
            'enrollease'    => ['label' => 'EnrollEase',    'icon' => '<path d="M4 4h16v16H4z"/><path d="M4 12h16"/><path d="M12 4v16"/>'],
            'gradetrack'    => ['label' => 'GradeTrack',    'icon' => '<path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/>'],
            'meditrack'     => ['label' => 'MediTrack',     'icon' => '<path d="M12 3v18"/><path d="M3 12h18"/>'],
            'librarysys'    => ['label' => 'LibrarySys',    'icon' => '<path d="M5 4h14v17H7a2 2 0 0 1-2-2z"/><path d="M8 4v17"/>'],
            'taskflow'      => ['label' => 'TaskFlow',      'icon' => '<path d="M20 6 9 17l-5-5"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
            'careerconnect' => ['label' => 'CareerConnect', 'icon' => '<path d="M16 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M6 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M2 21c0-4 3-7 7-7"/><path d="M22 21c0-4-3-7-7-7"/>'],
            'assesspay'     => ['label' => 'AssessPay',     'icon' => '<path d="M12 2v20"/><path d="M17 5H9a4 4 0 0 0 0 8h6a4 4 0 0 1 0 8H7"/>'],
            'votesys'       => ['label' => 'VoteSys',       'icon' => '<path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/>'],
            'clearcheck'    => ['label' => 'ClearCheck',    'icon' => '<path d="M4 4h16v16H4z"/><path d="M8 12h8"/><path d="M12 8v8"/>'],
          ];
        @endphp

        <div class="sidebar__roleBadge">
          <span class="sidebar__roleLabel">{{ $roleBadgeLabel }}</span>
        </div>

        <nav class="sidebar__nav">
          {{-- Dashboard home link --}}
          <a class="navItem {{ $selectedModule === 'dashboard' ? 'is-active' : '' }}"
             href="{{ route('homepage') }}"
             data-module="dashboard">
            <span class="navItem__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            </span>
            <span class="navItem__label">Dashboard</span>
          </a>

          {{-- Role-filtered module links --}}
          @foreach ($allModuleNav as $moduleKey => $moduleNav)
            @if (in_array($moduleKey, $visibleModules))
              <a class="navItem {{ $selectedModule === $moduleKey ? 'is-active' : '' }}"
                 href="{{ url('/' . $moduleKey) }}"
                 data-module="{{ $moduleKey }}"
                 data-module-url="{{ $moduleLinks[$moduleKey]['url'] ?? '' }}">
                <span class="navItem__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">{!! $moduleNav['icon'] !!}</svg>
                </span>
                <span class="navItem__label">{{ $moduleNav['label'] }}</span>
              </a>
            @endif
          @endforeach
        </nav>
      </aside>

      <div class="sidebarBackdrop" id="sidebarBackdrop" hidden aria-hidden="true"></div>

      <header class="topbar">
        <button class="mobileMenu" id="openSidebar" type="button" aria-label="Open menu">
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
            <path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h16" />
          </svg>
        </button>

        <label class="search" for="moduleSearch">
          <svg class="search__icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
          </svg>
          <input id="moduleSearch" class="search__input" type="search" placeholder="Search for modules, grades, courses..." />
        </label>
        <div class="searchPanel" id="searchPanel" hidden></div>

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

      <main class="moduleArea" id="moduleArea" data-selected-module="{{ $selectedModule }}">
        <div class="moduleArea__loader" id="moduleAreaLoader" hidden aria-live="polite" aria-busy="true">
          <div class="moduleArea__loaderCard">
            <div class="moduleArea__loaderSpinner" aria-hidden="true"></div>
            <p>Loading module…</p>
          </div>
        </div>
        {{-- Flash error from module access denial --}}
        @if (session('error'))
          <div class="flashError" id="flashError" role="alert">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span>{{ session('error') }}</span>
            <button type="button" class="flashError__dismiss" aria-label="Dismiss">
              <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
          </div>
        @endif
        <section class="dashboardHome" id="dashboardHome" {{ $selectedModule !== 'dashboard' ? 'hidden' : '' }}>
          @php
            $role      = $user?->role ?? 'student';
            $firstName = explode(' ', $userName)[0];
            $now       = now();
            $hour      = (int) $now->format('G');
            $greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
            $dayLabel  = $now->format('l, F j, Y');

            // Module descriptions used in module cards
            $moduleDesc = [
              'entryease'     => 'Entrance exams & admissions',
              'enrollease'    => 'Enrollment & registration',
              'gradetrack'    => 'Grades & academic records',
              'meditrack'     => 'Medical records & health',
              'librarysys'    => 'Library & borrowing',
              'taskflow'      => 'Tasks & workflows',
              'careerconnect' => 'Career opportunities',
              'assesspay'     => 'Assessments & payments',
              'votesys'       => 'School elections',
              'clearcheck'    => 'Clearance status',
            ];
            $moduleAbbr = [
              'entryease'     => 'EE',
              'enrollease'    => 'EN',
              'gradetrack'    => 'GT',
              'meditrack'     => 'MT',
              'librarysys'    => 'LB',
              'taskflow'      => 'TF',
              'careerconnect' => 'CC',
              'assesspay'     => 'AP',
              'votesys'       => 'VS',
              'clearcheck'    => 'CK',
            ];
            $moduleColors = [
              'entryease'     => ['bg' => '#fff7ed', 'accent' => '#ea580c', 'icon_bg' => '#fed7aa'],
              'enrollease'    => ['bg' => '#eff6ff', 'accent' => '#2563eb', 'icon_bg' => '#bfdbfe'],
              'gradetrack'    => ['bg' => '#f0fdf4', 'accent' => '#16a34a', 'icon_bg' => '#bbf7d0'],
              'meditrack'     => ['bg' => '#fdf2f8', 'accent' => '#db2777', 'icon_bg' => '#fbcfe8'],
              'librarysys'    => ['bg' => '#fefce8', 'accent' => '#ca8a04', 'icon_bg' => '#fef08a'],
              'taskflow'      => ['bg' => '#f5f3ff', 'accent' => '#7c3aed', 'icon_bg' => '#ddd6fe'],
              'careerconnect' => ['bg' => '#ecfdf5', 'accent' => '#059669', 'icon_bg' => '#a7f3d0'],
              'assesspay'     => ['bg' => '#fff1f2', 'accent' => '#e11d48', 'icon_bg' => '#fecdd3'],
              'votesys'       => ['bg' => '#f0f9ff', 'accent' => '#0284c7', 'icon_bg' => '#bae6fd'],
              'clearcheck'    => ['bg' => '#f7fee7', 'accent' => '#65a30d', 'icon_bg' => '#d9f99d'],
            ];
            $moduleIcons = [
              'entryease'     => '<path d="M8 3h8"/><path d="M12 3v18"/><path d="M6 7h12"/><path d="M7 21h10"/>',
              'enrollease'    => '<path d="M4 4h16v16H4z"/><path d="M4 12h16"/><path d="M12 4v16"/>',
              'gradetrack'    => '<path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/>',
              'meditrack'     => '<path d="M12 3v18"/><path d="M3 12h18"/>',
              'librarysys'    => '<path d="M5 4h14v17H7a2 2 0 0 1-2-2z"/><path d="M8 4v17"/>',
              'taskflow'      => '<path d="M20 6 9 17l-5-5"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
              'careerconnect' => '<path d="M16 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M6 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M2 21c0-4 3-7 7-7"/><path d="M22 21c0-4-3-7-7-7"/>',
              'assesspay'     => '<path d="M12 2v20"/><path d="M17 5H9a4 4 0 0 0 0 8h6a4 4 0 0 1 0 8H7"/>',
              'votesys'       => '<path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/>',
              'clearcheck'    => '<path d="M20 6 9 17l-5-5"/>',
            ];
          @endphp


          {{-- ═══════════════════════════════════════════════════════════════
               ADMIN DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @if ($role === 'admin')

            {{-- ── Hero ──────────────────────────────────────────────────── --}}
            <div class="homeHero homeHero--admin">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__orb homeHero__orb--2" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    <span class="homeHero__dot homeHero__dot--live" aria-label="System online"></span>
                    Administrator &nbsp;·&nbsp; DEORIS Portal
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">You have full system control. Monitor, manage, and oversee all {{ count($visibleModules) }} modules from this dashboard.</p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                      All systems operational
                    </span>
                  </div>
                </div>
                <div class="homeHero__right">
                  <div class="homeHero__statPill">
                    <div class="homeHero__statPill-item">
                      <strong id="hp-total-students">—</strong>
                      <span>Students</span>
                    </div>
                    <div class="homeHero__statPill-divider"></div>
                    <div class="homeHero__statPill-item">
                      <strong id="hp-total-instructors">—</strong>
                      <span>Faculty</span>
                    </div>
                    <div class="homeHero__statPill-divider"></div>
                    <div class="homeHero__statPill-item">
                      <strong id="hp-total-users">—</strong>
                      <span>Total Users</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- ── Live stat cards ───────────────────────────────────────── --}}
            <div class="homeStats">
              <div class="homeStat homeStat--purple">
                <div class="homeStat__icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="homeStat__body">
                  <strong id="hp-stat-students">—</strong>
                  <span>Total Students</span>
                  <small id="hp-stat-enrolled">— enrolled</small>
                </div>
                <div class="homeStat__trend homeStat__trend--up">
                  <svg viewBox="0 0 24 24" width="12" height="12"><path d="m18 15-6-6-6 6"/></svg>
                </div>
              </div>
              <div class="homeStat homeStat--blue">
                <div class="homeStat__icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/></svg>
                </div>
                <div class="homeStat__body">
                  <strong id="hp-stat-instructors">—</strong>
                  <span>Instructors</span>
                  <small>Active faculty</small>
                </div>
                <div class="homeStat__trend homeStat__trend--up">
                  <svg viewBox="0 0 24 24" width="12" height="12"><path d="m18 15-6-6-6 6"/></svg>
                </div>
              </div>
              <div class="homeStat homeStat--orange">
                <div class="homeStat__icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="homeStat__body">
                  <strong id="hp-stat-pending">—</strong>
                  <span>Pending Admissions</span>
                  <small>Awaiting review</small>
                </div>
                <div class="homeStat__trend homeStat__trend--warn">
                  <svg viewBox="0 0 24 24" width="12" height="12"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                </div>
              </div>
              <div class="homeStat homeStat--green">
                <div class="homeStat__icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div class="homeStat__body">
                  <strong id="hp-stat-cleared">—</strong>
                  <span>Cleared Students</span>
                  <small>ClearCheck passed</small>
                </div>
                <div class="homeStat__trend homeStat__trend--up">
                  <svg viewBox="0 0 24 24" width="12" height="12"><path d="m18 15-6-6-6 6"/></svg>
                </div>
              </div>
              <div class="homeStat homeStat--red">
                <div class="homeStat__icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div class="homeStat__body">
                  <strong id="hp-stat-events-today">—</strong>
                  <span>Events Today</span>
                  <small id="hp-stat-events-failed">— failed</small>
                </div>
                <div class="homeStat__trend" id="hp-events-trend">
                  <svg viewBox="0 0 24 24" width="12" height="12"><path d="m18 15-6-6-6 6"/></svg>
                </div>
              </div>
            </div>

            {{-- ── Bottom admin monitoring ───────────────────────────────── --}}
            <div class="homeAdminOps">
              <div class="homeSection__head">
                <div>
                  <h2 class="homeSection__title">Admin Monitoring</h2>
                  <p class="homeSection__sub">Live service health and recent portal events moved below the daily dashboard.</p>
                </div>
              </div>

              <div class="homeOpsGrid">
                <div class="homePanel homePanel--wide">
                  <div class="homePanel__head">
                    <div>
                      <h3 class="homePanel__title">Service Registry</h3>
                      <p class="homePanel__sub">Current status for registered modules.</p>
                    </div>
                    <button id="hp-refresh-services" type="button" class="homePanel__link homePanel__button">Refresh</button>
                  </div>
                  <div class="homeOpsTableWrap">
                    <table class="homeOpsTable">
                      <thead>
                        <tr>
                          <th>Service</th>
                          <th>URL</th>
                          <th>Status</th>
                          <th>Health</th>
                        </tr>
                      </thead>
                      <tbody id="hp-service-registry">
                        <tr><td colspan="4" class="homeOpsTable__empty">Loading services...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="homePanel">
                  <div class="homePanel__head">
                    <div>
                      <h3 class="homePanel__title">Recent Events</h3>
                      <p class="homePanel__sub">Latest ecosystem activity.</p>
                    </div>
                    <div class="homePanel__actions">
                      <a href="{{ route('admin.monitoring') }}" class="homePanel__link" data-native-link="true">View all</a>
                      <button id="hp-refresh-events" type="button" class="homePanel__link homePanel__button">Refresh</button>
                    </div>
                  </div>
                  <ul class="homeActivity" id="hp-admin-events">
                    <li class="homeActivity__item homeActivity__item--loading">
                      <div class="homeActivity__skeleton"></div>
                      <div class="homeActivity__skeleton homeActivity__skeleton--sm"></div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            {{-- Admin stats loaded by homepage.js (see initAdminHomepage) --}}


          {{-- ═══════════════════════════════════════════════════════════════
               INSTRUCTOR DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'instructor')

            <div class="homeHero homeHero--instructor">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    <span class="homeHero__dot" style="background:#60a5fa"></span>
                    Instructor &nbsp;·&nbsp; Faculty Portal
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">Manage grades, assign tasks, and explore career resources for your classes.</p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/></svg>
                      {{ count($visibleModules) }} modules available
                    </span>
                  </div>
                </div>
              </div>
            </div>

            {{-- Module feature cards --}}
            <div class="homeFeatureRow">
              @php $mc = $moduleColors['gradetrack']; @endphp
              <a href="{{ url('/gradetrack') }}" class="homeFeatureCard" data-module="gradetrack" data-module-url="{{ $moduleLinks['gradetrack']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeFeatureCard__icon"><svg viewBox="0 0 24 24" width="22" height="22">{!! $moduleIcons['gradetrack'] !!}</svg></div>
                <div class="homeFeatureCard__body">
                  <strong>GradeTrack</strong>
                  <span>Enter and manage student grades, view academic records, and generate reports.</span>
                </div>
                <div class="homeFeatureCard__cta">Open <svg viewBox="0 0 24 24" width="14" height="14"><path d="m9 18 6-6-6-6"/></svg></div>
              </a>
              @php $mc = $moduleColors['taskflow']; @endphp
              <a href="{{ url('/taskflow') }}" class="homeFeatureCard" data-module="taskflow" data-module-url="{{ $moduleLinks['taskflow']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeFeatureCard__icon"><svg viewBox="0 0 24 24" width="22" height="22">{!! $moduleIcons['taskflow'] !!}</svg></div>
                <div class="homeFeatureCard__body">
                  <strong>TaskFlow</strong>
                  <span>Assign tasks, track progress, and manage workflows for your classes and faculty duties.</span>
                </div>
                <div class="homeFeatureCard__cta">Open <svg viewBox="0 0 24 24" width="14" height="14"><path d="m9 18 6-6-6-6"/></svg></div>
              </a>
              @php $mc = $moduleColors['careerconnect']; @endphp
              <a href="{{ url('/careerconnect') }}" class="homeFeatureCard" data-module="careerconnect" data-module-url="{{ $moduleLinks['careerconnect']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeFeatureCard__icon"><svg viewBox="0 0 24 24" width="22" height="22">{!! $moduleIcons['careerconnect'] !!}</svg></div>
                <div class="homeFeatureCard__body">
                  <strong>CareerConnect</strong>
                  <span>Explore career opportunities, post job listings, and connect with industry partners.</span>
                </div>
                <div class="homeFeatureCard__cta">Open <svg viewBox="0 0 24 24" width="14" height="14"><path d="m9 18 6-6-6-6"/></svg></div>
              </a>
            </div>

            <div class="homeBottom">
              <div class="homePanel">
                <div class="homePanel__head">
                  <h3 class="homePanel__title">Faculty Reminders</h3>
                </div>
                <ul class="homeActivity">
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:#3b82f6"></span>
                    <div class="homeActivity__body"><strong>Grade submission deadline</strong><span>Check GradeTrack for the current schedule</span></div>
                  </li>
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:#f59e0b"></span>
                    <div class="homeActivity__body"><strong>Faculty meeting</strong><span>See TaskFlow for agenda and details</span></div>
                  </li>
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:#10b981"></span>
                    <div class="homeActivity__body"><strong>New career postings</strong><span>CareerConnect has been updated</span></div>
                  </li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               CASHIER DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'cashier')

            <div class="homeHero homeHero--cashier">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    <span class="homeHero__dot" style="background:#34d399"></span>
                    Cashier &nbsp;·&nbsp; Finance Office
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">Process and manage student payment records and assessments via AssessPay.</p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M12 2v20"/><path d="M17 5H9a4 4 0 0 0 0 8h6a4 4 0 0 1 0 8H7"/></svg>
                      Finance module active
                    </span>
                  </div>
                </div>
              </div>
            </div>

            @php $mc = $moduleColors['assesspay']; @endphp
            <div class="homeSingleModule">
              <a href="{{ url('/assesspay') }}" class="homeSingleModule__card" data-module="assesspay" data-module-url="{{ $moduleLinks['assesspay']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeSingleModule__icon">
                  <svg viewBox="0 0 24 24" width="28" height="28">{!! $moduleIcons['assesspay'] !!}</svg>
                </div>
                <div class="homeSingleModule__body">
                  <strong>AssessPay</strong>
                  <p>Process student fee assessments, record payments, issue receipts, and generate collection reports. Your primary tool for all finance operations.</p>
                </div>
                <div class="homeSingleModule__cta">
                  Open AssessPay
                  <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </div>
              </a>
              <div class="homeSingleModule__tips">
                <h4>Finance Tips</h4>
                <ul>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Always issue official receipts for every payment processed</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Verify student ID before processing any transaction</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Reconcile daily collections at end of shift</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Report discrepancies to the Finance Officer immediately</li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               LIBRARIAN DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'librarian')

            <div class="homeHero homeHero--librarian">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    <span class="homeHero__dot" style="background:#fbbf24"></span>
                    Librarian &nbsp;·&nbsp; Library Services
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">Manage library records, borrowing, book inventory, and patron services via LibrarySys.</p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M5 4h14v17H7a2 2 0 0 1-2-2z"/><path d="M8 4v17"/></svg>
                      Library module active
                    </span>
                  </div>
                </div>
              </div>
            </div>

            @php $mc = $moduleColors['librarysys']; @endphp
            <div class="homeSingleModule">
              <a href="{{ url('/librarysys') }}" class="homeSingleModule__card" data-module="librarysys" data-module-url="{{ $moduleLinks['librarysys']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeSingleModule__icon">
                  <svg viewBox="0 0 24 24" width="28" height="28">{!! $moduleIcons['librarysys'] !!}</svg>
                </div>
                <div class="homeSingleModule__body">
                  <strong>LibrarySys</strong>
                  <p>Manage book inventory, process borrowing and returns, track overdue items, and maintain patron records. Your complete library management solution.</p>
                </div>
                <div class="homeSingleModule__cta">
                  Open LibrarySys
                  <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </div>
              </a>
              <div class="homeSingleModule__tips">
                <h4>Library Reminders</h4>
                <ul>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Send overdue notices to students with unreturned books</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Update book inventory after every acquisition</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Verify student clearance status before issuing library clearance</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Log all damaged or lost books in the system</li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               ADMISSION OFFICER DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'admission_officer')

            <div class="homeHero homeHero--admission">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__orb homeHero__orb--2" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    <span class="homeHero__dot" style="background:#fb923c"></span>
                    Admission Officer &nbsp;·&nbsp; Registrar
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">Review applications, manage enrollment, and monitor student clearance status.</p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M8 3h8"/><path d="M12 3v18"/></svg>
                      {{ count($visibleModules) }} modules available
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="homeFeatureRow">
              @php $mc = $moduleColors['entryease']; @endphp
              <a href="{{ url('/entryease') }}" class="homeFeatureCard" data-module="entryease" data-module-url="{{ $moduleLinks['entryease']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeFeatureCard__icon"><svg viewBox="0 0 24 24" width="22" height="22">{!! $moduleIcons['entryease'] !!}</svg></div>
                <div class="homeFeatureCard__body">
                  <strong>EntryEase</strong>
                  <span>Review entrance exam results, evaluate applications, and approve or reject student admissions.</span>
                </div>
                <div class="homeFeatureCard__cta">Open <svg viewBox="0 0 24 24" width="14" height="14"><path d="m9 18 6-6-6-6"/></svg></div>
              </a>
              @php $mc = $moduleColors['enrollease']; @endphp
              <a href="{{ url('/enrollease') }}" class="homeFeatureCard" data-module="enrollease" data-module-url="{{ $moduleLinks['enrollease']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeFeatureCard__icon"><svg viewBox="0 0 24 24" width="22" height="22">{!! $moduleIcons['enrollease'] !!}</svg></div>
                <div class="homeFeatureCard__body">
                  <strong>EnrollEase</strong>
                  <span>Manage student enrollment, subject registration, and section assignments for the current school year.</span>
                </div>
                <div class="homeFeatureCard__cta">Open <svg viewBox="0 0 24 24" width="14" height="14"><path d="m9 18 6-6-6-6"/></svg></div>
              </a>
              @php $mc = $moduleColors['clearcheck']; @endphp
              <a href="{{ url('/clearcheck') }}" class="homeFeatureCard" data-module="clearcheck" data-module-url="{{ $moduleLinks['clearcheck']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeFeatureCard__icon"><svg viewBox="0 0 24 24" width="22" height="22">{!! $moduleIcons['clearcheck'] !!}</svg></div>
                <div class="homeFeatureCard__body">
                  <strong>ClearCheck</strong>
                  <span>Monitor student clearance status across all departments and issue final clearance certificates.</span>
                </div>
                <div class="homeFeatureCard__cta">Open <svg viewBox="0 0 24 24" width="14" height="14"><path d="m9 18 6-6-6-6"/></svg></div>
              </a>
            </div>

            <div class="homeBottom">
              <div class="homePanel">
                <div class="homePanel__head"><h3 class="homePanel__title">Registrar Reminders</h3></div>
                <ul class="homeActivity">
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:#f59e0b"></span>
                    <div class="homeActivity__body"><strong>Enrollment period open</strong><span>EnrollEase — SY 2025–2026 is now active</span></div>
                  </li>
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:#3b82f6"></span>
                    <div class="homeActivity__body"><strong>New applications received</strong><span>Review pending applications in EntryEase</span></div>
                  </li>
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:#10b981"></span>
                    <div class="homeActivity__body"><strong>Clearance processing</strong><span>Students awaiting clearance sign-off</span></div>
                  </li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               CAREER OFFICER DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'career_officer')

            <div class="homeHero homeHero--career">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__orb homeHero__orb--2" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    <span class="homeHero__dot" style="background:#7c2d12"></span>
                    Career Officer &nbsp;·&nbsp; Career Services
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">Manage jobs, internships, student applications, and career coordination through CareerConnect.</p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M16 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M2 21c0-4 3-7 7-7"/></svg>
                      Career services active
                    </span>
                  </div>
                </div>
              </div>
            </div>

            @php $mc = $moduleColors['careerconnect']; @endphp
            <div class="homeSingleModule">
              <a href="{{ url('/careerconnect') }}" class="homeSingleModule__card" data-module="careerconnect" data-module-url="{{ $moduleLinks['careerconnect']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeSingleModule__icon">
                  <svg viewBox="0 0 24 24" width="28" height="28">{!! $moduleIcons['careerconnect'] !!}</svg>
                </div>
                <div class="homeSingleModule__body">
                  <strong>CareerConnect</strong>
                  <p>Create and manage opportunities, review student applications, publish career announcements, and monitor recruitment reports.</p>
                </div>
                <div class="homeSingleModule__cta">
                  Open CareerConnect
                  <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </div>
              </a>
              <div class="homeSingleModule__tips">
                <h4>Career Services Reminders</h4>
                <ul>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Review pending applications before forwarding them to partners</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Keep internship and job postings current</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Use reports to track placement activity</li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               NURSE DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'nurse')

            <div class="homeHero homeHero--nurse">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    <span class="homeHero__dot" style="background:#f472b6"></span>
                    Nurse / Health Officer &nbsp;·&nbsp; Clinic
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">Manage student medical records, health assessments, and clinic data via MediTrack.</p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M12 3v18"/><path d="M3 12h18"/></svg>
                      Health module active
                    </span>
                  </div>
                </div>
              </div>
            </div>

            @php $mc = $moduleColors['meditrack']; @endphp
            <div class="homeSingleModule">
              <a href="{{ url('/meditrack') }}" class="homeSingleModule__card" data-module="meditrack" data-module-url="{{ $moduleLinks['meditrack']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeSingleModule__icon">
                  <svg viewBox="0 0 24 24" width="28" height="28">{!! $moduleIcons['meditrack'] !!}</svg>
                </div>
                <div class="homeSingleModule__body">
                  <strong>MediTrack</strong>
                  <p>Record and manage student health data, medical consultations, immunization records, and clinic visits. Issue medical clearances and track student health trends.</p>
                </div>
                <div class="homeSingleModule__cta">
                  Open MediTrack
                  <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </div>
              </a>
              <div class="homeSingleModule__tips">
                <h4>Clinic Reminders</h4>
                <ul>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Update student health records after every clinic visit</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Issue medical clearances promptly for enrollment requirements</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Flag students with chronic conditions for follow-up</li>
                  <li><svg viewBox="0 0 24 24" width="14" height="14"><path d="M20 6 9 17l-5-5"/></svg> Maintain confidentiality of all health records</li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               ELECTION OFFICER DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'election_officer')

            <div class="homeHero homeHero--election">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__orb homeHero__orb--2" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    @if ($electionActive)
                      <span class="homeHero__dot homeHero__dot--live" aria-label="Election active"></span>
                      Election Officer &nbsp;·&nbsp; Election Live
                    @else
                      <span class="homeHero__dot" style="background:#9ca3af"></span>
                      Election Officer &nbsp;·&nbsp; COMELEC
                    @endif
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">
                    @if ($electionActive)
                      The election is currently active. Monitor voting progress and manage results in VoteSys.
                    @else
                      Manage school elections, voter eligibility, and results. Set <code>ELECTION_ACTIVE=true</code> to start an election.
                    @endif
                  </p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem" style="color:{{ $electionActive ? '#4ade80' : '#9ca3af' }}">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/></svg>
                      Election {{ $electionActive ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                </div>
                <div class="homeHero__right">
                  <div class="homeHero__electionStatus {{ $electionActive ? 'homeHero__electionStatus--active' : 'homeHero__electionStatus--inactive' }}">
                    <div class="homeHero__electionStatus-dot"></div>
                    <strong>{{ $electionActive ? 'Voting Open' : 'No Active Election' }}</strong>
                    <span>{{ $electionActive ? 'Ballots are being cast' : 'Toggle ELECTION_ACTIVE to start' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="homeFeatureRow">
              @php $mc = $moduleColors['votesys']; @endphp
              <a href="{{ url('/votesys') }}" class="homeFeatureCard" data-module="votesys" data-module-url="{{ $moduleLinks['votesys']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeFeatureCard__icon"><svg viewBox="0 0 24 24" width="22" height="22">{!! $moduleIcons['votesys'] !!}</svg></div>
                <div class="homeFeatureCard__body">
                  <strong>VoteSys</strong>
                  <span>Manage candidates, monitor live vote counts, configure election settings, and publish official results.</span>
                </div>
                <div class="homeFeatureCard__cta">Open <svg viewBox="0 0 24 24" width="14" height="14"><path d="m9 18 6-6-6-6"/></svg></div>
              </a>
              @php $mc = $moduleColors['clearcheck']; @endphp
              <a href="{{ url('/clearcheck') }}" class="homeFeatureCard" data-module="clearcheck" data-module-url="{{ $moduleLinks['clearcheck']['url'] ?? '' }}" style="--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                <div class="homeFeatureCard__icon"><svg viewBox="0 0 24 24" width="22" height="22">{!! $moduleIcons['clearcheck'] !!}</svg></div>
                <div class="homeFeatureCard__body">
                  <strong>ClearCheck</strong>
                  <span>Verify voter eligibility — only students with cleared status are permitted to cast ballots.</span>
                </div>
                <div class="homeFeatureCard__cta">Open <svg viewBox="0 0 24 24" width="14" height="14"><path d="m9 18 6-6-6-6"/></svg></div>
              </a>
            </div>

            <div class="homeBottom">
              <div class="homePanel">
                <div class="homePanel__head"><h3 class="homePanel__title">Election Status</h3></div>
                <ul class="homeActivity">
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:{{ $electionActive ? '#10b981' : '#9ca3af' }}"></span>
                    <div class="homeActivity__body">
                      <strong>Election {{ $electionActive ? 'Active' : 'Inactive' }}</strong>
                      <span>{{ $electionActive ? 'Voting is currently open — monitor in VoteSys' : 'No election running. Set ELECTION_ACTIVE=true in .env to start.' }}</span>
                    </div>
                  </li>
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:#3b82f6"></span>
                    <div class="homeActivity__body"><strong>Voter eligibility</strong><span>Managed via ClearCheck — only cleared students may vote</span></div>
                  </li>
                  <li class="homeActivity__item">
                    <span class="homeActivity__dot" style="background:#7c3aed"></span>
                    <div class="homeActivity__body"><strong>Results publication</strong><span>Publish official results through VoteSys after voting closes</span></div>
                  </li>
                </ul>
              </div>
            </div>

          {{-- ═══════════════════════════════════════════════════════════════
               STUDENT DASHBOARD — progressive unlock
          ════════════════════════════════════════════════════════════════ --}}
          @else
            @php
              $admissionStatus  = $user?->admission_status  ?? 'pending';
              $enrollmentStatus = $user?->enrollment_status ?? 'not_enrolled';
              $clearCheckPassed = $user?->clearcheck_passed ?? false;
              $fullyCleared = $admissionStatus === 'approved'
                && $enrollmentStatus === 'enrolled'
                && $clearCheckPassed;
              $step = 1;
              if ($admissionStatus === 'rejected') $step = 1;
              elseif ($admissionStatus === 'under_review') $step = 2;
              elseif ($admissionStatus === 'approved' && $enrollmentStatus === 'not_enrolled') $step = 3;
              elseif ($admissionStatus === 'approved' && $enrollmentStatus === 'enrolled' && !$clearCheckPassed) $step = 4;
              elseif ($fullyCleared) $step = 5;
              $stepLabel = match(true) {
                $admissionStatus === 'rejected'                                         => 'Application Not Approved',
                $admissionStatus === 'under_review'                                     => 'Application Under Review',
                $admissionStatus === 'pending'                                          => 'Pending Admission',
                $admissionStatus === 'approved' && $enrollmentStatus === 'not_enrolled' => 'Approved — Enroll Now',
                $admissionStatus === 'approved' && $enrollmentStatus === 'enrolled' && !$clearCheckPassed => 'Enrolled — Pay Tuition',
                $fullyCleared                                                            => 'Fully Cleared',
                default                                                                 => 'In Progress',
              };
              $progressPct = round(($step / 5) * 100);
            @endphp

            {{-- Hero --}}
            <div class="homeHero homeHero--student">
              <div class="homeHero__orb homeHero__orb--1" aria-hidden="true"></div>
              <div class="homeHero__inner">
                <div class="homeHero__left">
                  <div class="homeHero__eyebrow">
                    <span class="homeHero__dot" style="background:{{ $fullyCleared ? '#4ade80' : ($admissionStatus === 'rejected' ? '#f87171' : '#fbbf24') }}"></span>
                    Student &nbsp;·&nbsp; {{ $stepLabel }}
                  </div>
                  <h1 class="homeHero__title">{{ $greeting }}, {{ $firstName }}.</h1>
                  <p class="homeHero__sub">
                    @if ($admissionStatus === 'rejected') Your application was not approved. Please contact the Admissions Office for assistance.
                    @elseif ($admissionStatus === 'under_review') Your application is under review by the admissions team. You'll be notified once a decision is made.
                    @elseif ($step === 1) Complete your entrance exam in EntryEase to begin the admission process.
                    @elseif ($step === 3) Your admission is approved. Proceed to EnrollEase to complete your enrollment.
                    @elseif ($step === 4) You are enrolled. Pay your tuition in AssessPay to become fully cleared.
                    @else You have full access to all your modules. Welcome to DEORIS!
                    @endif
                  </p>
                  <div class="homeHero__meta">
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                      {{ $dayLabel }}
                    </span>
                    <span class="homeHero__metaItem">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                      Step {{ $step }} of 5 &nbsp;·&nbsp; {{ $progressPct }}% complete
                    </span>
                  </div>
                </div>
                <div class="homeHero__right">
                  <div class="homeHero__progressRing" aria-label="{{ $progressPct }}% complete">
                    <svg viewBox="0 0 80 80" width="80" height="80">
                      <circle cx="40" cy="40" r="34" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="6"/>
                      <circle cx="40" cy="40" r="34" fill="none" stroke="{{ $fullyCleared ? '#4ade80' : '#fbbf24' }}" stroke-width="6"
                        stroke-dasharray="{{ round(2 * 3.14159 * 34) }}"
                        stroke-dashoffset="{{ round(2 * 3.14159 * 34 * (1 - $progressPct / 100)) }}"
                        stroke-linecap="round" transform="rotate(-90 40 40)"/>
                      <text x="40" y="45" text-anchor="middle" fill="#fff8ec" font-size="16" font-weight="800">{{ $progressPct }}%</text>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            {{-- Progress stepper --}}
            <div class="homeStepperWrap">
              <div class="homeStepper">
                @php
                  $stepDefs = [
                    1 => ['label' => 'Registration',     'sub' => 'Account created'],
                    2 => ['label' => 'Entrance Exam',    'sub' => 'EntryEase'],
                    3 => ['label' => 'Admission Review', 'sub' => match($admissionStatus) {
                      'approved' => 'Approved ✓', 'rejected' => 'Rejected', default => 'Pending review',
                    }],
                    4 => ['label' => 'Enrollment',       'sub' => $enrollmentStatus === 'enrolled' ? 'Enrolled ✓' : 'Not enrolled'],
                    5 => ['label' => 'Fully Cleared',    'sub' => $fullyCleared ? 'Cleared ✓' : 'Tuition pending'],
                  ];
                @endphp
                @foreach ($stepDefs as $n => $s)
                  @if ($n > 1)
                    <div class="homeStepper__line {{ $step >= $n ? 'is-done' : '' }}"></div>
                  @endif
                  <div class="homeStepper__item {{ $step > $n ? 'is-done' : '' }} {{ $step === $n ? 'is-current' : '' }}">
                    <div class="homeStepper__dot">
                      @if ($step > $n)
                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                      @else
                        {{ $n }}
                      @endif
                    </div>
                    <div class="homeStepper__body">
                      <strong>{{ $s['label'] }}</strong>
                      <span>{{ $s['sub'] }}</span>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            {{-- Status cards --}}
            <div class="homeStats homeStats--student">
              <div class="homeStat {{ $admissionStatus === 'approved' ? 'homeStat--green' : ($admissionStatus === 'rejected' ? 'homeStat--red' : 'homeStat--orange') }}">
                <div class="homeStat__icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><path d="M8 3h8"/><path d="M12 3v18"/><path d="M6 7h12"/><path d="M7 21h10"/></svg>
                </div>
                <div class="homeStat__body">
                  <strong>{{ ucfirst(str_replace('_', ' ', $admissionStatus)) }}</strong>
                  <span>Admission Status</span>
                  <small>EntryEase</small>
                </div>
              </div>
              <div class="homeStat {{ $enrollmentStatus === 'enrolled' ? 'homeStat--green' : 'homeStat--orange' }}">
                <div class="homeStat__icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><path d="M4 4h16v16H4z"/><path d="M4 12h16"/><path d="M12 4v16"/></svg>
                </div>
                <div class="homeStat__body">
                  <strong>{{ $enrollmentStatus === 'enrolled' ? 'Enrolled' : 'Not Enrolled' }}</strong>
                  <span>Enrollment Status</span>
                  <small>EnrollEase</small>
                </div>
              </div>
              <div class="homeStat {{ $fullyCleared ? 'homeStat--green' : 'homeStat--orange' }}">
                <div class="homeStat__icon">
                  <svg viewBox="0 0 24 24" width="20" height="20"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div class="homeStat__body">
                  <strong>{{ $fullyCleared ? 'Paid' : 'Pending' }}</strong>
                  <span>Payment Status</span>
                  <small>AssessPay</small>
                </div>
              </div>
              @if ($electionActive && $fullyCleared)
                <div class="homeStat homeStat--blue">
                  <div class="homeStat__icon">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/></svg>
                  </div>
                  <div class="homeStat__body">
                    <strong>Election Live</strong>
                    <span>VoteSys</span>
                    <small>Cast your vote</small>
                  </div>
                </div>
              @endif
            </div>

            {{-- Available modules --}}
            @if (count($visibleModules) > 0)
              <div class="homeSection">
                <div class="homeSection__head">
                  <div>
                    <h2 class="homeSection__title">Your Modules</h2>
                    <p class="homeSection__sub">{{ count($visibleModules) }} available &nbsp;·&nbsp; More unlock as you progress</p>
                  </div>
                </div>
                <div class="homeModuleGrid">
                  @foreach ($visibleModules as $mk)
                    @php $mc = $moduleColors[$mk] ?? ['bg'=>'#f9fafb','accent'=>'#374151','icon_bg'=>'#e5e7eb']; @endphp
                    <a href="{{ url('/' . $mk) }}" class="homeModuleCard" data-module="{{ $mk }}" data-module-url="{{ $moduleLinks[$mk]['url'] ?? '' }}" style="--mc-bg:{{ $mc['bg'] }};--mc-accent:{{ $mc['accent'] }};--mc-icon-bg:{{ $mc['icon_bg'] }}">
                      <div class="homeModuleCard__icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">{!! $moduleIcons[$mk] ?? '' !!}</svg>
                      </div>
                      <div class="homeModuleCard__body">
                        <strong>{{ $moduleLinks[$mk]['label'] ?? ucfirst($mk) }}</strong>
                        <span>{{ $moduleDesc[$mk] ?? '' }}</span>
                      </div>
                      <div class="homeModuleCard__arrow">
                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                      </div>
                    </a>
                  @endforeach
                </div>
              </div>

              <div class="homeBottom">
                <div class="homePanel">
                  <div class="homePanel__head"><h3 class="homePanel__title">Notices</h3></div>
                  <ul class="homeActivity">
                    <li class="homeActivity__item">
                      <span class="homeActivity__dot" style="background:#3b82f6"></span>
                      <div class="homeActivity__body"><strong>Midterm Exam Schedule</strong><span>Check GradeTrack for the latest schedule</span></div>
                    </li>
                    <li class="homeActivity__item">
                      <span class="homeActivity__dot" style="background:#ef4444"></span>
                      <div class="homeActivity__body"><strong>Tuition Payment Deadline</strong><span>Pay your fees via AssessPay before the deadline</span></div>
                    </li>
                    @if ($electionActive)
                      <li class="homeActivity__item">
                        <span class="homeActivity__dot" style="background:#7c3aed"></span>
                        <div class="homeActivity__body"><strong>Election is now open</strong><span>Cast your vote in VoteSys before it closes</span></div>
                      </li>
                    @endif
                  </ul>
                </div>
              </div>
            @else
              <div class="homeEmptyState">
                <div class="homeEmptyState__icon">
                  <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                @if ($admissionStatus === 'rejected')
                  <h3>Application Not Approved</h3>
                  <p>Your application was not approved. Please contact the Admissions Office for assistance and next steps.</p>
                @else
                  <h3>No Modules Available Yet</h3>
                  <p>Complete your admission process in EntryEase to unlock your student modules.</p>
                  <a href="{{ url('/entryease') }}" class="homeEmptyState__btn" data-module="entryease" data-module-url="{{ $moduleLinks['entryease']['url'] ?? '' }}">
                    <svg viewBox="0 0 24 24" width="15" height="15"><path d="M8 3h8"/><path d="M12 3v18"/></svg>
                    Go to EntryEase
                  </a>
                @endif
              </div>
            @endif
          @endif

        </section>


        {{-- Trusted first-party modules only.
             Do not set sandbox here: these modules need scripts and their real
             subdomain origins for strict postMessage validation. A sandbox
             would either break that contract or create a misleading partial
             restriction. Isolation is enforced by per-module origins plus each
             module's frame-ancestors CSP allowing only https://deoris.test to
             embed it. --}}
        <iframe
          class="moduleFrame"
          id="moduleFrame"
          title="DEORIS module"
          hidden
          referrerpolicy="strict-origin-when-cross-origin"
          allow="clipboard-read; clipboard-write"
        ></iframe>
      </main>
    </div>

    <script src="{{ asset('homepage_ui/portal-bridge.js') }}?v={{ filemtime(public_path('homepage_ui/portal-bridge.js')) }}"></script>
    <script src="{{ asset('homepage_ui/portal-notifications.js') }}?v={{ filemtime(public_path('homepage_ui/portal-notifications.js')) }}"></script>
    <script src="{{ asset('homepage_ui/homepage.js') }}?v={{ filemtime(public_path('homepage_ui/homepage.js')) }}"></script>
  </body>
</html>
