<?php

namespace App\Http\Controllers;

use App\Models\AssetStakeholder;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
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
        $users = (clone $baseQuery)->with(['roles', 'tenantProfile']);

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
            'userInsights' => $this->userInsights($baseQuery),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->userFormPage($actor),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $user->portfolio_id);
        $this->ensureCanManageUser($actor, $user);

        $user->loadMissing([
            'portfolio',
            'roles',
            'tenantProfile.leases.leaseable',
            'recordedPayments.lease',
            'submittedMaintenanceRequests.asset',
            'assignedMaintenanceRequests.asset',
            'uploadedDocuments',
        ]);

        $stakeholders = AssetStakeholder::query()
            ->with('asset')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'User detail',
                    'title' => $user->name,
                    'description' => $user->email.' · '.$user->roles->pluck('name')->implode(' / '),
                    'backHref' => route('users.index'),
                    'backLabel' => 'All users',
                    'actions' => array_values(array_filter([
                        ['label' => 'Edit user', 'href' => route('users.edit', $user), 'variant' => 'primary'],
                        $user->tenantProfile ? ['label' => 'Open tenant profile', 'href' => route('tenants.show', $user->tenantProfile), 'variant' => 'secondary'] : null,
                    ])),
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Status', 'value' => $user->status, 'tone' => $user->status === 'active' ? 'teal' : 'muted'],
                    ['label' => 'Assets assigned', 'value' => $stakeholders->count(), 'tone' => 'primary'],
                    ['label' => 'Recorded payments', 'value' => $user->recordedPayments->count()],
                    ['label' => 'Maintenance assigned', 'value' => $user->assignedMaintenanceRequests->whereIn('status', ['open', 'in_progress'])->count(), 'tone' => 'danger'],
                ]),
                'sections' => [
                    [
                        'title' => 'Account',
                        'description' => 'Identity, role, and portfolio access.',
                        'items' => $this->detailItems([
                            ['label' => 'Email', 'value' => $user->email],
                            ['label' => 'Phone', 'value' => $user->phone],
                            ['label' => 'Locale', 'value' => $user->preferred_locale],
                            ['label' => 'Portfolio', 'value' => $this->localized($user->portfolio?->name_en, $user->portfolio?->name_ar), 'href' => $user->portfolio ? route('portfolios.show', $user->portfolio) : null],
                            ['label' => 'Roles', 'value' => $user->roles->pluck('name')->implode(', ')],
                            ['label' => 'Temporary password', 'value' => $user->force_password_reset ? 'Yes' : 'No'],
                            ['label' => 'Last login', 'value' => $user->last_login_at?->toDateTimeString()],
                        ]),
                    ],
                ],
                'related' => [
                    [
                        'title' => 'Owned / managed assets',
                        'description' => 'Asset stakeholder records tied to this user.',
                        'columns' => ['Asset', 'Relationship', 'Primary', 'Status'],
                        'rows' => $stakeholders->map(fn (AssetStakeholder $stakeholder) => [
                            'Asset' => $this->localized($stakeholder->asset?->title_en, $stakeholder->asset?->title_ar) ?? '-',
                            'Relationship' => $stakeholder->relationship_type,
                            'Primary' => $stakeholder->is_primary ? 'Yes' : 'No',
                            'Status' => $stakeholder->ends_on ? 'Ended' : 'Active',
                        ])->all(),
                        'emptyText' => 'No asset ownership or management assignments yet.',
                    ],
                    [
                        'title' => 'Maintenance workload',
                        'description' => 'Requests currently assigned to this user.',
                        'columns' => ['Request', 'Asset', 'Status', 'Priority'],
                        'rows' => $user->assignedMaintenanceRequests->take(8)->map(fn ($maintenanceRequest) => [
                            'Request' => '#'.$maintenanceRequest->id.' '.$maintenanceRequest->title,
                            'Asset' => $this->localized($maintenanceRequest->asset?->title_en, $maintenanceRequest->asset?->title_ar) ?? '-',
                            'Status' => $maintenanceRequest->status,
                            'Priority' => $maintenanceRequest->priority,
                        ])->all(),
                        'emptyText' => 'No assigned maintenance requests.',
                    ],
                ],
                'documents' => $this->documentStrip($user->uploadedDocuments->take(8)),
                'timeline' => $this->activityTimeline($user),
            ],
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $user->portfolio_id);
        $this->ensureCanManageUser($actor, $user);
        $user->loadMissing('roles');

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->userFormPage($actor, $user),
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
            'password' => ['required', 'string', 'min:8', PasswordRule::defaults()],
            'role' => ['required', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);
        abort_if(
            $data['role'] !== 'superadmin' && $portfolioId === null,
            422,
            trans('app.errors.role_requires_portfolio'),
        );
        abort_unless(in_array($data['role'], $this->roleOptions($actor), true), 422, trans('app.errors.invalid_role'));

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
            $this->ensureTenantProfileForRole($user, $portfolioId, $data['role'], $data['status']);

            return $user;
        });

        return to_route('users.show', $user)->with('success', trans('app.messages.user_created', ['name' => $user->name]));
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
            'password' => ['nullable', 'string', 'min:8', PasswordRule::defaults()],
            'role' => ['required', 'string'],
        ]);

        abort_unless(in_array($data['role'], $this->roleOptions($actor), true), 422, trans('app.errors.invalid_role'));

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

        DB::transaction(function () use ($user, $updates, $data): void {
            $user->update($updates);
            $user->syncRoles([$data['role']]);
            $this->ensureTenantProfileForRole($user, $user->portfolio_id, $data['role'], $data['status']);
        });

        return to_route('users.show', $user)->with('success', trans('app.messages.user_updated', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $user->portfolio_id);
        $this->ensureCanManageUser($actor, $user);

        if ($actor->is($user)) {
            return back()->with('error', trans('app.errors.archive_self'));
        }

        if (! $actor->hasRole('superadmin') && $user->hasAnyRole(['superadmin', 'owner'])) {
            return back()->with('error', trans('app.errors.archive_privileged_account'));
        }

        if ($actor->hasRole('property_manager') && ! $user->hasRole('tenant')) {
            return back()->with('error', trans('app.errors.manager_archive_scope'));
        }

        DB::transaction(function () use ($user) {
            $user->update(['status' => 'suspended']);
            $user->tenantProfile?->update(['status' => 'blocked']);
        });

        return to_route('users.index')->with('success', trans('app.messages.user_archived', ['name' => $user->name]));
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
            abort(403, trans('app.errors.update_self_in_profile'));
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

        abort(403, trans('app.errors.manage_account_denied'));
    }

    /**
     * @return array<string, mixed>
     */
    private function userInsights(Builder $baseQuery): array
    {
        $roleCount = fn (string $role): int => (clone $baseQuery)
            ->whereHas('roles', fn (Builder $query) => $query->where('name', $role))
            ->count();

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'suspended' => (clone $baseQuery)->where('status', 'suspended')->count(),
            'temporary_passwords' => (clone $baseQuery)->where('force_password_reset', true)->count(),
            'tenants_without_profile' => (clone $baseQuery)
                ->whereHas('roles', fn (Builder $query) => $query->where('name', 'tenant'))
                ->whereDoesntHave('tenantProfile')
                ->count(),
            'roles' => [
                ['role' => 'superadmin', 'label' => 'Superadmin', 'count' => $roleCount('superadmin')],
                ['role' => 'owner', 'label' => 'Owner', 'count' => $roleCount('owner')],
                ['role' => 'property_manager', 'label' => 'Property manager', 'count' => $roleCount('property_manager')],
                ['role' => 'tenant', 'label' => 'Tenant', 'count' => $roleCount('tenant')],
            ],
        ];
    }

    private function ensureTenantProfileForRole(User $user, ?int $portfolioId, string $role, string $status): void
    {
        if ($role !== 'tenant') {
            return;
        }

        abort_if($portfolioId === null, 422, trans('app.errors.tenant_requires_portfolio'));

        TenantProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'portfolio_id' => $portfolioId,
                'profile_type' => 'individual',
                'status' => $this->tenantProfileStatus($status),
            ],
        );
    }

    private function tenantProfileStatus(string $userStatus): string
    {
        return match ($userStatus) {
            'suspended' => 'blocked',
            'inactive' => 'inactive',
            default => 'active',
        };
    }

    private function userFormPage(User $actor, ?User $user = null): array
    {
        $fields = [];

        if ($actor->hasRole('superadmin') && $user === null) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($this->portfolioOptions($actor))
                    ->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])
                    ->prepend(['value' => '', 'label' => 'No portfolio / superadmin'])
                    ->values()
                    ->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'name', 'label' => 'Name', 'required' => true],
        ];

        if ($user === null) {
            $fields[] = ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true];
        }

        $fields = [
            ...$fields,
            ['name' => 'phone', 'label' => 'Phone'],
            ['name' => 'preferred_locale', 'label' => 'Preferred language', 'type' => 'select', 'options' => [['value' => 'en', 'label' => 'English'], ['value' => 'ar', 'label' => 'Arabic']]],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions(['active', 'inactive', 'suspended'])],
            ['name' => 'role', 'label' => 'Role', 'type' => 'select', 'options' => collect($this->roleOptions($actor))->map(fn ($role) => ['value' => $role, 'label' => str($role)->replace('_', ' ')->headline()->toString()])->all()],
            ['name' => 'password', 'label' => $user ? 'New password' : 'Password', 'type' => 'password', 'required' => $user === null, 'help' => $user ? 'Leave blank to keep the current password.' : 'User will be forced to reset after login.'],
        ];

        return [
            'title' => $user ? trans('app.actions.edit').' '.$user->name : 'Create user',
            'description' => 'Create only the role this person needs. Permissions flow from role and portfolio.',
            'backHref' => $user ? route('users.show', $user) : route('users.index'),
            'backLabel' => $user ? 'User detail' : 'All users',
            'action' => $user ? route('users.update', $user) : route('users.store'),
            'method' => $user ? 'put' : 'post',
            'submitLabel' => $user ? 'Update user' : 'Create user',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) ($user?->portfolio_id ?? request('portfolio_id', $actor->portfolio_id ?? '')),
                'name' => $user?->name ?? '',
                'email' => $user?->email ?? '',
                'phone' => $user?->phone ?? '',
                'preferred_locale' => $user?->preferred_locale ?? 'en',
                'status' => $user?->status ?? 'active',
                'role' => $user?->roles?->first()?->name ?? $this->roleOptions($actor)[0],
                'password' => '',
            ],
        ];
    }
}
