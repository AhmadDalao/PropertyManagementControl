import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { ReportCollections } from './report-collections';
import { ReportCosts } from './report-costs';
import { ReportFilters } from './report-filters';
import { ReportOperations } from './report-operations';
import { ReportOverview } from './report-overview';
import { ReportPresets } from './report-presets';
import { cleanReportFilters } from './report-query';
import { isReportTab, ReportTabs } from './report-tabs';
import type { ReportFilterValues, ReportsPageProps, ReportTab } from './types';

export default function ReportsIndexPage() {
    const { props } = usePage<ReportsPageProps>();
    const { t } = useTranslator();
    const [filters, setFilters] = useState<ReportFilterValues>({
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        portfolio_id: props.filters.portfolio_id
            ? String(props.filters.portfolio_id)
            : 'all',
    });
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [activeTab, setActiveTab] = useState<ReportTab>(() => {
        if (typeof window === 'undefined') {
            return 'overview';
        }

        const requested = new URLSearchParams(window.location.search).get(
            'tab',
        );

        return isReportTab(requested) ? requested : 'overview';
    });
    const exportQuery = new URLSearchParams(
        cleanReportFilters(filters),
    ).toString();
    const exportHref = exportQuery
        ? `/reports/export?${exportQuery}`
        : '/reports/export';

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            '/reports',
            { ...cleanReportFilters(filters), tab: activeTab },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    const selectTab = (tab: ReportTab) => {
        setActiveTab(tab);

        if (typeof window === 'undefined') {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <AdminLayout>
            <Head title={t('reports.title')} />

            <WorkspaceHeader
                eyebrow={t('reports.eyebrow')}
                title={t('reports.title')}
                description={t('reports.description')}
                actions={[
                    {
                        label: t('reports.guide'),
                        href: '/documentation',
                        icon: 'bi-question-circle',
                        tone: 'quiet',
                    },
                    {
                        label: t('actions.export_xlsx'),
                        href: exportHref,
                        icon: 'bi-file-earmark-excel',
                        tone: 'primary',
                        native: true,
                    },
                ]}
            />

            <ReportFilters
                filters={filters}
                filtersOpen={filtersOpen}
                mode={props.mode}
                portfolioOptions={props.portfolioOptions}
                onChange={setFilters}
                onSubmit={applyFilters}
                onToggle={() => setFiltersOpen((open) => !open)}
            />
            <ReportTabs active={activeTab} onSelect={selectTab} />

            {activeTab === 'overview' ? <ReportOverview props={props} /> : null}
            {activeTab === 'collections' ? (
                <ReportCollections props={props} />
            ) : null}
            {activeTab === 'costs' ? <ReportCosts props={props} /> : null}
            {activeTab === 'operations' ? (
                <ReportOperations props={props} />
            ) : null}

            <ReportPresets
                key={props.presetVisibilityOptions.join('-')}
                filters={filters}
                presets={props.savedPresets}
                visibilityOptions={props.presetVisibilityOptions}
            />
        </AdminLayout>
    );
}
