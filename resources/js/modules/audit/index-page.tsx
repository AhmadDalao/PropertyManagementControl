import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { AuditMetrics } from './audit-metrics';
import { AuditTable } from './audit-table';
import type { AuditIndexPageProps } from './types';

export default function AuditIndexPage() {
    const { props } = usePage<AuditIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('audit.title')} />
            <WorkspaceHeader
                eyebrow={t('audit.eyebrow')}
                title={t('audit.title')}
                description={t('audit.description')}
                actions={[
                    {
                        label: t('audit.guide'),
                        href: '/documentation',
                        icon: 'bi-question-circle',
                        tone: 'quiet',
                    },
                ]}
            />
            <AuditMetrics insights={props.auditInsights} />
            <AuditTable props={props} />
        </AdminLayout>
    );
}
