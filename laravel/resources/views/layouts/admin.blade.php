<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Admin Absensi BKM')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|space-grotesk:500,600,700" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="admin-body">
        <div class="admin-shell">
            <aside class="admin-sidebar" data-mobile-nav data-open="false">
                <div class="admin-brand-wrap">
                    <span class="admin-brand-icon">BK</span>
                    <div>
                        <p class="admin-brand-title">Admin BKM</p>
                        <p class="admin-brand-subtitle">Control Center</p>
                    </div>
                </div>

                <nav class="admin-nav">
                    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.employees.index') }}" class="admin-nav-link {{ request()->routeIs('admin.employees.*') ? 'is-active' : '' }}">Pegawai</a>
                    <a href="{{ route('admin.attendances.index') }}" class="admin-nav-link {{ request()->routeIs('admin.attendances.*') ? 'is-active' : '' }}">Absensi</a>
                    <a href="{{ route('admin.login-attempts.index') }}" class="admin-nav-link {{ request()->routeIs('admin.login-attempts.*') ? 'is-active' : '' }}">Login Attempts</a>
                </nav>

                <form action="{{ route('admin.logout') }}" method="POST" class="mt-auto">
                    @csrf
                    <button type="submit" class="btn-ghost w-full">Logout</button>
                </form>
            </aside>

            <div class="admin-main-area">
                <header class="admin-header">
                    <button type="button" class="btn-ghost mobile-nav-toggle" data-mobile-toggle>Menu</button>
                    <div>
                        <p class="admin-header-eyebrow">Panel Administrator</p>
                        <h1 class="admin-header-title">@yield('header_title', 'Dashboard')</h1>
                    </div>
                </header>

                <main class="admin-content">
                    @if (session('success'))
                        <div class="flash flash-success" data-flash>
                            <p>{{ session('success') }}</p>
                            <button type="button" data-dismiss>Tutup</button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="flash flash-error" data-flash>
                            <p>{{ session('error') }}</p>
                            <button type="button" data-dismiss>Tutup</button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="flash flash-error" data-flash>
                            <div>
                                <p class="font-semibold">Validasi gagal:</p>
                                <ul class="list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" data-dismiss>Tutup</button>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        <div class="modal-overlay" id="delete-confirm-modal" data-modal>
            <div class="modal-card small">
                <h3 class="modal-title">Konfirmasi Hapus Data</h3>
                <p class="modal-text" id="delete-confirm-message">Data akan dihapus permanen. Lanjutkan?</p>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" data-close-modal>Batal</button>
                    <button type="button" class="btn-danger" id="delete-confirm-submit">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </body>
</html>
