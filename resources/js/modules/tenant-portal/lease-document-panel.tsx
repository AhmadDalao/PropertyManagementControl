import { useTranslator } from '@/lib/i18n';

import type { TenantLeasePageProps } from './types';

export function LeaseDocumentPanel({ props }: { props: TenantLeasePageProps }) {
    const { locale, t } = useTranslator();
    const lease = props.lease;

    if (!lease) {
        return null;
    }

    const generated = [
        {
            href: lease.contract_url,
            icon: 'bi-file-earmark-pdf',
            title: t('tenant_portal.lease_contract'),
            type: 'PDF',
        },
        {
            href: lease.contract_word_url,
            icon: 'bi-file-earmark-word',
            title: t('tenant_portal.lease_contract'),
            type: 'DOCX',
        },
        {
            href: lease.statement_url,
            icon: 'bi-file-earmark-text',
            title: t('tenant_portal.tenant_statement'),
            type: 'PDF',
        },
    ];

    return (
        <section className="pmc-portal-panel pmc-portal-downloads">
            <header>
                <div>
                    <span>{t('tenant_portal.files')}</span>
                    <h2>{t('tenant_portal.lease_documents')}</h2>
                </div>
            </header>
            {generated.map((document) => (
                <Download
                    key={`${document.href}-${document.type}`}
                    {...document}
                />
            ))}
            {props.documents.map((document) => (
                <Download
                    href={document.download_url}
                    icon="bi-file-earmark-pdf"
                    key={document.id}
                    title={
                        locale === 'ar'
                            ? document.title_ar || document.title_en
                            : document.title_en || document.title_ar || ''
                    }
                    type={t(`documents.options.${document.type}`)}
                />
            ))}
        </section>
    );
}

function Download({
    href,
    icon,
    title,
    type,
}: {
    href: string;
    icon: string;
    title: string;
    type: string;
}) {
    return (
        <a href={href}>
            <i className={`bi ${icon}`} />
            <span>
                <strong>{title}</strong>
                <small>{type}</small>
            </span>
            <i className="bi bi-download" />
        </a>
    );
}
