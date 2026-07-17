import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ChangeEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { AdminLayout } from '@/layouts/admin-layout';
import { currency } from '@/lib/utils';
import type { PropertyMapAsset } from '@/modules/dashboard/types';
import { PropertyMap } from '@/modules/dashboard/widgets';
import type { SharedProps } from '@/types';

type PropertyMapPageProps = SharedProps & {
    propertyMap: {
        assets: PropertyMapAsset[];
        summary: {
            mapped: number;
            total: number;
            ready: number;
            needs_position: number;
            needs_identity: number;
            coverage_percent: number;
            zones: string[];
        };
    };
    portfolioOptions: Array<{ id: number; name: string }>;
    filters: {
        portfolio_id?: number | null;
    };
};

export default function PropertyMapPage() {
    const { props } = usePage<PropertyMapPageProps>();
    const isSuperadmin = props.auth.user?.roles.includes('superadmin') ?? false;
    const totalValue = props.propertyMap.assets.reduce(
        (sum, asset) => sum + Number(asset.valuation_amount ?? 0),
        0,
    );
    const selectedPortfolio = props.filters.portfolio_id
        ? String(props.filters.portfolio_id)
        : 'all';
    const setupQueue = props.propertyMap.assets.filter(
        (asset) => !asset.has_coordinates || !asset.has_identity,
    );

    const changePortfolio = (event: ChangeEvent<HTMLSelectElement>) => {
        const portfolioId = event.currentTarget.value;

        router.get(
            '/property-map',
            portfolioId === 'all' ? {} : { portfolio_id: portfolioId },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <AdminLayout>
            <Head title="Properties Map" />

            <PageHeader
                title="Properties Map"
                description="See every scoped property, zone, and land number in one owner-first map. Click any land label to open the asset detail page."
                actions={
                    <>
                        <Link href="/assets/create" className="btn btn-primary">
                            <i className="bi bi-plus-lg me-2" />
                            Create asset
                        </Link>
                        <Link
                            href="/assets"
                            className="btn btn-outline-secondary"
                        >
                            Asset workspace
                        </Link>
                    </>
                }
            />

            <section className="pmc-map-command-strip mb-4">
                <div>
                    <div className="pmc-kicker mb-2">Owner map command</div>
                    <h2>Open the property from its zone or land number.</h2>
                    <p>
                        This page is intentionally map-first. Tables and bulk
                        edits stay in the asset workspace; this screen is for
                        finding land and opening the exact record fast.
                    </p>
                </div>

                {isSuperadmin ? (
                    <label className="pmc-map-portfolio-filter">
                        <span>Portfolio</span>
                        <select
                            className="form-select"
                            value={selectedPortfolio}
                            onChange={changePortfolio}
                        >
                            <option value="all">All portfolios</option>
                            {props.portfolioOptions.map((portfolio) => (
                                <option key={portfolio.id} value={portfolio.id}>
                                    {portfolio.name}
                                </option>
                            ))}
                        </select>
                    </label>
                ) : null}
            </section>

            <div className="row g-3 mb-4">
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Properties shown"
                        value={props.propertyMap.summary.total}
                        hint={`${props.propertyMap.summary.zones.length} zones in scope`}
                        tone="accent"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Map ready"
                        value={`${props.propertyMap.summary.coverage_percent}%`}
                        hint={`${props.propertyMap.summary.ready} records have position and identity`}
                        tone="teal"
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Needs setup"
                        value={
                            props.propertyMap.summary.needs_position +
                            props.propertyMap.summary.needs_identity
                        }
                        hint={`${props.propertyMap.summary.needs_position} position · ${props.propertyMap.summary.needs_identity} zone/land`}
                    />
                </div>
                <div className="col-sm-6 col-xl-3">
                    <StatCard
                        title="Mapped value"
                        value={currency(totalValue, props.app.locale)}
                        hint="Value of records currently shown on the map"
                    />
                </div>
            </div>

            <MapSetupQueue assets={setupQueue} />

            <PropertyMap
                assets={props.propertyMap.assets}
                locale={props.app.locale}
                summary={props.propertyMap.summary}
            />
        </AdminLayout>
    );
}

function MapSetupQueue({ assets }: { assets: PropertyMapAsset[] }) {
    return (
        <section className="pmc-map-setup-queue mb-4">
            <div className="pmc-map-setup-head">
                <div>
                    <div className="pmc-kicker mb-2">Map setup queue</div>
                    <h2>Fix the records blocking the owner map.</h2>
                    <p>
                        Every card below needs either a real position or a clear
                        zone/land label. Fix these first so the map stops
                        guessing.
                    </p>
                </div>
                <span
                    className={`pmc-map-setup-count ${assets.length === 0 ? 'is-clear' : ''}`}
                >
                    {assets.length === 0
                        ? 'All records ready'
                        : `${assets.length} need setup`}
                </span>
            </div>

            {assets.length === 0 ? (
                <div className="pmc-map-setup-empty">
                    <i className="bi bi-check2-circle" />
                    <div>
                        <strong>Map data is complete.</strong>
                        <span>
                            Every scoped property has position and zone/land
                            identity.
                        </span>
                    </div>
                </div>
            ) : (
                <div className="pmc-map-setup-grid">
                    {assets.map((asset) => {
                        const missingPosition = !asset.has_coordinates;
                        const missingIdentity = !asset.has_identity;

                        return (
                            <article
                                key={asset.id}
                                className="pmc-map-setup-card"
                            >
                                <div className="pmc-map-setup-card-head">
                                    <span>{asset.code}</span>
                                    <strong>{asset.title}</strong>
                                    <em>
                                        {asset.zone ?? 'No zone'} ·{' '}
                                        {asset.land_number ?? 'No land number'}
                                    </em>
                                </div>

                                <div
                                    className="pmc-map-setup-missing"
                                    aria-label="Missing map data"
                                >
                                    {missingPosition ? (
                                        <span>
                                            <i className="bi bi-geo-alt" />
                                            Missing position
                                        </span>
                                    ) : null}
                                    {missingIdentity ? (
                                        <span>
                                            <i className="bi bi-signpost" />
                                            Missing zone / land
                                        </span>
                                    ) : null}
                                </div>

                                <dl>
                                    <div>
                                        <dt>Owner</dt>
                                        <dd>{asset.owner ?? 'Not assigned'}</dd>
                                    </div>
                                    <div>
                                        <dt>Manager</dt>
                                        <dd>
                                            {asset.manager ?? 'Not assigned'}
                                        </dd>
                                    </div>
                                </dl>

                                <div className="pmc-map-setup-actions">
                                    <Link
                                        href={asset.edit_href}
                                        className="btn btn-primary btn-sm"
                                    >
                                        Edit map data
                                    </Link>
                                    <Link
                                        href={asset.href}
                                        className="btn btn-outline-secondary btn-sm"
                                    >
                                        Open detail
                                    </Link>
                                </div>
                            </article>
                        );
                    })}
                </div>
            )}
        </section>
    );
}
