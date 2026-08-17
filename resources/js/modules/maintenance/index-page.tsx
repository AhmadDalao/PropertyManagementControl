import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/maintenance/index.css';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { MaintenanceHeader } from './maintenance-header';
import { MaintenanceMetrics } from './maintenance-metrics';
import { MaintenanceTable } from './maintenance-table';
import type { MaintenanceIndexPageProps } from './types';

export default function MaintenanceIndexPage() {
    const { props } = usePage<MaintenanceIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('maintenance.title')} />

            <MaintenanceHeader {...props} />

            <MaintenanceMetrics {...props} />
            <MaintenanceTable {...props} />
        </AdminLayout>
    );
}
