import {
    MetricGrid,
    WorkspacePanel,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { ReportJournal } from './report-journal';
import { BreakdownCards, ReportRecordSection } from './report-visuals';
import type { ReportsPageProps } from './types';

export function ReportOperations({ props }: { props: ReportsPageProps }) {
    const { locale, t, text } = useTranslator();

    return (
        <>
            <MetricGrid
                metrics={[
                    {
                        label: t('reports.journal_total'),
                        value: localizedNumber(
                            props.journalSummary.totalEvents,
                            locale,
                        ),
                        detail: t('reports.journal_total_help'),
                        icon: 'bi-clock-history',
                        tone: 'ink',
                    },
                    {
                        label: t('reports.journal_new_leases'),
                        value: localizedNumber(
                            props.journalSummary.newLeases,
                            locale,
                        ),
                        detail: t('reports.journal_new_leases_help'),
                        icon: 'bi-file-earmark-text',
                        tone: 'blue',
                        href: '/leases',
                    },
                    {
                        label: t('reports.journal_service_activity'),
                        value: localizedNumber(
                            props.journalSummary.serviceOpened +
                                props.journalSummary.serviceResolved,
                            locale,
                        ),
                        detail: t(
                            'reports.journal_service_activity_help',
                            undefined,
                            {
                                opened: localizedNumber(
                                    props.journalSummary.serviceOpened,
                                    locale,
                                ),
                                resolved: localizedNumber(
                                    props.journalSummary.serviceResolved,
                                    locale,
                                ),
                            },
                        ),
                        icon: 'bi-tools',
                        tone:
                            props.journalSummary.serviceOpened > 0
                                ? 'amber'
                                : 'teal',
                        href: '/maintenance-requests',
                    },
                    {
                        label: t('reports.journal_documents'),
                        value: localizedNumber(
                            props.journalSummary.documentsAdded,
                            locale,
                        ),
                        detail: t('reports.journal_documents_help'),
                        icon: 'bi-file-earmark-pdf',
                        tone: 'teal',
                        href: '/documents',
                    },
                ]}
            />

            <ReportJournal events={props.operationalJournal} />

            <div className="pmc-report-breakdown-grid">
                <WorkspacePanel
                    eyebrow={t('reports.portfolio_eyebrow')}
                    title={t('reports.asset_mix')}
                    description={t('reports.asset_mix_description')}
                >
                    <BreakdownCards source={props.charts.assetMix} />
                </WorkspacePanel>
                <WorkspacePanel
                    eyebrow={t('reports.service_eyebrow')}
                    title={t('reports.maintenance_status')}
                    description={t('reports.maintenance_status_description')}
                >
                    <BreakdownCards source={props.charts.maintenanceByStatus} />
                </WorkspacePanel>
            </div>
            <div className="pmc-report-record-grid">
                <ReportRecordSection
                    title={t('reports.maintenance_backlog')}
                    description={t('reports.maintenance_backlog_description')}
                    empty={t('reports.no_maintenance_backlog')}
                    rows={props.maintenanceBacklog.map((request) => ({
                        href: `/maintenance-requests/${request.id}`,
                        title: request.title,
                        meta: `${request.asset ?? t('reports.no_asset')} · ${text(humanLabel(request.priority))}`,
                        value: text(humanLabel(request.status)),
                        status: request.status,
                    }))}
                />
            </div>
        </>
    );
}
