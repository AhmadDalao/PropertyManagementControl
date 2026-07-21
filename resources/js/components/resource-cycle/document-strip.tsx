import { useTranslator } from '@/lib/i18n';

import type { ResourceDocument } from './types';

export function DocumentStrip({
    documents,
}: {
    documents: ResourceDocument[];
}) {
    const { t } = useTranslator();

    return (
        <article className="pmc-card p-4 pmc-side-panel">
            <div className="pmc-kicker mb-2">{t('common.documents')}</div>
            <h2>{t('resource.files_and_downloads')}</h2>
            {documents.length > 0 ? (
                <div className="pmc-document-strip">
                    {documents.map((document) => (
                        <a key={document.id} href={document.href}>
                            <i className="bi bi-file-earmark-arrow-down" />
                            <span>
                                <strong>{document.title}</strong>
                                <small>{document.subtitle}</small>
                            </span>
                            {document.badge ? <em>{document.badge}</em> : null}
                        </a>
                    ))}
                </div>
            ) : (
                <p className="pmc-empty-inline">{t('resource.no_documents')}</p>
            )}
        </article>
    );
}
