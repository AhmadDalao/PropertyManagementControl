import { useTranslator } from '@/lib/i18n';

import { SavedReportGuidanceCard } from './saved-report-guidance-card';
import { SavedReportOutputCard } from './saved-report-output-card';
import type { SavedReportDetailPage } from './types';

export function SavedReportOutputPanel({
    detail,
}: {
    detail: SavedReportDetailPage;
}) {
    const { t } = useTranslator();

    return (
        <div className="pmc-saved-report-output-layout">
            <main>
                <section className="pmc-saved-report-output-section">
                    <header>
                        <div>
                            <span>{t('reports.available_outputs')}</span>
                            <h2>{t('reports.saved_report_files_title')}</h2>
                            <p>{t('reports.available_outputs_help')}</p>
                        </div>
                        <strong>{detail.outputs.length}</strong>
                    </header>
                    <div className="pmc-saved-report-output-grid">
                        {detail.outputs.map((output) => (
                            <SavedReportOutputCard
                                output={output}
                                key={output.id}
                            />
                        ))}
                    </div>
                </section>
            </main>
            <aside>
                <SavedReportGuidanceCard notice={detail.notices.outputs} />
            </aside>
        </div>
    );
}
