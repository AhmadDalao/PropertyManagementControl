import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import { DashboardRecordList } from '../shared/record-list';
import type { OperationsDashboardProps } from '../types';
import { propertyFocusUrl } from './property-focus-url';

export function OperationsPriorityPanels({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t, text } = useTranslator();
    const propertyId = props.propertyFocus.selected?.id;

    return (
        <div className="pmc-command-grid">
            <WorkspacePanel
                eyebrow={t('dashboard.collections')}
                title={t('dashboard.collection_queue')}
                description={t('dashboard.collection_queue_description')}
                action={{
                    label: t('dashboard.open_collections'),
                    href: propertyFocusUrl(
                        '/rent-collection?status=actionable',
                        propertyId,
                    ),
                }}
            >
                <DashboardRecordList
                    empty={t('dashboard.no_collection_work')}
                    rows={props.collectionQueue.slice(0, 5).map((item) => ({
                        href: item.lease_id
                            ? `/payments/create?lease_id=${item.lease_id}`
                            : '/payments/create',
                        title: `${item.tenant ?? text('No tenant')} · ${item.lease_code ?? ''}`,
                        meta: `${(locale === 'ar' ? item.asset_ar || item.asset_en : item.asset_en || item.asset_ar) ?? text('No asset')} · ${humanDate(item.due_date, props.app.locale)}${item.days_overdue > 0 ? ` · ${t('dashboard.days_overdue', undefined, { count: localizedNumber(item.days_overdue, locale) })}` : ''}`,
                        value: currency(
                            item.outstanding_amount,
                            props.app.locale,
                            item.currency,
                        ),
                        tone: item.days_overdue > 0 ? 'danger' : 'warning',
                    }))}
                />
            </WorkspacePanel>

            <WorkspacePanel
                eyebrow={t('dashboard.service')}
                title={t('dashboard.maintenance_queue')}
                description={t('dashboard.maintenance_queue_description')}
                action={{
                    label: t('dashboard.open_queue'),
                    href: propertyFocusUrl(
                        '/maintenance-requests?status=open',
                        propertyId,
                    ),
                }}
            >
                <DashboardRecordList
                    empty={t('dashboard.no_open_maintenance')}
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

            <WorkspacePanel
                eyebrow={t('dashboard.handover')}
                title={t('dashboard.move_out_queue')}
                description={t('dashboard.move_out_queue_description')}
                action={{
                    label: t('dashboard.open_move_outs'),
                    href: propertyFocusUrl(
                        '/lease-move-outs?queue=attention',
                        propertyId,
                    ),
                }}
            >
                <DashboardRecordList
                    empty={t('dashboard.no_move_out_work')}
                    rows={props.moveOutQueue.items.map((moveOut) => ({
                        href: `/leases/${moveOut.lease_id}`,
                        title: `${moveOut.code ?? ''} · ${moveOut.tenant ?? text('No tenant')}`,
                        meta: `${(locale === 'ar' ? moveOut.asset_ar || moveOut.asset_en : moveOut.asset_en || moveOut.asset_ar) ?? text('No asset')} · ${humanDate(moveOut.move_out_date, props.app.locale)}`,
                        value: t(`lease_move_outs.state_${moveOut.state}`),
                        tone:
                            moveOut.state === 'ready'
                                ? 'success'
                                : moveOut.state === 'overdue'
                                  ? 'danger'
                                  : 'warning',
                    }))}
                />
            </WorkspacePanel>
        </div>
    );
}
