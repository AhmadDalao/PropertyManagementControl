import { Head, usePage } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/action-center.css';
import { ActionCenterFilters } from './action-center-filters';
import { ActionCenterHeader } from './action-center-header';
import { ActionCenterMetrics } from './action-center-metrics';
import { ActionCenterWorkspace } from './action-center-workspace';
import type { ActionCenterPageProps } from './types';

export default function ActionCenterIndexPage() {
    const { props } = usePage<ActionCenterPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('action_center.title')} />
            <div className="pmc-action-center">
                <ActionCenterHeader filters={props.filters} />
                <ActionCenterMetrics
                    filters={props.filters}
                    metrics={props.metrics}
                />
                <ActionCenterFilters {...props} />
                <ActionCenterWorkspace actionItems={props.actionItems} />
            </div>
        </AdminLayout>
    );
}
