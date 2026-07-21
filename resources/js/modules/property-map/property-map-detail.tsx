import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import { statusLabel } from './map-utils';
import type { PropertyMapAsset } from './types';

export function PropertyMapDetail({
    asset,
}: {
    asset: PropertyMapAsset | null;
}) {
    const { locale, t } = useTranslator();

    if (!asset) {
        return (
            <aside
                className="pmc-property-map-detail"
                data-testid="property-map-detail"
            >
                <div className="pmc-property-map-detail-empty">
                    <span>
                        <i className="bi bi-cursor" />
                    </span>
                    <strong>{t('map.no_selection')}</strong>
                    <p>{t('map.no_selection_description')}</p>
                </div>
            </aside>
        );
    }

    return (
        <aside
            className="pmc-property-map-detail"
            data-testid="property-map-detail"
        >
            <header>
                <div>
                    <span>{asset.zone ?? t('map.no_zone')}</span>
                    <h3>{asset.title}</h3>
                    <p>
                        {asset.code} ·{' '}
                        {asset.land_number ?? t('map.no_land_number')}
                    </p>
                </div>
                <em className={asset.map_ready ? 'is-ready' : 'is-setup'}>
                    {asset.map_ready ? t('map.ready') : t('map.needs_setup')}
                </em>
            </header>

            {asset.is_showcase ? (
                <span className="pmc-showcase-badge">{t('map.showcase')}</span>
            ) : null}

            <p className="pmc-property-map-address">
                <i className="bi bi-geo-alt" />
                {asset.address ?? t('map.no_address')}
            </p>

            <dl className="pmc-property-map-facts">
                <div>
                    <dt>{t('map.value')}</dt>
                    <dd>
                        {currency(
                            asset.valuation_amount,
                            locale,
                            asset.currency,
                        )}
                    </dd>
                </div>
                <div>
                    <dt>{t('map.occupancy')}</dt>
                    <dd>{statusLabel(asset.occupancy_status, t)}</dd>
                </div>
                <div>
                    <dt>{t('map.units')}</dt>
                    <dd>{asset.rentable_children_count}</dd>
                </div>
                <div>
                    <dt>{t('map.open_issues')}</dt>
                    <dd>{asset.open_requests_count}</dd>
                </div>
            </dl>

            <div className="pmc-property-map-responsibility">
                <div>
                    <span>{t('map.owner')}</span>
                    <strong>{asset.owner ?? t('map.not_assigned')}</strong>
                </div>
                <div>
                    <span>{t('map.manager')}</span>
                    <strong>{asset.manager ?? t('map.not_assigned')}</strong>
                </div>
                <div>
                    <span>{t('map.active_leases')}</span>
                    <strong>{asset.active_leases_count}</strong>
                </div>
                <div>
                    <span>{t('map.coordinates')}</span>
                    <strong>
                        {asset.latitude !== null && asset.longitude !== null
                            ? `${asset.latitude}, ${asset.longitude}`
                            : t('map.not_set')}
                    </strong>
                </div>
            </div>

            <div className="pmc-property-map-detail-actions">
                <Link href={asset.href} className="btn btn-primary">
                    {t('map.open_property')}
                </Link>
                <Link
                    href={asset.edit_href}
                    className="btn btn-outline-secondary"
                >
                    {t('map.edit_map_data')}
                </Link>
            </div>
        </aside>
    );
}
