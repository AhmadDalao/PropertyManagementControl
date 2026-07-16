import { Link } from '@inertiajs/react';
import { useState } from 'react';

import { currency } from '@/lib/utils';

import type {
    DashboardPageProps,
    LeaseBalance,
    NextAction,
    PropertyMapAsset,
} from './types';

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

function isStringValue(value: unknown): value is string {
    return typeof value === 'string' && value.trim() !== '';
}

export function PropertyMap({
    assets,
    locale,
    summary,
}: {
    assets: PropertyMapAsset[];
    locale: 'en' | 'ar';
    summary?: NonNullable<DashboardPageProps['propertyMap']>['summary'];
}) {
    const [selectedAssetId, setSelectedAssetId] = useState<number | null>(
        assets[0]?.id ?? null,
    );
    const [mapSearch, setMapSearch] = useState('');
    const [zoneFilter, setZoneFilter] = useState('all');
    const [occupancyFilter, setOccupancyFilter] = useState('all');

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

    const searchNeedle = mapSearch.trim().toLowerCase();
    const zones = Array.from(
        new Set(assets.map((asset) => asset.zone).filter(isStringValue)),
    ).sort();
    const readyCount =
        summary?.ready ??
        assets.filter((asset) => asset.has_coordinates && asset.has_identity)
            .length;
    const needsPosition =
        summary?.needs_position ??
        assets.filter((asset) => !asset.has_coordinates).length;
    const needsIdentity =
        summary?.needs_identity ??
        assets.filter((asset) => !asset.has_identity).length;
    const coveragePercent =
        summary?.coverage_percent ??
        (assets.length > 0
            ? Math.round((readyCount / assets.length) * 100)
            : 0);
    const firstNeedsPosition = assets.find((asset) => !asset.has_coordinates);
    const firstNeedsIdentity = assets.find((asset) => !asset.has_identity);
    const occupancyStates = Array.from(
        new Set(
            assets.map((asset) => asset.occupancy_status).filter(isStringValue),
        ),
    ).sort();
    const visibleAssets = assets.filter((asset) => {
        const zoneMatches =
            zoneFilter === 'all' || (asset.zone ?? '') === zoneFilter;
        const occupancyMatches =
            occupancyFilter === 'all' ||
            asset.occupancy_status === occupancyFilter;
        const searchMatches =
            searchNeedle === '' ||
            [
                asset.title,
                asset.code,
                asset.zone,
                asset.land_number,
                asset.address,
                asset.owner,
                asset.manager,
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value).toLowerCase().includes(searchNeedle),
                );

        return zoneMatches && occupancyMatches && searchMatches;
    });
    const selectedAsset =
        visibleAssets.find((asset) => asset.id === selectedAssetId) ??
        visibleAssets[0] ??
        assets[0];
    const mappedCount = assets.filter((asset) => asset.has_coordinates).length;
    const hasFilteredMap = visibleAssets.length > 0;
    const resetMapFilters = () => {
        setMapSearch('');
        setZoneFilter('all');
        setOccupancyFilter('all');
    };

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
                <span>
                    <strong>{readyCount}</strong> map ready
                </span>
            </div>

            <div
                className={`pmc-map-readiness ${coveragePercent >= 100 ? 'is-ready' : ''}`}
            >
                <div>
                    <span>Map readiness</span>
                    <strong>{coveragePercent}% ready</strong>
                    <p>
                        A ready property has both a real position and a clear
                        zone/land number for owner review.
                    </p>
                </div>
                <div className="pmc-map-readiness-actions">
                    <MapReadinessAction
                        count={needsPosition}
                        label="Need position"
                        href={firstNeedsPosition?.edit_href}
                    />
                    <MapReadinessAction
                        count={needsIdentity}
                        label="Need zone / land"
                        href={firstNeedsIdentity?.edit_href}
                    />
                </div>
            </div>

            <div className="pmc-property-map-controls">
                <label>
                    <span>Find land</span>
                    <input
                        type="search"
                        className="form-control"
                        value={mapSearch}
                        placeholder="Zone, land number, owner, manager..."
                        onChange={(event) =>
                            setMapSearch(event.currentTarget.value)
                        }
                    />
                </label>
                <label>
                    <span>Zone</span>
                    <select
                        className="form-select"
                        value={zoneFilter}
                        onChange={(event) =>
                            setZoneFilter(event.currentTarget.value)
                        }
                    >
                        <option value="all">All zones</option>
                        {zones.map((zone) => (
                            <option key={zone} value={zone}>
                                {zone}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    <span>Occupancy</span>
                    <select
                        className="form-select"
                        value={occupancyFilter}
                        onChange={(event) =>
                            setOccupancyFilter(event.currentTarget.value)
                        }
                    >
                        <option value="all">All occupancy</option>
                        {occupancyStates.map((state) => (
                            <option key={state} value={state}>
                                {state}
                            </option>
                        ))}
                    </select>
                </label>
                <div className="pmc-property-map-filter-status">
                    <strong>{visibleAssets.length}</strong>
                    <span>of {assets.length} shown</span>
                    <button
                        type="button"
                        className="btn btn-outline-secondary btn-sm"
                        onClick={resetMapFilters}
                    >
                        Reset
                    </button>
                </div>
            </div>

            <div className="pmc-property-map-layout">
                <div
                    className="pmc-property-map-canvas"
                    aria-label="Property map"
                >
                    <div className="pmc-map-grid" />
                    {visibleAssets.map((asset) => (
                        <Link
                            key={asset.id}
                            href={asset.href}
                            className={`pmc-map-parcel is-${asset.occupancy_status} ${selectedAsset.id === asset.id ? 'is-selected' : ''}`}
                            style={{
                                insetInlineStart: `${asset.x}%`,
                                top: `${asset.y}%`,
                            }}
                            title={`${asset.zone ?? 'Zone'} · ${asset.land_number ?? asset.code}`}
                            aria-label={`Open ${asset.land_number ?? asset.code} details`}
                            onFocus={() => setSelectedAssetId(asset.id)}
                            onMouseEnter={() => setSelectedAssetId(asset.id)}
                        >
                            <span>{asset.zone ?? 'No zone'}</span>
                            <strong>{asset.land_number ?? asset.code}</strong>
                            <em>Open</em>
                        </Link>
                    ))}
                    {!hasFilteredMap ? (
                        <div className="pmc-property-map-empty">
                            <strong>No matching land records</strong>
                            <span>
                                Clear filters or create map metadata on the
                                asset.
                            </span>
                        </div>
                    ) : null}
                </div>

                {hasFilteredMap ? (
                    <aside className="pmc-property-map-detail">
                        <span>{selectedAsset.zone ?? 'No zone recorded'}</span>
                        <h3>{selectedAsset.title}</h3>
                        <p>
                            {selectedAsset.land_number ?? selectedAsset.code} ·{' '}
                            {selectedAsset.address ?? 'No address recorded'}
                        </p>
                        <dl>
                            <div>
                                <dt>Value</dt>
                                <dd>
                                    {currency(
                                        selectedAsset.valuation_amount,
                                        locale,
                                        selectedAsset.currency,
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt>Occupancy</dt>
                                <dd>{selectedAsset.occupancy_status}</dd>
                            </div>
                            <div>
                                <dt>Units</dt>
                                <dd>{selectedAsset.rentable_children_count}</dd>
                            </div>
                            <div>
                                <dt>Open issues</dt>
                                <dd>{selectedAsset.open_requests_count}</dd>
                            </div>
                            <div>
                                <dt>Owner</dt>
                                <dd>{selectedAsset.owner ?? 'Not assigned'}</dd>
                            </div>
                            <div>
                                <dt>Manager</dt>
                                <dd>
                                    {selectedAsset.manager ?? 'Not assigned'}
                                </dd>
                            </div>
                            <div>
                                <dt>Active leases</dt>
                                <dd>{selectedAsset.active_leases_count}</dd>
                            </div>
                            <div>
                                <dt>Coordinates</dt>
                                <dd>
                                    {selectedAsset.latitude !== null &&
                                    selectedAsset.latitude !== undefined &&
                                    selectedAsset.longitude !== null &&
                                    selectedAsset.longitude !== undefined
                                        ? `${selectedAsset.latitude}, ${selectedAsset.longitude}`
                                        : 'Not set'}
                                </dd>
                            </div>
                        </dl>
                        <Link
                            href={selectedAsset.href}
                            className="btn btn-primary"
                        >
                            Open land details
                        </Link>
                    </aside>
                ) : (
                    <aside className="pmc-property-map-detail">
                        <span>No result selected</span>
                        <h3>Clear the map filters</h3>
                        <p>
                            No property matches the current zone, occupancy, or
                            search filter.
                        </p>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={resetMapFilters}
                        >
                            Show all properties
                        </button>
                    </aside>
                )}
            </div>

            <div className="pmc-property-map-list">
                {visibleAssets.map((asset) => (
                    <Link
                        key={asset.id}
                        href={asset.href}
                        className={
                            selectedAsset.id === asset.id ? 'is-selected' : ''
                        }
                        aria-label={`Open ${asset.land_number ?? asset.code} details`}
                        onFocus={() => setSelectedAssetId(asset.id)}
                        onMouseEnter={() => setSelectedAssetId(asset.id)}
                    >
                        <span>{asset.zone ?? 'No zone'}</span>
                        <strong>{asset.land_number ?? asset.code}</strong>
                        <em>{asset.title}</em>
                    </Link>
                ))}
            </div>
        </section>
    );
}

function MapReadinessAction({
    count,
    label,
    href,
}: {
    count: number;
    label: string;
    href?: string;
}) {
    if (count <= 0) {
        return (
            <span className="pmc-map-readiness-chip is-complete">
                <strong>0</strong>
                {label}
            </span>
        );
    }

    return (
        <Link
            href={href ?? '/assets'}
            className="pmc-map-readiness-chip is-needed"
        >
            <strong>{count}</strong>
            {label}
            <em>Fix</em>
        </Link>
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
