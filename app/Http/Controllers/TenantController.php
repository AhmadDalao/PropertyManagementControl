<?php

namespace App\Http\Controllers;

use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $tenants = $this->scopeByPortfolio(
            TenantProfile::query()->with(['user', 'leases' => fn ($query) => $query->latest('started_at')]),
            $actor
        )->latest()->get();

        return Inertia::render('admin/tenants/index', [
            'tenants' => $tenants,
            'portfolioOptions' => $this->portfolioOptions($actor),
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
            'password' => ['required', 'string', 'min:8'],
            'profile_type' => ['required', 'string'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);

        DB::transaction(function () use ($data, $portfolioId) {
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

            $user->syncRoles(['tenant']);

            TenantProfile::query()->create([
                'portfolio_id' => $portfolioId,
                'user_id' => $user->id,
                'profile_type' => $data['profile_type'],
                'national_id' => $data['national_id'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return to_route('tenants.index')->with('success', 'Tenant created successfully.');
    }

    public function update(Request $request, TenantProfile $tenant): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $tenant->portfolio_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', 'in:en,ar'],
            'profile_type' => ['required', 'string'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ]);

        $tenant->user?->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'preferred_locale' => $data['preferred_locale'],
            'status' => $data['status'],
        ]);

        $tenant->update([
            'profile_type' => $data['profile_type'],
            'national_id' => $data['national_id'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return to_route('tenants.index')->with('success', 'Tenant updated successfully.');
    }
}
