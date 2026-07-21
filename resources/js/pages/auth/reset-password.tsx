import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { AuthShell } from '@/components/auth-shell';
import { useTranslator } from '@/lib/i18n';

export default function ResetPasswordPage({
    email,
    token,
}: {
    email: string;
    token: string;
}) {
    const { t } = useTranslator();
    const form = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/reset-password', {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title={t('login.reset_password')} />
            <AuthShell
                kicker={t('login.account_recovery')}
                title={t('login.reset_password')}
                description={t('login.choose_password')}
            >
                <form className="d-grid gap-3" onSubmit={submit}>
                    <div>
                        <label
                            className="form-label pmc-form-label"
                            htmlFor="reset-email"
                        >
                            {t('login.email')}
                        </label>
                        <input
                            id="reset-email"
                            type="email"
                            className="form-control form-control-lg"
                            autoComplete="email"
                            required
                            value={form.data.email}
                            aria-invalid={Boolean(form.errors.email)}
                            onChange={(event) =>
                                form.setData('email', event.currentTarget.value)
                            }
                        />
                        {form.errors.email ? (
                            <div className="text-danger small mt-1">
                                {form.errors.email}
                            </div>
                        ) : null}
                    </div>
                    <PasswordField
                        id="reset-password"
                        label={t('login.new_password')}
                        value={form.data.password}
                        error={form.errors.password}
                        onChange={(value) => form.setData('password', value)}
                    />
                    <PasswordField
                        id="reset-password-confirmation"
                        label={t('login.confirm_password')}
                        value={form.data.password_confirmation}
                        error={form.errors.password_confirmation}
                        onChange={(value) =>
                            form.setData('password_confirmation', value)
                        }
                    />
                    <button
                        type="submit"
                        className="btn btn-primary btn-lg"
                        disabled={form.processing}
                    >
                        {form.processing
                            ? t('login.resetting_password')
                            : t('login.reset_password')}
                    </button>
                    <Link href="/login" className="btn btn-link">
                        {t('login.back_to_login')}
                    </Link>
                </form>
            </AuthShell>
        </>
    );
}

function PasswordField({
    id,
    label,
    value,
    error,
    onChange,
}: {
    id: string;
    label: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
}) {
    return (
        <div>
            <label className="form-label pmc-form-label" htmlFor={id}>
                {label}
            </label>
            <input
                id={id}
                type="password"
                className="form-control form-control-lg"
                autoComplete="new-password"
                required
                value={value}
                aria-invalid={Boolean(error)}
                onChange={(event) => onChange(event.currentTarget.value)}
            />
            {error ? (
                <div className="text-danger small mt-1">{error}</div>
            ) : null}
        </div>
    );
}
