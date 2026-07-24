import { Head } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import { AuthShell } from './auth-shell';
import { ResetPasswordForm } from './reset-password-form';

export default function ResetPasswordPage({
    email,
    token,
}: {
    email: string;
    token: string;
}) {
    const { t } = useTranslator();

    return (
        <>
            <Head title={t('login.reset_password')} />
            <AuthShell
                kicker={t('login.account_recovery')}
                title={t('login.reset_password')}
                description={t('login.choose_password')}
            >
                <ResetPasswordForm email={email} token={token} />
            </AuthShell>
        </>
    );
}
