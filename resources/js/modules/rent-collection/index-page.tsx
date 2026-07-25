import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';

import { RentCollectionMetrics } from './rent-collection-metrics';
import { RentCollectionTable } from './rent-collection-table';
import type { RentCollectionPageProps } from './types';

export default function RentCollectionIndexPage() {
    const { props } = usePage<RentCollectionPageProps>();
    const { t } = useTranslator();
    const canCreate = canCreateOperationalRecord(props.auth.user);

    return (
        <AdminLayout>
            <Head title={t('rent_collection.title')} />

            <WorkspaceHeader
                eyebrow={t('rent_collection.workspace_eyebrow')}
                title={t('rent_collection.title')}
                description={t('rent_collection.workspace_description')}
                actions={[
                    {
                        label: t('rent_collection.reports'),
                        href: '/reports?tab=collections',
                        icon: 'bi-graph-up-arrow',
                        tone: 'quiet',
                    },
                    ...(canCreate
                        ? [
                              {
                                  label: t('rent_collection.post_payment'),
                                  href: '/payments/create',
                                  icon: 'bi-plus-lg',
                                  tone: 'primary' as const,
                              },
                          ]
                        : []),
                ]}
            />

            <RentCollectionMetrics {...props} />
            <RentCollectionTable {...props} />
        </AdminLayout>
    );
}
