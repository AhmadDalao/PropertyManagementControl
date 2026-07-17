import { Head, Link } from '@inertiajs/react';

import {
    MetricGrid,
    StatusBadge,
    WorkspaceHeader,
    WorkspacePanel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency, humanDate } from '@/lib/utils';

import type { DashboardPageProps } from '../types';

export function TenantDashboard({ props }: { props: DashboardPageProps }) {
    const lease = props.tenantPortal?.lease;
    const payments = props.tenantPortal?.payments ?? [];
    const requests = props.tenantPortal?.requests ?? [];
    const documents = props.tenantPortal?.documents ?? [];
    const currencyCode = lease?.currency ?? 'SAR';

    return (
        <AdminLayout>
            <Head title="Tenant Dashboard" />

            <WorkspaceHeader
                eyebrow="Tenant portal"
                title={lease?.leaseable?.title_en ?? 'Your rental portal'}
                description={
                    lease
                        ? `${lease.code} · ${lease.leaseable?.code ?? 'Rental asset'}`
                        : 'Your owner or manager needs to activate a lease before payment and document information appears.'
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
                        value: props.stats.leaseCode ?? 'Not active',
                        detail: lease?.ends_at
                            ? `Ends ${humanDate(lease.ends_at, props.app.locale)}`
                            : 'No end date',
                        icon: 'bi-file-earmark-text',
                        tone: 'ink',
                    },
                    {
                        label: 'Days remaining',
                        value: props.stats.daysLeft ?? 0,
                        detail: 'In the current contract',
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
                        detail: `${payments.length} posted payments`,
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
                        detail: `${requests.length} maintenance requests`,
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
                            <span>Starts</span>
                            <strong>
                                {humanDate(lease?.started_at, props.app.locale)}
                            </strong>
                        </div>
                        <div>
                            <span>Ends</span>
                            <strong>
                                {humanDate(lease?.ends_at, props.app.locale)}
                            </strong>
                        </div>
                        <div>
                            <span>Contract rent</span>
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
                                Contract PDF
                            </a>
                            <a href={lease.statement_url}>
                                <i className="bi bi-receipt" />
                                Tenant statement
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
                                        <strong>{document.title_en}</strong>
                                        <span>{document.type}</span>
                                    </div>
                                    <i className="bi bi-download" />
                                </a>
                            ))
                        ) : (
                            <div className="pmc-command-empty">
                                No contract documents are available.
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
                                                `Receipt #${payment.id}`}
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
                                No posted payments yet.
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
                            No maintenance requests submitted.
                        </div>
                    )}
                </div>
            </WorkspacePanel>
        </AdminLayout>
    );
}
