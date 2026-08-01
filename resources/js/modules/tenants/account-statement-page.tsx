import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import '../../../css/styles/tenant-statement.css';

import { MetricGrid, WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { TenantFinancialCards, TenantLeaseCards } from './statement-financials';
import {
    TenantDocumentCards,
    TenantInstallmentCards,
    TenantMaintenanceCards,
    TenantPaymentCards,
} from './statement-ledgers';
import type {
    TenantStatementPageProps,
    TenantStatementTab,
} from './statement-types';

const statementTabs: TenantStatementTab[] = [
    'overview',
    'installments',
    'payments',
    'maintenance',
    'documents',
];

export default function TenantAccountStatementPage() {
    const { props } = usePage<TenantStatementPageProps>();
    const { locale, t } = useTranslator();
    const [dateFrom, setDateFrom] = useState(props.filters.date_from);
    const [dateTo, setDateTo] = useState(props.filters.date_to);
    const [activeTab, setActiveTab] = useState<TenantStatementTab>(() => {
        if (typeof window === 'undefined') {
            return 'overview';
        }

        const tab = new URLSearchParams(window.location.search).get('tab');

        return statementTabs.includes(tab as TenantStatementTab)
            ? (tab as TenantStatementTab)
            : 'overview';
    });
    const query = new URLSearchParams({
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
    }).toString();
    const basePath = `/tenants/${props.tenant.id}/account-statement`;

    const selectTab = (tab: TenantStatementTab) => {
        setActiveTab(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    const applyPeriod = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            basePath,
            { date_from: dateFrom, date_to: dateTo, tab: activeTab },
            { preserveState: false },
        );
    };

    return (
        <AdminLayout>
            <Head
                title={t('tenants.account_statement_for', undefined, {
                    tenant: props.tenant.name,
                })}
            />
            <WorkspaceHeader
                eyebrow={t('tenants.statement_eyebrow')}
                title={t('tenants.account_statement_for', undefined, {
                    tenant: props.tenant.name,
                })}
                description={t('tenants.statement_description')}
                actions={[
                    {
                        label: t('tenants.back_to_tenant'),
                        href: props.tenant.back_url,
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                    {
                        label: t('reports.download_pdf'),
                        href: `${basePath}.pdf?${query}`,
                        icon: 'bi-file-earmark-pdf',
                        native: true,
                    },
                    {
                        label: t('reports.download_word'),
                        href: `${basePath}.docx?${query}`,
                        icon: 'bi-file-earmark-word',
                        native: true,
                    },
                    {
                        label: t('actions.export_xlsx'),
                        href: `${basePath}.xlsx?${query}`,
                        icon: 'bi-file-earmark-excel',
                        tone: 'primary',
                        native: true,
                    },
                ]}
            />

            <section className="pmc-tenant-statement-context">
                <div>
                    <span>{t('tenants.portfolio')}</span>
                    <strong>
                        {locale === 'ar'
                            ? props.tenant.portfolio.name_ar ||
                              props.tenant.portfolio.name_en
                            : props.tenant.portfolio.name_en ||
                              props.tenant.portfolio.name_ar}
                    </strong>
                </div>
                <div>
                    <span>{t('tenants.profile_type')}</span>
                    <strong>{t(`tenants.${props.tenant.profile_type}`)}</strong>
                </div>
                <div>
                    <span>{t('tenants.email')}</span>
                    <strong>{props.tenant.email || '-'}</strong>
                </div>
                <div>
                    <span>{t('tenants.phone')}</span>
                    <strong>{props.tenant.phone || '-'}</strong>
                </div>
            </section>

            <form
                className="pmc-tenant-statement-period"
                onSubmit={applyPeriod}
            >
                <div>
                    <label htmlFor="tenant-statement-from">
                        {t('reports.date_from')}
                    </label>
                    <input
                        id="tenant-statement-from"
                        type="date"
                        value={dateFrom}
                        max={dateTo}
                        onChange={(event) => setDateFrom(event.target.value)}
                    />
                </div>
                <div>
                    <label htmlFor="tenant-statement-to">
                        {t('reports.date_to')}
                    </label>
                    <input
                        id="tenant-statement-to"
                        type="date"
                        value={dateTo}
                        min={dateFrom}
                        onChange={(event) => setDateTo(event.target.value)}
                    />
                </div>
                <button type="submit">
                    <i className="bi bi-funnel" aria-hidden="true" />
                    {t('reports.apply_period')}
                </button>
            </form>

            <MetricGrid
                metrics={[
                    {
                        label: t('tenants.contracts'),
                        value: localizedNumber(
                            props.statement.lease_count,
                            locale,
                        ),
                        detail: t('tenants.active_contracts_count', undefined, {
                            count: localizedNumber(
                                props.statement.active_lease_count,
                                locale,
                            ),
                        }),
                        icon: 'bi-file-earmark-text',
                        tone: 'ink',
                    },
                    {
                        label: t('tenants.period_installments'),
                        value: localizedNumber(
                            props.counts.installments,
                            locale,
                        ),
                        detail: t('tenants.selected_period'),
                        icon: 'bi-calendar-check',
                        tone: 'amber',
                    },
                    {
                        label: t('tenants.period_payments'),
                        value: localizedNumber(props.counts.payments, locale),
                        detail: t('tenants.selected_period'),
                        icon: 'bi-cash-stack',
                        tone: 'teal',
                    },
                    {
                        label: t('tenants.account_documents'),
                        value: localizedNumber(props.counts.documents, locale),
                        detail: t('tenants.contracts_and_receipts'),
                        icon: 'bi-file-earmark-pdf',
                        tone: 'blue',
                    },
                ]}
            />

            <nav
                className="pmc-tenant-statement-tabs"
                aria-label={t('tenants.statement_sections')}
            >
                {statementTabs.map((tab) => (
                    <button
                        aria-current={activeTab === tab ? 'page' : undefined}
                        className={activeTab === tab ? 'is-active' : undefined}
                        key={tab}
                        onClick={() => selectTab(tab)}
                        type="button"
                    >
                        {t(`tenants.statement_tab_${tab}`)}
                    </button>
                ))}
            </nav>

            {activeTab === 'overview' ? (
                <>
                    <TenantFinancialCards
                        financials={props.statement.financials}
                    />
                    <TenantLeaseCards leases={props.leases} />
                </>
            ) : null}
            {activeTab === 'installments' ? (
                <TenantInstallmentCards props={props} />
            ) : null}
            {activeTab === 'payments' ? (
                <TenantPaymentCards props={props} />
            ) : null}
            {activeTab === 'maintenance' ? (
                <TenantMaintenanceCards props={props} />
            ) : null}
            {activeTab === 'documents' ? (
                <TenantDocumentCards props={props} />
            ) : null}
        </AdminLayout>
    );
}
