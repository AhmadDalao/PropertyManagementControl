import { Link } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { useTranslator } from '@/lib/i18n';

import type { ProfileRecord } from './types';

export function ProfileHeader({ profile }: { profile: ProfileRecord }) {
    const { t } = useTranslator();

    return (
        <>
            <PageHeader
                eyebrow={t('profile.eyebrow')}
                title={t('profile.title')}
                description={t('profile.description')}
                actions={
                    <Link
                        href="/documentation"
                        className="btn btn-outline-secondary"
                    >
                        <i className="bi bi-journal-richtext" />
                        {t('profile.account_guide')}
                    </Link>
                }
            />

            {profile.force_password_reset ? (
                <div className="pmc-profile-alert" role="status">
                    <i className="bi bi-shield-exclamation" />
                    <div>
                        <strong>{t('profile.temporary_password')}</strong>
                        <span>
                            {t('profile.temporary_password_description')}
                        </span>
                    </div>
                </div>
            ) : null}
        </>
    );
}
