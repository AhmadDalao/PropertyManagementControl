import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { OwnerStatementRecords } from './owner-statement-records';
import { OwnerStatementSummary } from './owner-statement-summary';
import { cleanReportFilters } from './report-query';
import type { OwnerStatementPageProps } from './types';

export default function OwnerStatementPage() {
    const { props } = usePage<OwnerStatementPageProps>();
    const { locale, t } = useTranslator();
    const query = new URLSearchParams(
        cleanReportFilters({
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

    return (
        <AdminLayout>
            <Head title={t('reports.owner_statement')} />

            <WorkspaceHeader
                eyebrow={t('reports.statement_eyebrow')}
                title={t('reports.owner_statement')}
                description={t('reports.statement_description')}
                actions={[
                    {
                        label: t('actions.back'),
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
                ]}
            />

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

            <OwnerStatementSummary props={props} />
            <OwnerStatementRecords props={props} />
        </AdminLayout>
    );
}
