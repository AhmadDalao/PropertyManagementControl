import { WorkspacePanel, humanLabel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantLeaseDocuments({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const { t, text } = useTranslator();
    const lease = props.tenantPortal.lease;
    const documents = props.tenantPortal.documents;
    const isArabic = props.app.locale === 'ar';
    const currencyCode = lease?.currency ?? 'SAR';

    return (
        <WorkspacePanel
            eyebrow="Contract"
            title="Lease and documents"
            description="Your contract period and downloadable PDF files."
        >
            <div className="pmc-tenant-lease-summary">
                <div>
                    <span>{text('Starts')}</span>
                    <strong>
                        {humanDate(lease?.started_at, props.app.locale)}
                    </strong>
                </div>
                <div>
                    <span>{text('Ends')}</span>
                    <strong>
                        {humanDate(lease?.ends_at, props.app.locale)}
                    </strong>
                </div>
                <div>
                    <span>{text('Contract rent')}</span>
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
                        {text('Contract PDF')}
                    </a>
                    <a href={lease.statement_url}>
                        <i className="bi bi-receipt" />
                        {text('Tenant statement')}
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
                                <span>{text(humanLabel(document.type))}</span>
                            </div>
                            <i className="bi bi-download" />
                        </a>
                    ))
                ) : (
                    <div className="pmc-command-empty">
                        {text('No contract documents are available.')}
                    </div>
                )}
            </div>
        </WorkspacePanel>
    );
}
