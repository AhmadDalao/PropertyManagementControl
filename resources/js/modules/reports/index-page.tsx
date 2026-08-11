import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import '../../../css/styles/reports.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { ReportFilters } from './report-filters';
import { ReportLibrary } from './report-library';
import { ReportOverview } from './report-overview';
import { ReportPresetList } from './report-preset-list';
import { cleanReportFilters } from './report-query';
import type { ReportFilterValues, ReportsPageProps } from './types';

export default function ReportsIndexPage() {
    const { props } = usePage<ReportsPageProps>();
    const { t } = useTranslator();
    const [filters, setFilters] = useState<ReportFilterValues>({
        period: props.filters.period,
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        portfolio_id: props.filters.portfolio_id
            ? String(props.filters.portfolio_id)
            : 'all',
        property_id: props.filters.property_id
            ? String(props.filters.property_id)
            : 'all',
    });
    const [filtersOpen, setFiltersOpen] = useState(false);
    const exportQuery = new URLSearchParams(
        cleanReportFilters(filters),
    ).toString();
    const exportHref = exportQuery
        ? `/reports/export?${exportQuery}`
        : '/reports/export';
    const saveReportHref = exportQuery
        ? `/reports/saved/create?${exportQuery}`
        : '/reports/saved/create';

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get('/reports', cleanReportFilters(filters), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
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
                        label: t('reports.manage_saved_reports'),
                        href: '/reports/saved',
                        icon: 'bi-bookmark',
                        tone: 'quiet',
                    },
                    {
                        label: t('reports.save_current_report'),
                        href: saveReportHref,
                        icon: 'bi-bookmark',
                        tone: 'secondary',
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
                propertyOptions={props.propertyOptions}
                propertyContext={props.propertyContext}
                onChange={setFilters}
                onSubmit={applyFilters}
                onToggle={() => setFiltersOpen((open) => !open)}
            />
            <ReportLibrary groups={props.reportLibrary} />

            <div className="pmc-report-command-grid">
                <section className="pmc-report-command-main">
                    <header>
                        <div>
                            <span>{t('reports.tab_overview')}</span>
                            <h2>{t('reports.financial_overview')}</h2>
                        </div>
                    </header>
                    <ReportOverview props={props} />
                </section>

                <aside className="pmc-report-command-saved">
                    <header>
                        <div>
                            <span>{t('reports.saved_reports_eyebrow')}</span>
                            <h2>{t('reports.saved_reports_title')}</h2>
                        </div>
                        <a href="/reports/saved">
                            {t('reports.manage_saved_reports')}
                        </a>
                    </header>
                    <ReportPresetList presets={props.savedPresets} />
                </aside>
            </div>
        </AdminLayout>
    );
}
