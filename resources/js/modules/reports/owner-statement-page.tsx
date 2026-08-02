import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

import '../../../css/styles/reports.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { OwnerStatementFilters } from './owner-statement-filters';
import { OwnerStatementRecords } from './owner-statement-records';
import {
    OwnerStatementComparison,
    OwnerStatementOverview,
} from './owner-statement-summary';
import {
    isOwnerStatementTab,
    OwnerStatementTabs,
} from './owner-statement-tabs';
import { cleanReportFilters } from './report-query';
import type { OwnerStatementPageProps, OwnerStatementTab } from './types';

export default function OwnerStatementPage() {
    const { props } = usePage<OwnerStatementPageProps>();
    const { locale, t } = useTranslator();
    const query = new URLSearchParams(
        cleanReportFilters({
            period: props.filters.period,
            date_from: props.filters.date_from,
            date_to: props.filters.date_to,
            portfolio_id: props.filters.portfolio_id
                ? String(props.filters.portfolio_id)
                : 'all',
            property_id: props.filters.property_id
                ? String(props.filters.property_id)
                : 'all',
        }),
    ).toString();
    const suffix = query ? `?${query}` : '';
    const [activeTab, setActiveTab] = useState<OwnerStatementTab>(() => {
        if (typeof window === 'undefined') {
            return 'overview';
        }

        const requested = new URLSearchParams(window.location.search).get(
            'tab',
        );

        return isOwnerStatementTab(requested) ? requested : 'overview';
    });
    const selectTab = (tab: OwnerStatementTab) => {
        setActiveTab(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <AdminLayout>
            <Head title={t('reports.owner_statement')} />

            <WorkspaceHeader
                eyebrow={t('reports.statement_eyebrow')}
                title={t('reports.owner_statement')}
                description={t('reports.statement_description')}
                actions={[
                    {
                        label: t('common.back'),
                        href: `/reports${suffix}`,
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                    {
                        label: t('reports.download_pdf'),
                        href: `/reports/statement.pdf${suffix}`,
                        icon: 'bi-file-earmark-pdf',
                        tone: 'secondary',
                        native: true,
                    },
                    {
                        label: t('reports.download_word'),
                        href: `/reports/statement.docx${suffix}`,
                        icon: 'bi-file-earmark-word',
                        tone: 'primary',
                        native: true,
                    },
                    {
                        label: t('actions.export_xlsx'),
                        href: `/reports/statement.xlsx${suffix}`,
                        icon: 'bi-file-earmark-excel',
                        tone: 'primary',
                        native: true,
                    },
                ]}
            />

            <OwnerStatementFilters props={props} activeTab={activeTab} />

            <section className="pmc-statement-context">
                <div>
                    <span>{t('reports.portfolio')}</span>
                    <strong>{props.statement.portfolio[locale]}</strong>
                </div>
                <div>
                    <span>{t('reports.property')}</span>
                    <strong>{props.statement.property[locale]}</strong>
                </div>
                <div>
                    <span>{t('reports.statement_period')}</span>
                    <strong>
                        {props.filters.date_from} - {props.filters.date_to}
                    </strong>
                </div>
                <div>
                    <span>{t('reports.prepared_for')}</span>
                    <strong>{props.statement.prepared_for}</strong>
                </div>
            </section>

            <OwnerStatementTabs active={activeTab} onSelect={selectTab} />
            <section
                id="owner-statement-panel"
                className="pmc-owner-statement-panel"
                role="tabpanel"
                aria-labelledby={`owner-statement-tab-${activeTab}`}
            >
                {activeTab === 'overview' ? (
                    <OwnerStatementOverview props={props} />
                ) : null}
                {activeTab === 'comparison' ? (
                    <OwnerStatementComparison props={props} />
                ) : null}
                {activeTab === 'arrears' ||
                activeTab === 'payments' ||
                activeTab === 'maintenance' ? (
                    <OwnerStatementRecords props={props} section={activeTab} />
                ) : null}
            </section>
        </AdminLayout>
    );
}
