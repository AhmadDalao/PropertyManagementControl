import { Link } from '@inertiajs/react';

import { currency } from '@/lib/utils';

import type { LeaseBalance, NextAction, PropertyMapAsset } from './types';

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

export function PropertyMap({
    assets,
    locale,
}: {
    assets: PropertyMapAsset[];
    locale: 'en' | 'ar';
}) {
    if (assets.length === 0) {
        return (
            <section className="pmc-property-map-card">
                <SectionTitle
                    eyebrow="Property map"
                    title="No mapped properties yet"
                    actionHref="/assets/create"
                    actionLabel="Create asset"
                />
                <InlineEmptyState message="Create a property or building, then add zone and land number fields to activate the owner map." />
            </section>
        );
    }

    const featured = assets[0];
    const mappedCount = assets.filter((asset) => asset.has_coordinates).length;
    const zones = Array.from(
        new Set(assets.map((asset) => asset.zone).filter(Boolean)),
    );

    return (
        <section className="pmc-property-map-card">
            <SectionTitle
                eyebrow="Owner property map"
                title="Click a zone or land number to open details"
                actionHref="/assets"
                actionLabel="Manage assets"
            />

            <div className="pmc-property-map-summary">
                <span>
                    <strong>{assets.length}</strong> properties
                </span>
                <span>
                    <strong>{mappedCount}</strong> positioned
                </span>
                <span>
                    <strong>{zones.length}</strong> zones
                </span>
            </div>

            <div className="pmc-property-map-layout">
                <div
                    className="pmc-property-map-canvas"
                    aria-label="Property map"
                >
                    <div className="pmc-map-grid" />
                    {assets.map((asset) => (
                        <Link
                            key={asset.id}
                            href={asset.href}
                            className={`pmc-map-parcel is-${asset.occupancy_status}`}
                            style={{
                                insetInlineStart: `${asset.x}%`,
                                top: `${asset.y}%`,
                            }}
                            title={`${asset.zone ?? 'Zone'} · ${asset.land_number ?? asset.code}`}
                        >
                            <span>{asset.zone}</span>
                            <strong>{asset.land_number ?? asset.code}</strong>
                        </Link>
                    ))}
                </div>

                <aside className="pmc-property-map-detail">
                    <span>{featured.zone}</span>
                    <h3>{featured.title}</h3>
                    <p>
                        {featured.land_number ?? featured.code} ·{' '}
                        {featured.address ?? 'No address recorded'}
                    </p>
                    <dl>
                        <div>
                            <dt>Value</dt>
                            <dd>
                                {currency(
                                    featured.valuation_amount,
                                    locale,
                                    featured.currency,
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt>Occupancy</dt>
                            <dd>{featured.occupancy_status}</dd>
                        </div>
                        <div>
                            <dt>Units</dt>
                            <dd>{featured.rentable_children_count}</dd>
                        </div>
                        <div>
                            <dt>Open issues</dt>
                            <dd>{featured.open_requests_count}</dd>
                        </div>
                    </dl>
                    <Link href={featured.href} className="btn btn-primary">
                        Open property details
                    </Link>
                </aside>
            </div>

            <div className="pmc-property-map-list">
                {assets.map((asset) => (
                    <Link key={asset.id} href={asset.href}>
                        <span>{asset.zone}</span>
                        <strong>{asset.land_number ?? asset.code}</strong>
                        <em>{asset.title}</em>
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
