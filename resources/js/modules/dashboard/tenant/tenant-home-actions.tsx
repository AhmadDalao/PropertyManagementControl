import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { TenantDashboardProps } from '../types';

export function TenantHomeActions({ props }: { props: TenantDashboardProps }) {
    const { locale, t } = useTranslator();
    const lease = props.tenantPortal.lease;

    if (!lease) {
        return null;
    }

    return (
        <section className="pmc-tenant-home-actions">
            <header>
                <span>{t('dashboard.quick_actions')}</span>
                <h2>{t('tenant_portal.files')}</h2>
            </header>

            {props.stats.maintenanceConfirmations > 0 ? (
                <Link
                    className="pmc-tenant-confirmation-alert"
                    href="/maintenance-requests?confirmation=pending"
                >
                    <i className="bi bi-check2-circle" aria-hidden="true" />
                    <span>
                        <strong>{t('maintenance.pending_confirmation')}</strong>
                        <small>
                            {t(
                                'tenant_portal.confirmation_description',
                                undefined,
                                {
                                    count: localizedNumber(
                                        props.stats.maintenanceConfirmations,
                                        locale,
                                    ),
                                },
                            )}
                        </small>
                    </span>
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                </Link>
            ) : null}

            <div>
                <a href={lease.contract_url}>
                    <i className="bi bi-file-earmark-pdf" aria-hidden="true" />
                    <span>
                        <strong>{t('tenant_portal.lease_contract')}</strong>
                        <small>{t('tenant_portal.ready_to_download')}</small>
                    </span>
                </a>
                <a href={lease.statement_url}>
                    <i className="bi bi-receipt" aria-hidden="true" />
                    <span>
                        <strong>{t('tenant_portal.tenant_statement')}</strong>
                        <small>{t('tenant_portal.ready_to_download')}</small>
                    </span>
                </a>
                <Link href="/my-documents">
                    <i className="bi bi-folder2-open" aria-hidden="true" />
                    <span>
                        <strong>{t('tenant_portal.my_documents')}</strong>
                        <small>
                            {t(
                                'tenant_portal.available_files_count',
                                undefined,
                                {
                                    count: localizedNumber(
                                        props.tenantPortal.documents.length,
                                        locale,
                                    ),
                                },
                            )}
                        </small>
                    </span>
                </Link>
                <Link href="/maintenance-requests/create">
                    <i className="bi bi-tools" aria-hidden="true" />
                    <span>
                        <strong>{t('tenant_portal.new_request')}</strong>
                        <small>
                            {t(
                                'dashboard.maintenance_requests_count',
                                undefined,
                                {
                                    count: localizedNumber(
                                        props.stats.maintenanceRequests,
                                        locale,
                                    ),
                                },
                            )}
                        </small>
                    </span>
                </Link>
            </div>
        </section>
    );
}
