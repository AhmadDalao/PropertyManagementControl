import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/profile.css';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { ProfileAccessContext } from './profile-access-context';
import { ProfileDetailsForm } from './profile-details-form';
import { ProfileHeader } from './profile-header';
import { ProfilePasswordForm } from './profile-password-form';
import { ProfileSummary } from './profile-summary';
import type { ProfilePageProps } from './types';

export default function ProfilePage() {
    const { props } = usePage<ProfilePageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('profile.title')} />
            <div className="pmc-profile-page">
                <ProfileHeader profile={props.profile} />
                <ProfileSummary profile={props.profile} />
                <div className="pmc-profile-form-grid">
                    <ProfileDetailsForm profile={props.profile} />
                    <ProfilePasswordForm
                        forcePasswordReset={props.profile.force_password_reset}
                    />
                </div>
                <ProfileAccessContext profile={props.profile} />
            </div>
        </AdminLayout>
    );
}
