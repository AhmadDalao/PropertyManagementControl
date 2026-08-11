import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/tenant-portal.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { LeaseContractPanel } from './lease-contract-panel';
import { LeaseDocumentPanel } from './lease-document-panel';
import { LeaseMetrics } from './lease-metrics';
import { LeaseSchedule } from './lease-schedule';
import { LeaseSelectorHero } from './lease-selector-hero';
import { PortalEmpty } from './portal-empty';
import type { TenantLeasePageProps } from './types';

export default function TenantLeasePage() {
    const { props } = usePage<TenantLeasePageProps>();
    const { t } = useTranslator();
    const lease = props.lease;

    return (
        <AdminLayout>
            <Head title={t('tenant_portal.my_lease')} />
            <WorkspaceHeader
                eyebrow={t('tenant_portal.portal_eyebrow')}
                title={t('tenant_portal.my_lease')}
                description={t('tenant_portal.lease_description')}
                actions={
                    lease
                        ? [
                              {
                                  label: t('tenant_portal.download_contract'),
                                  href: lease.contract_url,
                                  icon: 'bi-file-earmark-pdf',
                                  native: true,
                              },
                              {
                                  label: t('tenant_portal.new_request'),
                                  href: '/maintenance-requests/create',
                                  icon: 'bi-tools',
                                  tone: 'primary',
                              },
                          ]
                        : []
                }
            />

            {!lease ? (
                <PortalEmpty kind="lease" />
            ) : (
                <>
                    <LeaseSelectorHero props={props} />
                    <LeaseMetrics lease={lease} />
                    <div className="pmc-portal-layout">
                        <LeaseContractPanel lease={lease} />
                        <LeaseDocumentPanel props={props} />
                    </div>
                    <LeaseSchedule props={props} />
                </>
            )}
        </AdminLayout>
    );
}
