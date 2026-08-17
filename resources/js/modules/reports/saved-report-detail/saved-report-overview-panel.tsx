import { DetailCard } from '@/components/resource-cycle/detail-card';
import { useTranslator } from '@/lib/i18n';

import { SavedReportNextStepCard } from './saved-report-next-step-card';
import type { SavedReportDetailPage } from './types';

export function SavedReportOverviewPanel({
    detail,
}: {
    detail: SavedReportDetailPage;
}) {
    const { t } = useTranslator();
    const identity = detail.sections.find(
        (section) => section.key === 'identity',
    );

    return (
        <div className="pmc-saved-report-overview-grid">
            <main>
                <article className="pmc-saved-report-summary">
                    <span aria-hidden="true">
                        <i className="bi bi-bar-chart-line" />
                    </span>
                    <div>
                        <small>{t('reports.controlled_reporting_view')}</small>
                        <h2>{detail.header.title}</h2>
                        <p>{detail.header.description}</p>
                    </div>
                </article>
                {identity ? <DetailCard section={identity} /> : null}
            </main>
            <aside>
                <SavedReportNextStepCard workflow={detail.workflow} />
            </aside>
        </div>
    );
}
