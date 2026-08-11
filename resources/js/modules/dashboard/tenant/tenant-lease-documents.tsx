import { WorkspacePanel, humanLabel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantLeaseDocuments({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const { t } = useTranslator();
    const lease = props.tenantPortal.lease;
    const documents = props.tenantPortal.documents;
    const isArabic = props.app.locale === 'ar';
    const currencyCode = lease?.currency ?? 'SAR';

    return (
        <WorkspacePanel
            eyebrow={t('dashboard.tenant_contract_eyebrow')}
            title={t('dashboard.tenant_lease_documents')}
            description={t('dashboard.tenant_lease_documents_description')}
            action={{ label: t('actions.view_all'), href: '/my-lease' }}
        >
            <div className="pmc-tenant-lease-summary">
                <div>
                    <span>{t('tenant_portal.start_date')}</span>
                    <strong>
                        {humanDate(lease?.started_at, props.app.locale)}
                    </strong>
                </div>
                <div>
                    <span>{t('tenant_portal.end_date')}</span>
                    <strong>
                        {humanDate(lease?.ends_at, props.app.locale)}
                    </strong>
                </div>
                <div>
                    <span>{t('tenant_portal.monthly_rent')}</span>
                    <strong>
                        {currency(
                            lease?.rent_amount ?? 0,
                            props.app.locale,
                            currencyCode,
                        )}
                    </strong>
                </div>
                <div>
                    <span>{t('dashboard.contract_balance')}</span>
                    <strong>
                        {currency(
                            lease?.balance_remaining ?? 0,
                            props.app.locale,
                            currencyCode,
                        )}
                    </strong>
                </div>
            </div>

            {lease ? (
                <div className="pmc-tenant-document-actions">
                    <a href={lease.contract_url}>
                        <i className="bi bi-file-earmark-pdf" />
                        {t('tenant_portal.lease_contract')}
                    </a>
                    <a href={lease.statement_url}>
                        <i className="bi bi-receipt" />
                        {t('tenant_portal.tenant_statement')}
                    </a>
                </div>
            ) : null}

            <div className="pmc-tenant-document-list">
                {documents.length > 0 ? (
                    documents.slice(0, 5).map((document) => (
                        <a key={document.id} href={document.download_url}>
                            <i className="bi bi-file-earmark-pdf" />
                            <div>
                                <strong>
                                    {(isArabic
                                        ? document.title_ar
                                        : document.title_en) ??
                                        document.title_en}
                                </strong>
                                <span>
                                    {t(
                                        `documents.options.${document.type}`,
                                        humanLabel(document.type),
                                    )}
                                </span>
                            </div>
                            <i className="bi bi-download" />
                        </a>
                    ))
                ) : (
                    <div className="pmc-command-empty">
                        {t('tenant_portal.empty_document_description')}
                    </div>
                )}
            </div>
        </WorkspacePanel>
    );
}
