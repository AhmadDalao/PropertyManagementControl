import { Link } from '@inertiajs/react';

import { currency } from '@/lib/utils';

import type { LeaseBalance, NextAction } from './types';

export const chartColors = [
    '#ef6c2f',
    '#0c8a7c',
    '#ffca4b',
    '#24314a',
    '#38bdf8',
];

export function SectionTitle({
    eyebrow,
    title,
    actionHref,
    actionLabel,
}: {
    eyebrow: string;
    title: string;
    actionHref?: string;
    actionLabel?: string;
}) {
    return (
        <div className="pmc-section-title">
            <div>
                <div className="pmc-kicker mb-2">{eyebrow}</div>
                <h2>{title}</h2>
            </div>
            {actionHref && actionLabel ? (
                <Link
                    href={actionHref}
                    className="btn btn-outline-secondary btn-sm"
                >
                    {actionLabel}
                </Link>
            ) : null}
        </div>
    );
}

export function NextActionDeck({ actions }: { actions: NextAction[] }) {
    if (actions.length === 0) {
        return null;
    }

    return (
        <section className="pmc-next-action-grid">
            {actions.map((action) => (
                <Link
                    key={`${action.href}-${action.label}`}
                    href={action.href}
                    className="pmc-next-action-card"
                >
                    <i className={`bi ${action.icon}`} />
                    <div>
                        <span>Next action</span>
                        <strong>{action.label}</strong>
                        <small>{action.description}</small>
                    </div>
                    <em>
                        Open
                        <i className="bi bi-arrow-right-short" />
                    </em>
                </Link>
            ))}
        </section>
    );
}

export function CycleMap({
    steps,
}: {
    steps: Array<{
        label: string;
        description: string;
        done: boolean;
        href: string;
        icon: string;
    }>;
}) {
    return (
        <section className="pmc-cycle-map">
            <div className="pmc-section-title">
                <div>
                    <div className="pmc-kicker mb-2">Operating cycle</div>
                    <h2>Follow the property workflow in order</h2>
                </div>
                <Link
                    href="/documentation"
                    className="btn btn-outline-secondary btn-sm"
                >
                    How it works
                </Link>
            </div>
            <div className="pmc-cycle-rail">
                {steps.map((step, index) => (
                    <Link
                        key={step.label}
                        href={step.href}
                        className={step.done ? 'is-done' : ''}
                    >
                        <span>{index + 1}</span>
                        <i className={`bi ${step.icon}`} />
                        <strong>{step.label}</strong>
                        <small>{step.description}</small>
                    </Link>
                ))}
            </div>
        </section>
    );
}

export function LeaseList({
    leases,
    locale,
    empty,
    showBalanceOnly = false,
}: {
    leases: LeaseBalance[];
    locale: 'en' | 'ar';
    empty: string;
    showBalanceOnly?: boolean;
}) {
    if (leases.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-lease-list">
            {leases.map((lease) => (
                <Link key={lease.id} href="/leases">
                    <div>
                        <strong>{lease.code}</strong>
                        <span>
                            {lease.tenant ?? 'No tenant'} ·{' '}
                            {lease.asset ?? 'No asset'}
                        </span>
                    </div>
                    <em>
                        {showBalanceOnly
                            ? currency(
                                  lease.balance_remaining,
                                  locale,
                                  lease.currency,
                              )
                            : `${lease.days_remaining ?? 0} days`}
                    </em>
                </Link>
            ))}
        </div>
    );
}

export function MiniMetricList({
    source,
    empty,
}: {
    source: Record<string, number>;
    empty: string;
}) {
    const entries = Object.entries(source);

    if (entries.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-mini-metric-list">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <span>{label.replaceAll('_', ' ')}</span>
                    <strong>{value}</strong>
                </div>
            ))}
        </div>
    );
}

export function ActivityTable({
    rows,
    empty,
}: {
    rows: Array<{ id: number; title: string; meta: string; value: string }>;
    empty: string;
}) {
    if (rows.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-activity-list">
            {rows.map((row) => (
                <div key={row.id}>
                    <div>
                        <strong>{row.title}</strong>
                        <span>{row.meta}</span>
                    </div>
                    <em>{row.value}</em>
                </div>
            ))}
        </div>
    );
}

export function ChartEmptyState({
    icon,
    title,
    message,
}: {
    icon: string;
    title: string;
    message: string;
}) {
    return (
        <div className="pmc-chart-empty">
            <i className={`bi ${icon}`} />
            <strong>{title}</strong>
            <span>{message}</span>
        </div>
    );
}

export function InlineEmptyState({ message }: { message: string }) {
    return <div className="pmc-inline-empty">{message}</div>;
}
