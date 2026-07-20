import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { humanLabel } from '@/components/operations';
import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
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
    const { locale, t, text } = useTranslator();

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
            <Head title={text('Profile')} />
            <PageHeader
                title="Profile"
                description="Control your account details, portal language, and password without touching user-management records."
                actions={
                    <Link href="/documentation" className="btn btn-primary">
                        {t('profile.account_guide')}
                    </Link>
                }
            />

            {profile.force_password_reset ? (
                <div className="alert alert-warning mb-4 border-0">
                    <strong>{t('profile.temporary_password')}</strong>{' '}
                    {t('profile.temporary_password_description')}
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
                                    {t('profile.signed_in_account')}
                                </div>
                                <h2 className="h4 mb-1">{profile.name}</h2>
                                <p className="text-secondary mb-0">
                                    {profile.email}
                                </p>
                            </div>
                        </div>

                        <div className="d-grid gap-3">
                            <InfoRow
                                label={text('Status')}
                                value={t(
                                    `status.${profile.status}`,
                                    text(humanLabel(profile.status)),
                                )}
                            />
                            <InfoRow
                                label={text('Role')}
                                value={
                                    profile.roles
                                        .map((role) =>
                                            t(
                                                `roles.${role}`,
                                                text(humanLabel(role)),
                                            ),
                                        )
                                        .join(' / ') || text('User')
                                }
                            />
                            <InfoRow
                                label={text('Language')}
                                value={
                                    profile.preferred_locale === 'ar'
                                        ? text('Arabic')
                                        : text('English')
                                }
                            />
                            <InfoRow
                                label={text('Last login')}
                                value={formatDate(
                                    profile.last_login_at,
                                    locale,
                                    t('profile.not_recorded'),
                                )}
                            />
                        </div>
                    </section>
                </div>

                <div className="col-xl-8">
                    <div className="row g-4">
                        <div className="col-lg-6">
                            <section className="pmc-card p-4 h-100">
                                <div className="pmc-kicker mb-2">
                                    {t('profile.details')}
                                </div>
                                <h2 className="h4 mb-3">
                                    {t('profile.details_title')}
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
                                            {text('Name')}
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
                                            {text('Email')}
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
                                            {t('profile.email_notice')}
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            className="form-label pmc-form-label"
                                            htmlFor="profile-phone"
                                        >
                                            {text('Phone')}
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
                                            {t('profile.portal_language')}
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
                                            <option value="en">
                                                {text('English')}
                                            </option>
                                            <option value="ar">
                                                {text('Arabic')}
                                            </option>
                                        </select>
                                    </div>
                                    <button
                                        className="btn btn-primary"
                                        disabled={profileForm.processing}
                                    >
                                        {t('profile.update_profile')}
                                    </button>
                                </form>
                            </section>
                        </div>

                        <div className="col-lg-6">
                            <section className="pmc-card p-4 h-100">
                                <div className="pmc-kicker mb-2">
                                    {t('profile.security')}
                                </div>
                                <h2 className="h4 mb-3">
                                    {t('profile.change_password')}
                                </h2>
                                <p className="text-secondary small">
                                    {t('profile.password_notice')}
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
                                            {t('profile.current_password')}
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
                                                {t(
                                                    'profile.current_password_optional',
                                                )}
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
                                            {t('profile.new_password')}
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
                                            {t('profile.confirm_password')}
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
                                        {t('profile.update_password')}
                                    </button>
                                </form>
                            </section>
                        </div>

                        <div className="col-12">
                            <section className="pmc-card p-4">
                                <div className="pmc-kicker mb-2">
                                    {t('profile.access_context')}
                                </div>
                                <div className="row g-3">
                                    <ContextCard
                                        icon="bi-buildings"
                                        title={text('Portfolio')}
                                        value={
                                            (locale === 'ar'
                                                ? profile.portfolio?.name_ar ||
                                                  profile.portfolio?.name_en
                                                : profile.portfolio?.name_en ||
                                                  profile.portfolio?.name_ar) ??
                                            t('profile.global_account')
                                        }
                                        detail={
                                            profile.portfolio
                                                ? `${profile.portfolio.code} · ${t(
                                                      `status.${profile.portfolio.status}`,
                                                      text(
                                                          humanLabel(
                                                              profile.portfolio
                                                                  .status,
                                                          ),
                                                      ),
                                                  )}`
                                                : t(
                                                      'profile.global_account_description',
                                                  )
                                        }
                                    />
                                    <ContextCard
                                        icon="bi-person-badge"
                                        title={text('Tenant profile')}
                                        value={
                                            profile.tenant_profile
                                                ? text(
                                                      humanLabel(
                                                          profile.tenant_profile
                                                              .profile_type,
                                                      ),
                                                  )
                                                : t('profile.no_tenant_profile')
                                        }
                                        detail={
                                            profile.tenant_profile
                                                ? t(
                                                      `status.${profile.tenant_profile.status}`,
                                                      text(
                                                          humanLabel(
                                                              profile
                                                                  .tenant_profile
                                                                  .status,
                                                          ),
                                                      ),
                                                  )
                                                : t(
                                                      'profile.no_tenant_profile_description',
                                                  )
                                        }
                                    />
                                    <ContextCard
                                        icon="bi-shield-check"
                                        title={t('profile.password_state')}
                                        value={
                                            profile.force_password_reset
                                                ? t('profile.temporary')
                                                : t('profile.confirmed')
                                        }
                                        detail={
                                            profile.force_password_reset
                                                ? t(
                                                      'profile.change_before_continuing',
                                                  )
                                                : t('profile.no_reset_required')
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

function formatDate(
    value?: string | null,
    locale: string = 'en',
    fallback: string = 'Not recorded',
) {
    if (!value) {
        return fallback;
    }

    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SA' : 'en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
