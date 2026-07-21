import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import type { UserIndexPageProps } from './types';
import { UserMetrics } from './user-metrics';
import { UserTable } from './user-table';

export default function UsersIndexPage() {
    const { props } = usePage<UserIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('users.title')} />

            <WorkspaceHeader
                eyebrow={t('users.workspace_eyebrow')}
                title={t('users.title')}
                description={t('users.workspace_description')}
                actions={[
                    {
                        label: t('users.create_user'),
                        href: '/users/create',
                        icon: 'bi-person-plus',
                        tone: 'primary',
                    },
                ]}
            />

            <UserMetrics insights={props.userInsights} />
            <UserTable {...props} />
        </AdminLayout>
    );
}
