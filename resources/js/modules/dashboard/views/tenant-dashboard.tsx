import { Head, Link } from '@inertiajs/react';

import {
    MetricGrid,
    StatusBadge,
    WorkspaceHeader,
    WorkspacePanel,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { DashboardPageProps } from '../types';

export function TenantDashboard({ props }: { props: DashboardPageProps }) {
    const { locale, t, text } = useTranslator();
    const isArabic = props.app.locale === 'ar';
    const lease = props.tenantPortal?.lease;
    const payments = props.tenantPortal?.payments ?? [];
    const requests = props.tenantPortal?.requests ?? [];
    const documents = props.tenantPortal?.documents ?? [];
    const currencyCode = lease?.currency ?? 'SAR';

    return (
        <AdminLayout>
            <Head title={t('dashboard.tenant_dashboard')} />

            <WorkspaceHeader
                eyebrow="Tenant portal"
                title={
                    (isArabic
                        ? lease?.leaseable?.title_ar ||
                          lease?.leaseable?.title_en
                        : lease?.leaseable?.title_en ||
                          lease?.leaseable?.title_ar) ??
                    text('Your rental portal')
                }
                description={
                    lease
                        ? `${lease.code} · ${lease.leaseable?.code ?? t('dashboard.rental_asset')}`
                        : text(
                              'Your owner or manager needs to activate a lease before payment and document information appears.',
                          )
                }
                actions={[
                    {
                        label: 'Request maintenance',
                        href: '/maintenance-requests/create',
                        icon: 'bi-tools',
                        tone: 'primary',
                    },
                    {
                        label: 'Tenant guide',
                        href: '/documentation',
                        icon: 'bi-journal-text',
                        tone: 'quiet',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: 'Lease',
                        value:
                            props.stats.leaseCode ?? t('dashboard.not_active'),
                        detail: lease?.ends_at
                            ? t('dashboard.ends_on', undefined, {
                                  date: humanDate(lease.ends_at, locale),
                              })
                            : t('dashboard.no_end_date'),
                        icon: 'bi-file-earmark-text',
                        tone: 'ink',
                    },
                    {
                        label: 'Days remaining',
                        value: props.stats.daysLeft ?? 0,
                        detail: text('In the current contract'),
                        icon: 'bi-calendar3',
                        tone: 'blue',
                    },
                    {
                        label: 'Paid',
                        value: currency(
                            Number(props.stats.paidAmount ?? 0),
                            props.app.locale,
                            currencyCode,
                        ),
                        detail: t(
                            'dashboard.posted_payments_count',
                            undefined,
                            {
                                count: payments.length,
                            },
                        ),
                        icon: 'bi-check-circle',
                        tone: 'teal',
                    },
                    {
                        label: 'Remaining',
                        value: currency(
                            Number(props.stats.amountLeft ?? 0),
                            props.app.locale,
                            currencyCode,
                        ),
                        detail: t(
                            'dashboard.maintenance_requests_count',
                            undefined,
                            { count: requests.length },
                        ),
                        icon: 'bi-hourglass-split',
                        tone:
                            Number(props.stats.amountLeft ?? 0) > 0
                                ? 'amber'
                                : 'teal',
                    },
                ]}
            />

            <div className="pmc-command-grid">
                <WorkspacePanel
                    eyebrow="Contract"
                    title="Lease and documents"
                    description="Your contract period and downloadable PDF files."
                >
                    <div className="pmc-tenant-lease-summary">
                        <div>
                            <span>{text('Starts')}</span>
                            <strong>
                                {humanDate(lease?.started_at, props.app.locale)}
                            </strong>
                        </div>
                        <div>
                            <span>{text('Ends')}</span>
                            <strong>
                                {humanDate(lease?.ends_at, props.app.locale)}
                            </strong>
                        </div>
                        <div>
                            <span>{text('Contract rent')}</span>
                            <strong>
                                {currency(
                                    Number(lease?.rent_amount ?? 0),
                                    props.app.locale,
                                    currencyCode,
                                )}
                            </strong>
                        </div>
                    </div>

                    {lease ? (
                        <div className="pmc-tenant-document-actions">
                            <a href={lease.contract_url}>
                                <i className="bi bi-file-earmark-pdf" />
                                {text('Contract PDF')}
                            </a>
                            <a href={lease.statement_url}>
                                <i className="bi bi-receipt" />
                                {text('Tenant statement')}
                            </a>
                        </div>
                    ) : null}

                    <div className="pmc-tenant-document-list">
                        {documents.length > 0 ? (
                            documents.slice(0, 5).map((document) => (
                                <a
                                    key={document.id}
                                    href={document.download_url}
                                >
                                    <i className="bi bi-file-earmark-pdf" />
                                    <div>
                                        <strong>
                                            {(isArabic
                                                ? document.title_ar
                                                : document.title_en) ??
                                                document.title_en}
                                        </strong>
                                        <span>
                                            {text(humanLabel(document.type))}
                                        </span>
                                    </div>
                                    <i className="bi bi-download" />
                                </a>
                            ))
                        ) : (
                            <div className="pmc-command-empty">
                                {text('No contract documents are available.')}
                            </div>
                        )}
                    </div>
                </WorkspacePanel>

                <WorkspacePanel
                    eyebrow="Payments"
                    title="Payment history"
                    description="Posted rent payments and downloadable receipts."
                >
                    <div className="pmc-command-list">
                        {payments.length > 0 ? (
                            payments.slice(0, 7).map((payment) => (
                                <a key={payment.id} href={payment.receipt_url}>
                                    <div>
                                        <strong>
                                            {payment.reference ??
                                                t(
                                                    'dashboard.receipt_number',
                                                    undefined,
                                                    { id: payment.id },
                                                )}
                                        </strong>
                                        <span>
                                            {humanDate(
                                                payment.received_on,
                                                props.app.locale,
                                            )}
                                        </span>
                                    </div>
                                    <em className="is-success">
                                        {currency(
                                            payment.amount,
                                            props.app.locale,
                                            payment.currency,
                                        )}
                                    </em>
                                </a>
                            ))
                        ) : (
                            <div className="pmc-command-empty">
                                {text('No posted payments yet.')}
                            </div>
                        )}
                    </div>
                </WorkspacePanel>
            </div>

            <WorkspacePanel
                eyebrow="Service"
                title="Maintenance requests"
                description="Track every request you submitted for this rental."
                action={{
                    label: 'Submit request',
                    href: '/maintenance-requests/create',
                }}
            >
                <div className="pmc-command-list">
                    {requests.length > 0 ? (
                        requests.slice(0, 6).map((request) => (
                            <Link
                                key={request.id}
                                href={`/maintenance-requests/${request.id}`}
                            >
                                <div>
                                    <strong>{request.title}</strong>
                                    <span>
                                        {humanDate(
                                            request.created_at,
                                            props.app.locale,
                                        )}
                                    </span>
                                </div>
                                <StatusBadge value={request.status} />
                            </Link>
                        ))
                    ) : (
                        <div className="pmc-command-empty">
                            {text('No maintenance requests submitted.')}
                        </div>
                    )}
                </div>
            </WorkspacePanel>
        </AdminLayout>
    );
}
