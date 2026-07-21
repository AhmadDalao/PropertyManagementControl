import { WorkspacePanel, humanLabel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { BreakdownCards, ReportRecordSection } from './report-visuals';
import type { ReportsPageProps } from './types';

export function ReportOperations({ props }: { props: ReportsPageProps }) {
    const { t, text } = useTranslator();

    return (
        <>
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
