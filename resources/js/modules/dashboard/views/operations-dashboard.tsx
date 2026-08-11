import { Head } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { OperationsDashboardGroups } from '../operations/operations-dashboard-groups';
import { OperationsHeader } from '../operations/operations-header';
import { OperationsMetrics } from '../operations/operations-metrics';
import { PortfolioSetupPanel } from '../operations/portfolio-setup-panel';
import { PropertyFocus } from '../operations/property-focus';
import type { OperationsDashboardProps } from '../types';

export function OperationsDashboard({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { text } = useTranslator();

    return (
        <AdminLayout>
            <Head title={text('Dashboard')} />
            <OperationsHeader
                mode={props.mode}
                propertyFocus={props.propertyFocus}
            />
            <PropertyFocus focus={props.propertyFocus} period={props.period} />
            <PortfolioSetupPanel target={props.setupTarget} />
            <OperationsMetrics props={props} />
            <OperationsDashboardGroups props={props} />
        </AdminLayout>
    );
}
