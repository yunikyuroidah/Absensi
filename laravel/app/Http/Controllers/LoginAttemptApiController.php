<?php

namespace App\Http\Controllers;

use App\Models\LoginAttemptApi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginAttemptApiController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $attempts = LoginAttemptApi::query()
            ->when($q !== '', fn ($query) => $query->where('ip_address', 'like', "%{$q}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.login-attempts.index', [
            'attempts' => $attempts,
            'q' => $q,
        ]);
    }
}
