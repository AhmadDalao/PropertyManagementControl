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
    const { text } = useTranslator();
    const requests = props.tenantPortal.requests;

    return (
        <WorkspacePanel
            eyebrow="Service"
            title="Maintenance requests"
            description="Track every request you submitted for this rental."
            action={{
                label: 'Submit request',
                href: '/maintenance-requests/create',
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
                        {text('No maintenance requests submitted.')}
                    </div>
                )}
            </div>
        </WorkspacePanel>
    );
}
