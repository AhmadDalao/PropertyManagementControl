import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/tenant-portal.css';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { DocumentRecords } from './document-records';
import { PortalEmpty } from './portal-empty';
import { PortalFilters } from './portal-filters';
import type { TenantDocumentsPageProps } from './types';

export default function TenantDocumentsPage() {
    const { props } = usePage<TenantDocumentsPageProps>();
    const { locale, t } = useTranslator();
    const topTypes = props.types.slice(0, 3);

    return (
        <AdminLayout>
            <Head title={t('tenant_portal.my_documents')} />
            <WorkspaceHeader
                eyebrow={t('tenant_portal.portal_eyebrow')}
                title={t('tenant_portal.my_documents')}
                description={t('tenant_portal.documents_description')}
                actions={[
                    {
                        label: t('tenant_portal.my_lease'),
                        href: '/my-lease',
                        icon: 'bi-file-earmark-text',
                        tone: 'quiet',
                    },
                ]}
            />
            <MetricGrid
                metrics={[
                    {
                        label: t('tenant_portal.available_documents'),
                        value: localizedNumber(props.counts.all ?? 0, locale),
                        detail: t('tenant_portal.documents_are_private'),
                        icon: 'bi-folder2-open',
                        tone: 'ink',
                    },
                    ...topTypes.map((type, index) => ({
                        label: t(`documents.options.${type}`),
                        value: localizedNumber(props.counts[type] ?? 0, locale),
                        detail: t('tenant_portal.ready_to_download'),
                        icon: 'bi-file-earmark-pdf',
                        tone: (['teal', 'blue', 'amber'][index] ?? 'ink') as
                            'teal' | 'blue' | 'amber' | 'ink',
                    })),
                ]}
            />
            <PortalFilters
                basePath="/my-documents"
                filters={props.filters}
                leases={props.leases}
                mode="documents"
                types={props.types}
            />
            {props.documents.total === 0 &&
            !props.filters.search &&
            props.filters.type === 'all' &&
            !props.filters.lease_id ? (
                <PortalEmpty kind="document" />
            ) : (
                <DocumentRecords props={props} />
            )}
        </AdminLayout>
    );
}
