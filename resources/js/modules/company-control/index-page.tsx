import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/company-control.css';

import { WorkspaceHeader, WorkspacePanel } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { CompanyControlFilters } from './company-control-filters';
import { CompanyControlGrid } from './company-control-grid';
import { CompanyControlMetrics } from './company-control-metrics';
import type { CompanyControlProps } from './types';

export default function CompanyControlPage() {
    const { props } = usePage<CompanyControlProps>();
    const { t } = useTranslator();
    const exportQuery = new URLSearchParams(
        Object.entries(props.filters)
            .filter(([key]) => key !== 'page')
            .map(([key, value]) => [key, String(value)]),
    ).toString();

    return (
        <AdminLayout>
            <Head title={t('company_control.title')} />
            <WorkspaceHeader
                eyebrow={t('company_control.eyebrow')}
                title={t('company_control.title')}
                description={t('company_control.description')}
                actions={[
                    {
                        label: t('company_control.export_xlsx'),
                        href: `/company-control/export.xlsx?${exportQuery}`,
                        icon: 'bi-file-earmark-excel',
                        tone: 'primary',
                    },
                    {
                        label: t('nav.system_readiness'),
                        href: '/system/readiness',
                        icon: 'bi-shield-check',
                    },
                ]}
            />
            <CompanyControlMetrics summary={props.summary} />
            <WorkspacePanel
                eyebrow={t('company_control.workspace_eyebrow')}
                title={t('company_control.directory')}
                description={t('company_control.directory_description')}
            >
                <CompanyControlFilters
                    filters={props.filters}
                    counts={props.counts}
                />
                <CompanyControlGrid portfolios={props.portfolios} />
            </WorkspacePanel>
        </AdminLayout>
    );
}
