import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { AuthField } from './auth-field';

export function ResetPasswordForm({
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
        <form className="d-grid gap-3" onSubmit={submit}>
            <AuthField
                id="reset-email"
                name="email"
                type="email"
                autoComplete="email"
                label={t('login.email')}
                value={form.data.email}
                error={form.errors.email}
                onChange={(value) => form.setData('email', value)}
            />
            <AuthField
                id="reset-password"
                name="password"
                type="password"
                autoComplete="new-password"
                label={t('login.new_password')}
                value={form.data.password}
                error={form.errors.password}
                onChange={(value) => form.setData('password', value)}
            />
            <AuthField
                id="reset-password-confirmation"
                name="password_confirmation"
                type="password"
                autoComplete="new-password"
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
    );
}
