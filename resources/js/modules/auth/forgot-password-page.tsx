import { Head } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import { AuthShell } from './auth-shell';
import { ForgotPasswordForm } from './forgot-password-form';

export default function ForgotPasswordPage({
    status,
}: {
    status?: string | null;
}) {
    const { t } = useTranslator();

    return (
        <>
            <Head title={t('login.forgot_password')} />
            <AuthShell
                kicker={t('login.account_recovery')}
                title={t('login.forgot_password')}
                description={t('login.reset_description')}
            >
                <ForgotPasswordForm status={status} />
            </AuthShell>
        </>
    );
}
