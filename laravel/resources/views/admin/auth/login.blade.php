@extends('layouts.public')

@section('title', 'Login Admin BKM')

@section('content')
    <section class="auth-wrap reveal">
        <div class="auth-card">
            <p class="auth-eyebrow">Admin Area</p>
            <h1 class="auth-title">Login Administrator</h1>
            <p class="auth-subtitle">Masuk menggunakan akun admin yang tersimpan pada tabel admin.</p>

            <form action="{{ route('admin.login.submit') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="field-label" for="email">Email Admin</label>
                    <input id="email" type="email" name="email" class="field-input" value="{{ old('email') }}" required autofocus>
                </div>

                <div>
                    <label class="field-label" for="password">Password</label>
                    <input id="password" type="password" name="password" class="field-input" required>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat sesi login</span>
                </label>

                <button type="submit" class="btn-primary w-full">Masuk ke Dashboard Admin</button>
            </form>

            <a href="{{ route('public.attendance.index') }}" class="btn-ghost mt-4 w-full text-center">Kembali ke Halaman Absensi Pegawai</a>
        </div>
    </section>
@endsection
