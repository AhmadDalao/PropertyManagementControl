import { Head } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { OperationsActionQueue } from '../operations/action-queue';
import { OperationsHeader } from '../operations/operations-header';
import { OperationsInsightPanels } from '../operations/operations-insight-panels';
import { OperationsMetrics } from '../operations/operations-metrics';
import { OperationsPriorityPanels } from '../operations/operations-priority-panels';
import { PlatformStatusPanel } from '../operations/platform-status-panel';
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
            <OperationsHeader mode={props.mode} />
            <OperationsMetrics props={props} />
            <OperationsActionQueue actions={props.nextActions} />
            <OperationsPriorityPanels props={props} />
            <OperationsInsightPanels props={props} />
            {props.mode === 'superadmin' && props.cmsStatus ? (
                <PlatformStatusPanel status={props.cmsStatus} />
            ) : null}
        </AdminLayout>
    );
}
