import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';

import { DocumentMetrics } from './document-metrics';
import { DocumentTable } from './document-table';
import type { DocumentIndexPageProps } from './types';

export default function DocumentsIndexPage() {
    const { props } = usePage<DocumentIndexPageProps>();
    const { t } = useTranslator();
    const canCreate = canCreateOperationalRecord(props.auth.user);

    return (
        <AdminLayout>
            <Head title={t('documents.title')} />

            <WorkspaceHeader
                eyebrow={t('documents.workspace_eyebrow')}
                title={t('documents.title')}
                description={t('documents.workspace_description')}
                actions={
                    canCreate
                        ? [
                              {
                                  label: t('documents.upload_pdf'),
                                  href: '/documents/create',
                                  icon: 'bi-file-earmark-plus',
                                  tone: 'primary',
                              },
                          ]
                        : []
                }
            />

            <DocumentMetrics {...props} />
            <DocumentTable {...props} />
        </AdminLayout>
    );
}
