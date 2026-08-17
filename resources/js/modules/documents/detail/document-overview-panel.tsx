import { DetailCard } from '@/components/resource-cycle/detail-card';
import { WorkflowActionPanel } from '@/components/resource-cycle/workflow-action-panel';
import { useTranslator } from '@/lib/i18n';

import type { DocumentDetailPage } from './types';

export function DocumentOverviewPanel({
    detail,
}: {
    detail: DocumentDetailPage;
}) {
    const { t } = useTranslator();
    const identity = detail.sections.find(
        (section) => section.key === 'identity',
    );
    const ownership = detail.sections.find(
        (section) => section.key === 'ownership',
    );

    return (
        <div className="pmc-document-overview-grid">
            <main>
                <article className="pmc-document-file-summary">
                    <span aria-hidden="true">
                        <i className="bi bi-file-earmark-pdf" />
                    </span>
                    <div>
                        <small>{t('documents.protected_pdf')}</small>
                        <h2>{detail.header.title}</h2>
                        <p>{detail.header.description}</p>
                    </div>
                </article>
                {identity ? <DetailCard section={identity} /> : null}
                {ownership ? <DetailCard section={ownership} /> : null}
            </main>
            <aside>
                <WorkflowActionPanel workflow={detail.workflow} />
            </aside>
        </div>
    );
}
