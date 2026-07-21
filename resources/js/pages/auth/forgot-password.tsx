import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { AuthShell } from '@/components/auth-shell';
import { useTranslator } from '@/lib/i18n';

export default function ForgotPasswordPage({
    status,
}: {
    status?: string | null;
}) {
    const { t } = useTranslator();
    const form = useForm({ email: '' });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/forgot-password');
    };

    return (
        <>
            <Head title={t('login.forgot_password')} />
            <AuthShell
                kicker={t('login.account_recovery')}
                title={t('login.forgot_password')}
                description={t('login.reset_description')}
            >
                {status ? (
                    <div className="alert alert-success" role="status">
                        {status}
                    </div>
                ) : null}
                <form className="d-grid gap-3" onSubmit={submit}>
                    <div>
                        <label
                            className="form-label pmc-form-label"
                            htmlFor="recovery-email"
                        >
                            {t('login.email')}
                        </label>
                        <input
                            id="recovery-email"
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
            </AuthShell>
        </>
    );
}
