@extends('layouts.admin')

@section('title', 'Monitor Login Attempts')
@section('header_title', 'Login Attempts')

@section('content')
    <section class="panel reveal">
        <div class="panel-toolbar">
            <div>
                <h2 class="panel-title">Log Percobaan Login Admin</h2>
                <p class="panel-subtitle">Data ini otomatis tercatat dari aktivitas login. Halaman ini hanya untuk monitoring.</p>
            </div>
        </div>

        <form action="{{ route('admin.login-attempts.index') }}" method="GET" class="search-grid mt-4">
            <input type="text" name="q" value="{{ $q }}" class="field-input" placeholder="Cari IP address">
            <button type="submit" class="btn-primary">Cari</button>
            <a href="{{ route('admin.login-attempts.index') }}" class="btn-ghost text-center">Reset</a>
        </form>

        <div class="table-shell mt-5">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Attempts</th>
                        <th>Blocked Until</th>
                        <th>Status</th>
                        <th>Update Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        @php
                            $isBlocked = $attempt->blocked_until && $attempt->blocked_until->isFuture();
                        @endphp
                        <tr>
                            <td>{{ $attempt->ip_address }}</td>
                            <td>{{ $attempt->attempts }}</td>
                            <td>{{ $attempt->blocked_until ? $attempt->blocked_until->translatedFormat('d M Y H:i') : '-' }}</td>
                            <td>
                                <span class="badge {{ $isBlocked ? 'badge-red' : 'badge-green' }}">
                                    {{ $isBlocked ? 'BLOCKED' : 'NORMAL' }}
                                </span>
                            </td>
                            <td>{{ $attempt->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-500">Belum ada catatan login attempts.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap mt-5">{{ $attempts->links() }}</div>
    </section>
@endsection
