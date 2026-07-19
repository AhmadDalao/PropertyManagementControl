import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type ProfileRecord = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    preferred_locale: 'en' | 'ar';
    status: string;
    force_password_reset: boolean;
    last_login_at?: string | null;
    roles: string[];
    portfolio?: {
        id: number;
        name_en: string;
        name_ar: string;
        code: string;
        status: string;
    } | null;
    tenant_profile?: {
        id: number;
        profile_type: string;
        status: string;
    } | null;
};

type PageProps = SharedProps & {
    profile: ProfileRecord;
};

export default function ProfilePage() {
    const { props } = usePage<PageProps>();
    const { profile } = props;

    const profileForm = useForm({
        name: profile.name,
        phone: profile.phone ?? '',
        preferred_locale: profile.preferred_locale,
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updateProfile = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        profileForm.put('/profile', { preserveScroll: true });
    };

    const updatePassword = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        passwordForm.put('/profile/password', {
            preserveScroll: true,
            onSuccess: () =>
                passwordForm.reset(
                    'current_password',
                    'password',
                    'password_confirmation',
                ),
        });
    };

    return (
        <AdminLayout>
            <Head title="Profile" />
            <PageHeader
                title="Profile"
                description="Control your account details, portal language, and password without touching user-management records."
                actions={
                    <Link href="/documentation" className="btn btn-primary">
                        Account guide
                    </Link>
                }
            />

            {profile.force_password_reset ? (
                <div className="alert alert-warning mb-4 border-0">
                    <strong>Temporary password active.</strong> Set a new
                    password below to secure this portal account.
                </div>
            ) : null}

            <div className="row g-4">
                <div className="col-xl-4">
                    <section className="pmc-card pmc-card--teal p-4 h-100">
                        <div className="d-flex align-items-center gap-3 mb-4">
                            <div className="pmc-profile-avatar">
                                {profile.name.slice(0, 1).toUpperCase()}
                            </div>
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Signed-in account
                                </div>
                                <h2 className="h4 mb-1">{profile.name}</h2>
                                <p className="text-secondary mb-0">
                                    {profile.email}
                                </p>
                            </div>
                        </div>

                        <div className="d-grid gap-3">
                            <InfoRow label="Status" value={profile.status} />
                            <InfoRow
                                label="Role"
                                value={profile.roles.join(' / ') || 'User'}
                            />
                            <InfoRow
                                label="Language"
                                value={
                                    profile.preferred_locale === 'ar'
                                        ? 'Arabic'
                                        : 'English'
                                }
                            />
                            <InfoRow
                                label="Last login"
                                value={formatDate(profile.last_login_at)}
                            />
                        </div>
                    </section>
                </div>

                <div className="col-xl-8">
                    <div className="row g-4">
                        <div className="col-lg-6">
                            <section className="pmc-card p-4 h-100">
                                <div className="pmc-kicker mb-2">
                                    Profile details
                                </div>
                                <h2 className="h4 mb-3">
                                    Keep contact details current
                                </h2>

                                <form
                                    className="d-grid gap-3"
                                    onSubmit={updateProfile}
                                >
                                    <div>
                                        <label
                                            className="form-label pmc-form-label"
                                            htmlFor="profile-name"
                                        >
                                            Name
                                        </label>
                                        <input
                                            id="profile-name"
                                            name="name"
                                            className="form-control"
                                            value={profileForm.data.name}
                                            onChange={(event) =>
                                                profileForm.setData(
                                                    'name',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                        <FieldError
                                            message={profileForm.errors.name}
                                        />
                                    </div>
                                    <div>
                                        <label
                                            className="form-label pmc-form-label"
                                            htmlFor="profile-email"
                                        >
                                            Email
                                        </label>
                                        <input
                                            id="profile-email"
                                            name="email"
                                            type="email"
                                            className="form-control"
                                            value={profile.email}
                                            disabled
                                        />
                                        <div className="form-text">
                                            Email changes are handled by an
                                            administrator to avoid broken tenant
                                            access.
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            className="form-label pmc-form-label"
                                            htmlFor="profile-phone"
                                        >
                                            Phone
                                        </label>
                                        <input
                                            id="profile-phone"
                                            name="phone"
                                            type="tel"
                                            className="form-control"
                                            value={profileForm.data.phone}
                                            onChange={(event) =>
                                                profileForm.setData(
                                                    'phone',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                        <FieldError
                                            message={profileForm.errors.phone}
                                        />
                                    </div>
                                    <div>
                                        <label
                                            className="form-label pmc-form-label"
                                            htmlFor="profile-locale"
                                        >
                                            Portal language
                                        </label>
                                        <select
                                            id="profile-locale"
                                            name="preferred_locale"
                                            className="form-select"
                                            value={
                                                profileForm.data
                                                    .preferred_locale
                                            }
                                            onChange={(event) =>
                                                profileForm.setData(
                                                    'preferred_locale',
                                                    event.currentTarget
                                                        .value as 'en' | 'ar',
                                                )
                                            }
                                        >
                                            <option value="en">English</option>
                                            <option value="ar">Arabic</option>
                                        </select>
                                    </div>
                                    <button
                                        className="btn btn-primary"
                                        disabled={profileForm.processing}
                                    >
                                        Update profile
                                    </button>
                                </form>
                            </section>
                        </div>

                        <div className="col-lg-6">
                            <section className="pmc-card p-4 h-100">
                                <div className="pmc-kicker mb-2">Security</div>
                                <h2 className="h4 mb-3">Change password</h2>
                                <p className="text-secondary small">
                                    Use a strong password. If your owner or
                                    manager just created this account, this
                                    clears the temporary-password flag.
                                </p>

                                <form
                                    className="d-grid gap-3"
                                    onSubmit={updatePassword}
                                >
                                    <div>
                                        <label
                                            className="form-label pmc-form-label"
                                            htmlFor="current-password"
                                        >
                                            Current password
                                        </label>
                                        <input
                                            id="current-password"
                                            name="current_password"
                                            type="password"
                                            className="form-control"
                                            value={
                                                passwordForm.data
                                                    .current_password
                                            }
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'current_password',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                        {profile.force_password_reset ? (
                                            <div className="form-text">
                                                Optional while temporary
                                                password reset is active.
                                            </div>
                                        ) : null}
                                        <FieldError
                                            message={
                                                passwordForm.errors
                                                    .current_password
                                            }
                                        />
                                    </div>
                                    <div>
                                        <label
                                            className="form-label pmc-form-label"
                                            htmlFor="new-password"
                                        >
                                            New password
                                        </label>
                                        <input
                                            id="new-password"
                                            name="password"
                                            type="password"
                                            className="form-control"
                                            value={passwordForm.data.password}
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'password',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                        <FieldError
                                            message={
                                                passwordForm.errors.password
                                            }
                                        />
                                    </div>
                                    <div>
                                        <label
                                            className="form-label pmc-form-label"
                                            htmlFor="password-confirmation"
                                        >
                                            Confirm password
                                        </label>
                                        <input
                                            id="password-confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            className="form-control"
                                            value={
                                                passwordForm.data
                                                    .password_confirmation
                                            }
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'password_confirmation',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <button
                                        className="btn btn-warning"
                                        disabled={passwordForm.processing}
                                    >
                                        Update password
                                    </button>
                                </form>
                            </section>
                        </div>

                        <div className="col-12">
                            <section className="pmc-card p-4">
                                <div className="pmc-kicker mb-2">
                                    Access context
                                </div>
                                <div className="row g-3">
                                    <ContextCard
                                        icon="bi-buildings"
                                        title="Portfolio"
                                        value={
                                            profile.portfolio?.name_en ??
                                            'Global system account'
                                        }
                                        detail={
                                            profile.portfolio
                                                ? `${profile.portfolio.code} · ${profile.portfolio.status}`
                                                : 'Superadmin accounts are not tied to one portfolio.'
                                        }
                                    />
                                    <ContextCard
                                        icon="bi-person-badge"
                                        title="Tenant profile"
                                        value={
                                            profile.tenant_profile
                                                ? profile.tenant_profile
                                                      .profile_type
                                                : 'No tenant profile'
                                        }
                                        detail={
                                            profile.tenant_profile
                                                ? profile.tenant_profile.status
                                                : 'Owners and managers usually do not have tenant profiles.'
                                        }
                                    />
                                    <ContextCard
                                        icon="bi-shield-check"
                                        title="Password state"
                                        value={
                                            profile.force_password_reset
                                                ? 'Temporary'
                                                : 'Confirmed'
                                        }
                                        detail={
                                            profile.force_password_reset
                                                ? 'Change it before continuing.'
                                                : 'No reset is currently required.'
                                        }
                                    />
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="d-flex justify-content-between gap-3 border-bottom pb-2">
            <span className="text-secondary">{label}</span>
            <strong className="text-end">{value}</strong>
        </div>
    );
}

function ContextCard({
    icon,
    title,
    value,
    detail,
}: {
    icon: string;
    title: string;
    value: string;
    detail: string;
}) {
    return (
        <div className="col-md-4">
            <div className="pmc-profile-context-card">
                <i className={`bi ${icon}`} />
                <span>{title}</span>
                <strong>{value}</strong>
                <small>{detail}</small>
            </div>
        </div>
    );
}

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <div className="text-danger small mt-1">{message}</div>;
}

function formatDate(value?: string | null) {
    if (!value) {
        return 'Not recorded';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
