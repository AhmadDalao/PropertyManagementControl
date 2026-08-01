import { Head, usePage } from '@inertiajs/react';

import { ResourceHeader } from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/users/portal-access.css';
import { PortalAccessAccountCard } from './portal-access-account-card';
import { PortalAccessGenerator } from './portal-access-generator';
import { PortalAccessSteps } from './portal-access-steps';
import type { PortalAccessPageProps } from './portal-access-types';

export default function PortalAccessPage() {
    const { props } = usePage<PortalAccessPageProps>();
    const { t } = useTranslator();
    const access = props.portalAccess;

    return (
        <AdminLayout>
            <Head title={access.header.title} />
            <ResourceHeader {...access.header} />

            <main className="pmc-portal-access-layout">
                <section className="pmc-portal-access-primary">
                    <PortalAccessGenerator
                        endpoint={access.endpoint}
                        canGenerate={access.canGenerate}
                        expiresInMinutes={access.expiresInMinutes}
                    />
                    <PortalAccessSteps />
                </section>
                <aside
                    className="pmc-portal-access-aside"
                    aria-label={t('users.portal_access_account_summary')}
                >
                    <PortalAccessAccountCard account={access.account} />
                </aside>
            </main>
        </AdminLayout>
    );
}
