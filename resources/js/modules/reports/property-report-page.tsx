import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

import '../../../css/styles/reports.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { PropertyReportContext } from './property-report-context';
import { PropertyReportPeriod } from './property-report-period';
import {
    isPropertyReportTab,
    PropertyReportTabs,
} from './property-report-tabs';
import { ReportCollections } from './report-collections';
import { ReportCosts } from './report-costs';
import { ReportOperations } from './report-operations';
import { ReportOverview } from './report-overview';
import type { PropertyReportPageProps, PropertyReportTab } from './types';

export default function PropertyReportPage() {
    const { props } = usePage<PropertyReportPageProps>();
    const { locale, t } = useTranslator();
    const propertyTitle =
        locale === 'ar'
            ? props.property.title_ar || props.property.title_en
            : props.property.title_en || props.property.title_ar || '';
    const [activeTab, setActiveTab] = useState<PropertyReportTab>(() => {
        if (typeof window === 'undefined') {
            return 'overview';
        }

        const requested = new URLSearchParams(window.location.search).get(
            'tab',
        );

        return isPropertyReportTab(requested) ? requested : 'overview';
    });

    const selectTab = (tab: PropertyReportTab) => {
        setActiveTab(tab);

        if (typeof window !== 'undefined') {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        }
    };

    return (
        <AdminLayout>
            <Head
                title={t('reports.property_report_title', undefined, {
                    property: propertyTitle,
                })}
            />
            <WorkspaceHeader
                eyebrow={t('reports.property_report_eyebrow')}
                title={t('reports.property_report_title', undefined, {
                    property: propertyTitle,
                })}
                description={t('reports.property_report_description')}
                actions={[
                    {
                        label: t('reports.open_property'),
                        href: props.property.links.asset,
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                    {
                        label: t('reports.open_explorer'),
                        href: props.property.links.explorer,
                        icon: 'bi-diagram-3',
                    },
                    {
                        label: t('reports.download_pdf'),
                        href: props.property.downloads.pdf,
                        icon: 'bi-file-earmark-pdf',
                        native: true,
                    },
                    {
                        label: t('reports.download_word'),
                        href: props.property.downloads.docx,
                        icon: 'bi-file-earmark-word',
                        native: true,
                    },
                    {
                        label: t('actions.export_xlsx'),
                        href: props.property.downloads.xlsx,
                        icon: 'bi-file-earmark-excel',
                        tone: 'primary',
                        native: true,
                    },
                ]}
            />

            <PropertyReportContext props={props} />
            <PropertyReportPeriod
                key={`${props.filters.date_from}:${props.filters.date_to}`}
                props={props}
                activeTab={activeTab}
            />
            <PropertyReportTabs active={activeTab} onSelect={selectTab} />

            {activeTab === 'overview' ? <ReportOverview props={props} /> : null}
            {activeTab === 'collections' ? (
                <ReportCollections props={props} />
            ) : null}
            {activeTab === 'costs' ? <ReportCosts props={props} /> : null}
            {activeTab === 'operations' ? (
                <ReportOperations props={props} links={props.property.links} />
            ) : null}
        </AdminLayout>
    );
}
