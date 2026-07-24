import { Head, usePage } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

import { AuthShell } from './auth-shell';
import { LoginForm } from './login-form';

export default function LoginPage() {
    const { t } = useTranslator();
    const { status } = usePage<SharedProps>().props.flash;

    return (
        <>
            <Head title={t('login.title')} />
            <AuthShell
                kicker={t('login.secure_access')}
                title={t('login.title')}
                description={t('login.form_description')}
            >
                <LoginForm status={status} />
            </AuthShell>
        </>
    );
}
