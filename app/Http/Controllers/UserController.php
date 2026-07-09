<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, [
            'status' => 'all',
            'role' => 'all',
        ]);
        $baseQuery = $this->scopeByPortfolio(User::query(), $actor);
        $users = (clone $baseQuery)->with('roles');

        $this->applyExactFilter($users, $filters, 'portfolio_id');
        $this->applyExactFilter($users, $filters, 'status');
        $this->applySearch($users, $filters['search'], [
            'name',
            'email',
            'phone',
            fn ($query, $search, $like) => $query->orWhereHas(
                'roles',
                fn ($roleQuery) => $roleQuery->where('name', 'like', $like)
            ),
        ]);

        if (($filters['role'] ?? 'all') !== 'all') {
            $users->whereHas('roles', fn ($query) => $query->where('name', $filters['role']));
        }

        return Inertia::render('admin/users/index', [
            'users' => $this->paginateTable($users, $request, $filters, [
                'created_at',
                'name',
                'email',
                'status',
            ]),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['active', 'inactive', 'suspended'], $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'roleOptions' => $this->roleOptions($actor),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', 'in:en,ar'],
            'status' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);
        abort_unless(in_array($data['role'], $this->roleOptions($actor), true), 422, 'Invalid role.');

        $user = DB::transaction(function () use ($data, $portfolioId) {
            $user = User::query()->create([
                'portfolio_id' => $portfolioId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'preferred_locale' => $data['preferred_locale'],
                'status' => $data['status'],
                'force_password_reset' => true,
                'password' => Hash::make($data['password']),
            ]);

            $user->syncRoles([$data['role']]);

            return $user;
        });

        return to_route('users.index')->with('success', "User {$user->name} created.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $user->portfolio_id);
        $this->ensureCanManageUser($actor, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', 'in:en,ar'],
            'status' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string'],
        ]);

        abort_unless(in_array($data['role'], $this->roleOptions($actor), true), 422, 'Invalid role.');

        $updates = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'preferred_locale' => $data['preferred_locale'],
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
            $updates['force_password_reset'] = true;
        }

        $user->update($updates);

        $user->syncRoles([$data['role']]);

        return to_route('users.index')->with('success', "User {$user->name} updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $user->portfolio_id);
        $this->ensureCanManageUser($actor, $user);

        if ($actor->is($user)) {
            return back()->with('error', 'You cannot archive your own account.');
        }

        if (! $actor->hasRole('superadmin') && $user->hasAnyRole(['superadmin', 'owner'])) {
            return back()->with('error', 'Only the system owner can archive owner or superadmin accounts.');
        }

        if ($actor->hasRole('property_manager') && ! $user->hasRole('tenant')) {
            return back()->with('error', 'Managers can only archive tenant accounts.');
        }

        DB::transaction(function () use ($user) {
            $user->update(['status' => 'suspended']);
            $user->tenantProfile?->update(['status' => 'blocked']);
        });

        return to_route('users.index')->with('success', "User {$user->name} archived.");
    }

    /**
     * @return array<int, string>
     */
    private function roleOptions(User $actor): array
    {
        if ($actor->hasRole('superadmin')) {
            return ['superadmin', 'owner', 'property_manager', 'tenant'];
        }

        if ($actor->hasRole('owner')) {
            return ['property_manager', 'tenant'];
        }

        return ['tenant'];
    }

    private function ensureCanManageUser(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            abort(403, 'Use Profile to update your own account.');
        }

        if ($actor->hasRole('superadmin')) {
            return;
        }

        if ($actor->hasRole('owner') && $target->hasAnyRole(['property_manager', 'tenant'])) {
            return;
        }

        if ($actor->hasRole('property_manager') && $target->hasRole('tenant')) {
            return;
        }

        abort(403, 'You are not allowed to manage this account.');
    }
}
