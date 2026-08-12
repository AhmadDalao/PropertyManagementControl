import { Head } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { TenantHomeCommandCenter } from '../tenant/tenant-home-command-center';
import type { TenantDashboardProps } from '../types';

export function TenantDashboard({ props }: { props: TenantDashboardProps }) {
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('dashboard.tenant_dashboard')} />
            <TenantHomeCommandCenter props={props} />
        </AdminLayout>
    );
}
