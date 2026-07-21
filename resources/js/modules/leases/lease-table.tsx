import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import {
    RecordActions,
    StatusBadge,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { leaseFilterFields } from './lease-filters';
import type { LeaseIndexPageProps } from './types';

type LeaseTableProps = Pick<
    LeaseIndexPageProps,
    | 'leases'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'statusOptions'
    | 'frequencyOptions'
    | 'auth'
    | 'app'
>;

export function LeaseTable(props: LeaseTableProps) {
    const { locale, t, text } = useTranslator();
    const filterFields = leaseFilterFields({
        statuses: props.statusOptions,
        frequencies: props.frequencyOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });

    return (
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
                                {humanDate(lease.started_at, props.app.locale)}{' '}
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
                                        { count: lease.overdue_count },
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
    );
}
