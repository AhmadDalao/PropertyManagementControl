import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { AuthField } from './auth-field';

export function LoginForm({ status }: { status?: string | null }) {
    const { t } = useTranslator();
    const form = useForm({
        email: '',
        password: '',
        remember: true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/login', {
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <>
            {status ? (
                <div className="alert alert-success" role="status">
                    {status}
                </div>
            ) : null}

            <form onSubmit={submit} className="d-grid gap-3">
                <AuthField
                    id="login-email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    label={t('login.email')}
                    value={form.data.email}
                    error={form.errors.email}
                    onChange={(value) => form.setData('email', value)}
                />
                <AuthField
                    id="login-password"
                    name="password"
                    type="password"
                    autoComplete="current-password"
                    label={t('login.password')}
                    value={form.data.password}
                    error={form.errors.password}
                    onChange={(value) => form.setData('password', value)}
                />

                <div className="text-end">
                    <Link href="/account-recovery">
                        {t('login.forgot_password')}
                    </Link>
                </div>

                <div className="form-check">
                    <input
                        id="remember"
                        name="remember"
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
                    <label className="form-check-label" htmlFor="remember">
                        {t('login.remember')}
                    </label>
                </div>

                <button
                    type="submit"
                    className="btn btn-primary btn-lg"
                    disabled={form.processing}
                >
                    {form.processing
                        ? t('login.signing_in')
                        : t('login.sign_in')}
                </button>
            </form>
        </>
    );
}
