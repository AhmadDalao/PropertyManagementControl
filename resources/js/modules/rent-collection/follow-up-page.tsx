import '../../../css/styles/rent-collection/follow-up.css';

import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { FollowUpForm } from './follow-up-form';
import { FollowUpHistory } from './follow-up-history';
import { FollowUpSummary } from './follow-up-summary';
import type { CollectionFollowUpPageProps } from './types';

export default function CollectionFollowUpPage() {
    const { collection } = usePage<CollectionFollowUpPageProps>().props;
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('rent_collection.follow_up_title')} />

            <WorkspaceHeader
                eyebrow={t('rent_collection.collection_account')}
                title={t('rent_collection.follow_up_title')}
                description={t(
                    'rent_collection.follow_up_description',
                    undefined,
                    {
                        tenant: collection.tenant.name,
                        lease: collection.lease.code,
                    },
                )}
                actions={[
                    {
                        label: t('rent_collection.back_to_ledger'),
                        href: collection.links.back,
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                    {
                        label: t('rent_collection.open_lease'),
                        href: collection.links.lease,
                        icon: 'bi-file-earmark-text',
                        tone: 'quiet',
                    },
                    {
                        label: t('rent_collection.download_statement'),
                        href: collection.links.statement,
                        icon: 'bi-file-earmark-pdf',
                        tone: 'secondary',
                        native: true,
                    },
                    ...(collection.can_record
                        ? [
                              {
                                  label: t('rent_collection.post_payment'),
                                  href: collection.links.payment,
                                  icon: 'bi-cash-stack',
                                  tone: 'primary' as const,
                              },
                          ]
                        : []),
                ]}
            />

            <FollowUpSummary collection={collection} />

            <div className="pmc-collection-follow-up-workspace">
                <FollowUpForm collection={collection} />
                <FollowUpHistory collection={collection} />
            </div>
        </AdminLayout>
    );
}
