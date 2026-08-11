import { Link } from '@inertiajs/react';

import { TablePagination } from '@/components/data-table/table-pagination';
import { useTranslator } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import type { TenantDocumentsPageProps } from './types';

export function DocumentRecords({
    props,
}: {
    props: TenantDocumentsPageProps;
}) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-portal-panel pmc-portal-register">
            <header>
                <div>
                    <span>{t('tenant_portal.secure_files')}</span>
                    <h2>{t('tenant_portal.my_documents')}</h2>
                </div>
                <small>{t('tenant_portal.documents_are_private')}</small>
            </header>
            <div className="pmc-portal-document-grid">
                {props.documents.data.map((document) => (
                    <article key={document.id}>
                        <div className="pmc-portal-document-icon">
                            <i className="bi bi-file-earmark-pdf" />
                        </div>
                        <div>
                            <span>
                                {t(`documents.options.${document.type}`)}
                            </span>
                            <h3>
                                {locale === 'ar'
                                    ? document.title_ar || document.title_en
                                    : document.title_en || document.title_ar}
                            </h3>
                            <p>
                                {document.attachment.label} ·{' '}
                                {humanDate(document.issued_on, locale)}
                            </p>
                        </div>
                        <div className="pmc-portal-document-actions">
                            {document.attachment.url ? (
                                <Link
                                    href={document.attachment.url}
                                    aria-label={t('actions.open')}
                                >
                                    <i className="bi bi-arrow-up-right" />
                                </Link>
                            ) : null}
                            <a
                                href={document.download_url}
                                aria-label={t('actions.download')}
                            >
                                <i className="bi bi-download" />
                            </a>
                        </div>
                    </article>
                ))}
            </div>
            <TablePagination data={props.documents} />
        </section>
    );
}
