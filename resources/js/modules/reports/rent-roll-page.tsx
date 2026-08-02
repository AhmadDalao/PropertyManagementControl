import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/reports.css';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { RentRollFinancials } from './rent-roll-financials';
import { RentRollRecords } from './rent-roll-records';
import { RentRollScope } from './rent-roll-scope';
import type { RentRollPageProps } from './rent-roll-types';

export default function RentRollPage() {
    const { props } = usePage<RentRollPageProps>();
    const { locale, t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('reports.rent_roll_title')} />

            <WorkspaceHeader
                eyebrow={t('reports.rent_roll_eyebrow')}
                title={t('reports.rent_roll_title')}
                description={t('reports.rent_roll_page_description')}
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

            <RentRollScope scope={props.scope} />

            <MetricGrid
                metrics={[
                    {
                        label: t('reports.rent_roll_matching'),
                        value: localizedNumber(props.insights.matching, locale),
                        detail: t('reports.rent_roll_matching_help'),
                        icon: 'bi-grid-3x3-gap',
                        tone: 'ink',
                    },
                    {
                        label: t('reports.rent_roll_occupied'),
                        value: localizedNumber(props.insights.occupied, locale),
                        detail: t('reports.rent_roll_occupied_help'),
                        icon: 'bi-building-check',
                        tone: 'teal',
                    },
                    {
                        label: t('reports.rent_roll_vacant'),
                        value: localizedNumber(props.insights.vacant, locale),
                        detail: t('reports.rent_roll_vacant_help'),
                        icon: 'bi-door-open',
                        tone: 'amber',
                    },
                    {
                        label: t('reports.rent_roll_attention'),
                        value: localizedNumber(
                            props.insights.attention,
                            locale,
                        ),
                        detail: t('reports.rent_roll_attention_help'),
                        icon: 'bi-exclamation-circle',
                        tone: props.insights.attention > 0 ? 'red' : 'teal',
                    },
                ]}
            />

            <RentRollFinancials positions={props.currencyPositions} />
            <RentRollRecords props={props} />
        </AdminLayout>
    );
}
