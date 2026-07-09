import { Head, useForm } from '@inertiajs/react';
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
            <div className="py-5 container">
                <div className="d-flex justify-content-end mb-4">
                    <LanguageSwitcher />
                </div>

                <div className="row justify-content-center">
                    <div className="col-lg-5">
                        <div className="pmc-card pmc-login-panel p-4 p-lg-5 mx-auto">
                            <div className="pmc-kicker mb-3">Secure access</div>
                            <h1 className="pmc-page-title mb-3">
                                Property Control Login
                            </h1>
                            <p className="text-secondary mb-4">
                                Sign in with the account created for you by the
                                system owner or property owner.
                            </p>

                            <form onSubmit={submit} className="d-grid gap-3">
                                <div>
                                    <label className="form-label pmc-form-label">
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        className="form-control form-control-lg"
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
                                        className="form-control form-control-lg"
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
        </>
    );
}
