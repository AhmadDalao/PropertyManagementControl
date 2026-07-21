import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import type { AppUser } from '@/types/auth';

export function TemporaryPasswordNotice({
    user,
    currentUrl,
}: {
    user: AppUser | null;
    currentUrl: string;
}) {
    const { t } = useTranslator();

    if (!user?.force_password_reset || currentUrl === '/profile') {
        return null;
    }

    return (
        <div className="alert alert-warning d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-4 border-0">
            <div>
                <strong>{t('shell.temporary_password')}</strong>{' '}
                {t('shell.update_password_notice')}
            </div>
            <Link href="/profile" className="btn btn-warning btn-sm">
                {t('shell.open_profile')}
            </Link>
        </div>
    );
}
