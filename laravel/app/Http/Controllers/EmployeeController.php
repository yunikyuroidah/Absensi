<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $employees = Employee::query()
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($filter) use ($q): void {
                    $filter->where('nama', 'like', "%{$q}%")
                        ->orWhere('posisi', 'like', "%{$q}%")
                        ->orWhere('nomer_telepon', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.employees.index', [
            'employees' => $employees,
            'q' => $q,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'posisi' => ['required', 'string', 'max:120'],
            'nomer_telepon' => ['nullable', 'string', 'max:30'],
        ]);

        Employee::create($validated);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'posisi' => ['required', 'string', 'max:120'],
            'nomer_telepon' => ['nullable', 'string', 'max:30'],
        ]);

        $employee->update($validated);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil dihapus.');
    }
}
