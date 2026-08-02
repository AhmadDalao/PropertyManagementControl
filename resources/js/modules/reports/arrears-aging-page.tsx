import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/reports.css';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { ArrearsAgingRecords } from './arrears-aging-records';
import { ArrearsAgingScope } from './arrears-aging-scope';
import { ArrearsAgingSummary } from './arrears-aging-summary';
import type { ArrearsAgingPageProps } from './arrears-aging-types';

export default function ArrearsAgingPage() {
    const { props } = usePage<ArrearsAgingPageProps>();
    const { locale, t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('reports.aging_title')} />
            <WorkspaceHeader
                eyebrow={t('reports.aging_eyebrow')}
                title={t('reports.aging_title')}
                description={t('reports.aging_page_description')}
                actions={[
                    {
                        label: t('common.back'),
                        href: '/reports',
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                    {
                        label: t('reports.download_pdf'),
                        href: props.downloads.pdf,
                        icon: 'bi-file-earmark-pdf',
                        native: true,
                    },
                    {
                        label: t('reports.download_word'),
                        href: props.downloads.docx,
                        icon: 'bi-file-earmark-word',
                        native: true,
                    },
                    {
                        label: t('actions.export_xlsx'),
                        href: props.downloads.xlsx,
                        icon: 'bi-file-earmark-excel',
                        tone: 'primary',
                        native: true,
                    },
                ]}
            />
            <ArrearsAgingScope scope={props.scope} />
            <MetricGrid
                metrics={[
                    {
                        label: t('reports.aging_installments'),
                        value: localizedNumber(
                            props.insights.installments,
                            locale,
                        ),
                        detail: t('reports.aging_installments_help'),
                        icon: 'bi-receipt',
                        tone: 'red',
                    },
                    {
                        label: t('reports.aging_leases'),
                        value: localizedNumber(props.insights.leases, locale),
                        detail: t('reports.aging_leases_help'),
                        icon: 'bi-file-earmark-text',
                        tone: 'amber',
                    },
                    {
                        label: t('reports.aging_tenants'),
                        value: localizedNumber(props.insights.tenants, locale),
                        detail: t('reports.aging_tenants_help'),
                        icon: 'bi-people',
                        tone: 'teal',
                    },
                    {
                        label: t('reports.aging_oldest'),
                        value: t('reports.aging_days', undefined, {
                            count: localizedNumber(
                                props.insights.oldest_days,
                                locale,
                            ),
                        }),
                        detail: t('reports.aging_oldest_help'),
                        icon: 'bi-hourglass-split',
                        tone: 'ink',
                    },
                ]}
            />
            <ArrearsAgingSummary positions={props.currencyPositions} />
            <ArrearsAgingRecords props={props} />
        </AdminLayout>
    );
}
