import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { AuthField } from './auth-field';

export function ForgotPasswordForm({ status }: { status?: string | null }) {
    const { t } = useTranslator();
    const form = useForm({ email: '' });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/account-recovery');
    };

    return (
        <>
            {status ? (
                <div className="alert alert-success" role="status">
                    {status}
                </div>
            ) : null}
            <form className="d-grid gap-3" onSubmit={submit}>
                <AuthField
                    id="recovery-email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    label={t('login.email')}
                    value={form.data.email}
                    error={form.errors.email}
                    onChange={(value) => form.setData('email', value)}
                />
                <button
                    type="submit"
                    className="btn btn-primary btn-lg"
                    disabled={form.processing}
                >
                    {form.processing
                        ? t('login.sending_reset')
                        : t('login.send_reset')}
                </button>
                <Link href="/login" className="btn btn-link">
                    {t('login.back_to_login')}
                </Link>
            </form>
        </>
    );
}
