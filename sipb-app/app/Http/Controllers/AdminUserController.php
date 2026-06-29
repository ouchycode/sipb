<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureSuperAdmin($request);

        $filters = $request->only(['q', 'role', 'per_page']);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 10;
        $filters['per_page'] = $perPage;

        $users = User::query()
            ->when($filters['q'] ?? null, function ($query, string $keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderByRaw("case when role = 'super_admin' then 0 else 1 end")
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => $this->payload($user, $request->user()));

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $filters,
            'roles' => $this->roles(),
            'stats' => [
                'total' => User::count(),
                'admin' => User::where('role', User::ROLE_ADMIN)->count(),
                'super_admin' => User::where('role', User::ROLE_SUPER_ADMIN)->count(),
            ],
        ]);
    }

    public function profile(Request $request): Response
    {
        $user = $request->user();
        
        $filters = $request->only(['date', 'action', 'per_page']);
        if (!$request->has('date')) {
            $filters['date'] = now()->format('Y-m-d');
        }
        $perPage = (int) ($filters['per_page'] ?? 5);
        $perPage = in_array($perPage, [5, 10, 20], true) ? $perPage : 5;
        $filters['per_page'] = $perPage;

        $baseQuery = \App\Models\StatusAudit::query()
            ->where('user_id', $user->id)
            ->with(['foundItem']);

        $audits = (clone $baseQuery)
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['date'] ?? null, function ($query, string $date): void {
                [$start, $end] = str_contains($date, ' - ') ? explode(' - ', $date) : [$date, $date];
                $query->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($audit) => [
                'id' => $audit->id,
                'action' => $audit->action,
                'action_label' => match ($audit->action) {
                    'status_changed' => 'Ubah Status',
                    'published' => 'Publikasi',
                    'updated' => 'Perbarui Data',
                    'created' => 'Tambah Barang',
                    default => str($audit->action)->title(),
                },
                'notes' => $audit->notes,
                'ip_address' => $audit->ip_address,
                'created_at' => $audit->created_at->format('d/m/Y, H:i').' WIB',
                'item_name' => $audit->foundItem?->name ?? '-',
            ]);

        $actions = (clone $baseQuery)
            ->select('action')
            ->distinct()
            ->pluck('action')
            ->map(fn (string $action) => [
                'value' => $action,
                'label' => match ($action) {
                    'status_changed' => 'Ubah Status',
                    'published' => 'Publikasi',
                    'updated' => 'Perbarui Data',
                    'created' => 'Tambah Barang',
                    default => str($action)->title(),
                },
            ]);

        return Inertia::render('Admin/Profile', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->roleLabel(),
                'status' => 'Aktif',
            ],
            'audits' => $audits,
            'filters' => $filters,
            'actions' => $actions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:60', 'unique:users,username', 'regex:/^[a-zA-Z0-9._]+$/'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create($data);

        return back()->with('success', 'Akun admin berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:60', Rule::unique('users', 'username')->ignore($user->id), 'regex:/^[a-zA-Z0-9._]+$/'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($request->user()->is($user) && $data['role'] !== User::ROLE_SUPER_ADMIN) {
            return back()->with('error', 'Role akun sendiri tidak bisa diturunkan.');
        }

        if ($user->isSuperAdmin() && $data['role'] !== User::ROLE_SUPER_ADMIN && $this->superAdminCount() <= 1) {
            return back()->with('error', 'Minimal harus ada satu super admin aktif.');
        }

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'Akun admin berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        if ($request->user()->is($user)) {
            return back()->with('error', 'Akun sendiri tidak bisa dihapus.');
        }

        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return back()->with('error', 'Minimal harus ada satu super admin aktif.');
        }

        $user->delete();

        return back()->with('success', 'Akun admin berhasil dihapus.');
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
    }

    private function roles(): array
    {
        return [
            ['value' => User::ROLE_ADMIN, 'label' => 'Admin'],
            ['value' => User::ROLE_SUPER_ADMIN, 'label' => 'Super Admin'],
        ];
    }

    private function payload(User $user, User $currentUser): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'is_current_user' => $currentUser->is($user),
        ];
    }

    private function superAdminCount(): int
    {
        return User::where('role', User::ROLE_SUPER_ADMIN)->count();
    }
}
