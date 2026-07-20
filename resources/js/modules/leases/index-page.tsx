import { Head, usePage } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import type { TableFilterField } from '@/components/data-table';
import {
    MetricGrid,
    RecordActions,
    StatusBadge,
    WorkspaceHeader,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

type LeaseRecord = {
    id: number;
    code: string;
    status: string;
    payment_frequency: string;
    started_at?: string | null;
    ends_at?: string | null;
    signed_at?: string | null;
    currency: string;
    tenant_profile?: {
        user?: { name?: string | null; email?: string | null };
    };
    leaseable?: {
        title_en?: string | null;
        title_ar?: string | null;
        code?: string | null;
    };
    total_due: number;
    total_paid: number;
    balance_remaining: number;
    days_remaining?: number | null;
    overdue_count: number;
    next_due_date?: string | null;
    next_due_amount?: number | null;
};

type PageProps = SharedProps & {
    leases: PaginatedData<LeaseRecord>;
    leaseInsights: {
        total: number;
        active: number;
        draft: number;
        unsigned: number;
        expiring_soon: number;
        overdue: number;
        total_due: number;
        total_paid: number;
        balance_remaining: number;
    };
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
};

export default function LeasesIndexPage() {
    const { props } = usePage<PageProps>();
    const { locale, t, text } = useTranslator();
    const filterFields: TableFilterField[] = [
        {
            name: 'status',
            label: 'Status',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Draft', value: 'draft' },
                { label: 'Active', value: 'active' },
                { label: 'Expired', value: 'expired' },
                { label: 'Terminated', value: 'terminated' },
            ],
        },
        {
            name: 'payment_frequency',
            label: 'Frequency',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Monthly', value: 'monthly' },
                { label: 'Quarterly', value: 'quarterly' },
                { label: 'Yearly', value: 'yearly' },
            ],
        },
        { name: 'date_from', label: 'From', type: 'date' },
        { name: 'date_to', label: 'To', type: 'date' },
    ];

    if (props.auth.user?.roles.includes('superadmin')) {
        filterFields.push({
            name: 'portfolio_id',
            label: 'Portfolio',
            options: [
                { label: 'All', value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return (
        <AdminLayout>
            <Head title={text('Leases')} />

            <WorkspaceHeader
                eyebrow="Portfolio"
                title="Leases"
                description="Find a contract and open it to manage installments, signed PDFs, balances, renewal, termination, and history."
                actions={[
                    {
                        label: 'Post payment',
                        href: '/payments/create',
                        icon: 'bi-cash-stack',
                    },
                    {
                        label: 'Create lease',
                        href: '/leases/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Active leases',
                        value: props.leaseInsights.active,
                        detail: t('leases.total_contracts', undefined, {
                            count: props.leaseInsights.total,
                        }),
                        icon: 'bi-file-earmark-text',
                        tone: 'ink',
                    },
                    {
                        label: 'Collected',
                        value: currency(
                            props.leaseInsights.total_paid,
                            props.app.locale,
                        ),
                        detail: t('leases.scheduled_amount', undefined, {
                            amount: currency(
                                props.leaseInsights.total_due,
                                locale,
                            ),
                        }),
                        icon: 'bi-check-circle',
                        tone: 'teal',
                    },
                    {
                        label: 'Outstanding',
                        value: currency(
                            props.leaseInsights.balance_remaining,
                            props.app.locale,
                        ),
                        detail: t('leases.overdue_contracts', undefined, {
                            count: props.leaseInsights.overdue,
                        }),
                        icon: 'bi-hourglass-split',
                        tone:
                            props.leaseInsights.balance_remaining > 0
                                ? 'red'
                                : 'blue',
                    },
                    {
                        label: 'Contract attention',
                        value:
                            props.leaseInsights.unsigned +
                            props.leaseInsights.expiring_soon,
                        detail: t('leases.attention_mix', undefined, {
                            unsigned: props.leaseInsights.unsigned,
                            expiring: props.leaseInsights.expiring_soon,
                        }),
                        icon: 'bi-file-earmark-excel',
                        tone:
                            props.leaseInsights.unsigned +
                                props.leaseInsights.expiring_soon >
                            0
                                ? 'amber'
                                : 'blue',
                    },
                ]}
            />

            <DataTable
                title="Lease register"
                description="Search contract code, tenant, asset, notes, dates, or payment frequency."
                data={props.leases}
                filters={props.filters}
                counts={props.counts}
                basePath="/leases"
                rowHref={(lease) => `/leases/${lease.id}`}
                exportHref={exportUrl('/exports/leases', props.filters)}
                filterFields={filterFields}
                columns={[
                    {
                        key: 'lease',
                        label: 'Lease',
                        render: (lease) => (
                            <div className="pmc-primary-cell">
                                <strong>{lease.code}</strong>
                                <span>
                                    {text(humanLabel(lease.payment_frequency))}
                                </span>
                                <StatusBadge value={lease.status} />
                            </div>
                        ),
                    },
                    {
                        key: 'tenant',
                        label: 'Tenant / asset',
                        render: (lease) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {lease.tenant_profile?.user?.name ??
                                        text('No tenant')}
                                </strong>
                                <span>
                                    {(locale === 'ar'
                                        ? lease.leaseable?.title_ar ||
                                          lease.leaseable?.title_en
                                        : lease.leaseable?.title_en ||
                                          lease.leaseable?.title_ar) ??
                                        text('No asset')}{' '}
                                    · {lease.leaseable?.code ?? text('No code')}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'period',
                        label: 'Contract period',
                        render: (lease) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {humanDate(
                                        lease.started_at,
                                        props.app.locale,
                                    )}{' '}
                                    {text('to')}{' '}
                                    {humanDate(lease.ends_at, props.app.locale)}
                                </strong>
                                <span>
                                    {t('leases.days_remaining', undefined, {
                                        count: lease.days_remaining ?? 0,
                                    })}{' '}
                                    ·{' '}
                                    {lease.signed_at
                                        ? text('Signed')
                                        : text('Unsigned')}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'balance',
                        label: 'Balance',
                        render: (lease) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {currency(
                                        lease.balance_remaining,
                                        props.app.locale,
                                        lease.currency,
                                    )}{' '}
                                    {text('left')}
                                </strong>
                                <span>
                                    {currency(
                                        lease.total_paid,
                                        props.app.locale,
                                        lease.currency,
                                    )}{' '}
                                    {text('paid')}
                                </span>
                            </div>
                        ),
                    },
                    {
                        key: 'next',
                        label: 'Next due',
                        render: (lease) => (
                            <div className="pmc-stacked-cell">
                                <strong>
                                    {humanDate(
                                        lease.next_due_date,
                                        props.app.locale,
                                    )}
                                </strong>
                                <span>
                                    {lease.next_due_amount
                                        ? currency(
                                              lease.next_due_amount,
                                              props.app.locale,
                                              lease.currency,
                                          )
                                        : text('No open installment')}
                                </span>
                                {lease.overdue_count > 0 ? (
                                    <StatusBadge
                                        value="overdue"
                                        label={t(
                                            'leases.overdue_installments',
                                            undefined,
                                            {
                                                count: lease.overdue_count,
                                            },
                                        )}
                                        tone="danger"
                                    />
                                ) : null}
                            </div>
                        ),
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        className: 'text-end',
                        render: (lease) => (
                            <RecordActions
                                showHref={`/leases/${lease.id}`}
                                editHref={`/leases/${lease.id}/edit`}
                            >
                                <a
                                    href={`/leases/${lease.id}/contract`}
                                    className="btn btn-outline-secondary btn-sm"
                                >
                                    <i className="bi bi-file-earmark-pdf" />
                                    <span>{text('Contract')}</span>
                                </a>
                                {lease.status !== 'terminated' ? (
                                    <ArchiveAction
                                        href={`/leases/${lease.id}`}
                                        label="Terminate"
                                        confirmMessage={t(
                                            'leases.terminate_confirm',
                                            undefined,
                                            { code: lease.code },
                                        )}
                                    />
                                ) : null}
                            </RecordActions>
                        ),
                    },
                ]}
            />
        </AdminLayout>
    );
}
