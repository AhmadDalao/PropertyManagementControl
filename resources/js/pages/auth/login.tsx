import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { LanguageSwitcher } from '@/components/language-switcher';

export default function LoginPage() {
    const form = useForm({
        email: '',
        password: '',
        remember: true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/login');
    };

    return (
        <>
            <Head title="Login" />
            <div className="pmc-auth-page">
                <div className="container">
                    <div className="pmc-auth-topbar">
                        <Link href="/" className="pmc-public-brand">
                            <span>PMC</span>
                            <strong>Property Management Control</strong>
                        </Link>
                        <LanguageSwitcher />
                    </div>

                    <div className="row g-4 g-xl-5 align-items-center">
                        <div className="col-lg-6">
                            <div className="pmc-auth-copy">
                                <div className="pmc-kicker mb-3">
                                    Secure portal
                                </div>
                                <h1>
                                    One login for owners, managers, tenants, and
                                    system control.
                                </h1>
                                <p>
                                    Access portfolio dashboards, rent balances,
                                    contracts, payment receipts, maintenance
                                    requests, and website control based on your
                                    role.
                                </p>
                                <div className="pmc-auth-points">
                                    <span>
                                        <i className="bi bi-shield-check" />
                                        Role-based access
                                    </span>
                                    <span>
                                        <i className="bi bi-translate" />
                                        English and Arabic
                                    </span>
                                    <span>
                                        <i className="bi bi-file-earmark-lock" />
                                        Private documents
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="col-lg-5 offset-lg-1">
                            <div className="pmc-card pmc-login-panel p-4 p-lg-5 mx-auto">
                                <div className="pmc-kicker mb-3">
                                    Secure access
                                </div>
                                <h1 className="pmc-page-title mb-3">
                                    Property Control Login
                                </h1>
                                <p className="text-secondary mb-4">
                                    Sign in with the account created for you by
                                    the system owner or property owner.
                                </p>

                                <form
                                    onSubmit={submit}
                                    className="d-grid gap-3"
                                >
                                    <div>
                                        <label className="form-label pmc-form-label">
                                            Email
                                        </label>
                                        <input
                                            type="email"
                                            autoComplete="email"
                                            className="form-control form-control-lg"
                                            required
                                            value={form.data.email}
                                            onChange={(event) =>
                                                form.setData(
                                                    'email',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                        {form.errors.email ? (
                                            <div className="text-danger small mt-1">
                                                {form.errors.email}
                                            </div>
                                        ) : null}
                                    </div>

                                    <div>
                                        <label className="form-label pmc-form-label">
                                            Password
                                        </label>
                                        <input
                                            type="password"
                                            autoComplete="current-password"
                                            className="form-control form-control-lg"
                                            required
                                            value={form.data.password}
                                            onChange={(event) =>
                                                form.setData(
                                                    'password',
                                                    event.currentTarget.value,
                                                )
                                            }
                                        />
                                        {form.errors.password ? (
                                            <div className="text-danger small mt-1">
                                                {form.errors.password}
                                            </div>
                                        ) : null}
                                    </div>

                                    <div className="form-check">
                                        <input
                                            id="remember"
                                            type="checkbox"
                                            className="form-check-input"
                                            checked={form.data.remember}
                                            onChange={(event) =>
                                                form.setData(
                                                    'remember',
                                                    event.currentTarget.checked,
                                                )
                                            }
                                        />
                                        <label
                                            className="form-check-label"
                                            htmlFor="remember"
                                        >
                                            Keep me signed in
                                        </label>
                                    </div>

                                    <button
                                        type="submit"
                                        className="btn btn-primary btn-lg"
                                        disabled={form.processing}
                                    >
                                        {form.processing
                                            ? 'Signing in...'
                                            : 'Sign in'}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
