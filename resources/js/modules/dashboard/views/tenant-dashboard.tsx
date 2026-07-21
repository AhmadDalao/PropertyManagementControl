import { Head } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { TenantHeader } from '../tenant/tenant-header';
import { TenantLeaseDocuments } from '../tenant/tenant-lease-documents';
import { TenantMaintenancePanel } from '../tenant/tenant-maintenance-panel';
import { TenantMetrics } from '../tenant/tenant-metrics';
import { TenantPaymentHistory } from '../tenant/tenant-payment-history';
import type { TenantDashboardProps } from '../types';

export function TenantDashboard({ props }: { props: TenantDashboardProps }) {
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('dashboard.tenant_dashboard')} />
            <TenantHeader props={props} />
            <TenantMetrics props={props} />
            <div className="pmc-command-grid">
                <TenantLeaseDocuments props={props} />
                <TenantPaymentHistory props={props} />
            </div>
            <TenantMaintenancePanel props={props} />
        </AdminLayout>
    );
}
