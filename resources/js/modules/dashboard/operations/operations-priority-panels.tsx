import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import { DashboardRecordList } from '../shared/record-list';
import type { OperationsDashboardProps } from '../types';

export function OperationsPriorityPanels({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, text } = useTranslator();

    return (
        <div className="pmc-command-grid">
            <WorkspacePanel
                eyebrow="Collections"
                title="Outstanding balances"
                description="Largest balances that should be reviewed first."
                action={{ label: 'Open payments', href: '/payments' }}
            >
                <DashboardRecordList
                    empty="No outstanding balances."
                    rows={props.arrearsLeases.slice(0, 5).map((lease) => ({
                        href: `/leases/${lease.id}`,
                        title: lease.code,
                        meta: `${lease.tenant ?? text('No tenant')} · ${lease.asset ?? text('No asset')}`,
                        value: currency(
                            lease.arrears_amount,
                            props.app.locale,
                            lease.currency,
                        ),
                        tone: 'danger',
                    }))}
                />
            </WorkspacePanel>

            <WorkspacePanel
                eyebrow="Service"
                title="Maintenance queue"
                description="Latest requests across the properties in scope."
                action={{
                    label: 'Open queue',
                    href: '/maintenance-requests',
                }}
            >
                <DashboardRecordList
                    empty="No maintenance requests."
                    rows={props.recentMaintenance
                        .slice(0, 5)
                        .map((request) => ({
                            href: `/maintenance-requests/${request.id}`,
                            title: request.title,
                            meta:
                                (locale === 'ar'
                                    ? request.asset?.title_ar ||
                                      request.asset?.title_en
                                    : request.asset?.title_en ||
                                      request.asset?.title_ar) ??
                                text('No asset'),
                            value: request.status,
                            status: request.status,
                        }))}
                />
            </WorkspacePanel>
        </div>
    );
}
