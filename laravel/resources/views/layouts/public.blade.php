<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Absensi Pegawai BKM')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|space-grotesk:500,600,700" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="public-body">
        <main class="public-wrapper">
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
    </body>
</html>
