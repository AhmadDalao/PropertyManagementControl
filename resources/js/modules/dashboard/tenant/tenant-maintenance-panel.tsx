import { Link } from '@inertiajs/react';

import { StatusBadge, WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantMaintenancePanel({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const { t, text } = useTranslator();
    const requests = props.tenantPortal.requests;

    return (
        <WorkspacePanel
            eyebrow={t('dashboard.tenant_service_eyebrow')}
            title={t('dashboard.tenant_maintenance_requests')}
            description={t('dashboard.tenant_maintenance_requests_description')}
            action={{
                label: t('actions.view_all'),
                href: '/maintenance-requests',
            }}
        >
            <div className="pmc-command-list">
                {requests.length > 0 ? (
                    requests.slice(0, 6).map((request) => (
                        <Link
                            key={request.id}
                            href={`/maintenance-requests/${request.id}`}
                        >
                            <div>
                                <strong>{request.title}</strong>
                                <span>
                                    {humanDate(
                                        request.created_at,
                                        props.app.locale,
                                    )}
                                </span>
                            </div>
                            <StatusBadge value={request.status} />
                        </Link>
                    ))
                ) : (
                    <div className="pmc-command-empty">
                        {text(t('dashboard.no_maintenance_requests'))}
                    </div>
                )}
            </div>
        </WorkspacePanel>
    );
}
