import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { PropertyMapAsset } from './types';

type PropertyMapDirectoryProps = {
    assets: PropertyMapAsset[];
    visibleCount: number;
    selectedAsset: PropertyMapAsset | null;
    safePage: number;
    lastPage: number;
    onSelect: (asset: PropertyMapAsset) => void;
    onPrevious: () => void;
    onNext: () => void;
    onReset: () => void;
};

export function PropertyMapDirectory({
    assets,
    visibleCount,
    selectedAsset,
    safePage,
    lastPage,
    onSelect,
    onPrevious,
    onNext,
    onReset,
}: PropertyMapDirectoryProps) {
    const { t } = useTranslator();

    return (
        <section
            className="pmc-property-map-directory"
            data-testid="property-map-directory"
        >
            <header>
                <div>
                    <span>{t('map.directory_eyebrow')}</span>
                    <h3>{t('map.directory_title')}</h3>
                    <p>{t('map.directory_description')}</p>
                </div>
                <strong>
                    {t('map.record_count', undefined, { count: visibleCount })}
                </strong>
            </header>

            {assets.length > 0 ? (
                <>
                    <div className="pmc-property-map-records">
                        {assets.map((asset) => (
                            <article
                                key={asset.id}
                                className={
                                    selectedAsset?.id === asset.id
                                        ? 'is-selected'
                                        : ''
                                }
                                data-testid="property-map-record"
                            >
                                <button
                                    type="button"
                                    onClick={() => onSelect(asset)}
                                >
                                    <span>
                                        {asset.zone ?? t('map.no_zone')}
                                    </span>
                                    <strong>{asset.title}</strong>
                                    <small>
                                        {asset.code} ·{' '}
                                        {asset.land_number ??
                                            t('map.no_land_number')}
                                    </small>
                                </button>
                                {asset.is_showcase ? (
                                    <span className="pmc-showcase-badge">
                                        {t('map.showcase')}
                                    </span>
                                ) : null}
                                <span
                                    className={`pmc-property-map-record-status ${asset.map_ready ? 'is-ready' : ''}`}
                                >
                                    {asset.map_ready
                                        ? t('map.ready')
                                        : t('map.needs_setup')}
                                </span>
                                <Link
                                    href={asset.href}
                                    aria-label={t(
                                        'map.open_property_named',
                                        undefined,
                                        { property: asset.title },
                                    )}
                                >
                                    <i className="bi bi-arrow-up-right" />
                                </Link>
                            </article>
                        ))}
                    </div>
                    {lastPage > 1 ? (
                        <nav
                            className="pmc-property-map-pagination"
                            aria-label={t('map.directory_title')}
                        >
                            <button
                                type="button"
                                className="btn btn-outline-secondary"
                                disabled={safePage === 1}
                                onClick={onPrevious}
                            >
                                {t('map.previous_records')}
                            </button>
                            <span>
                                {t('map.directory_page', undefined, {
                                    page: safePage,
                                    pages: lastPage,
                                })}
                            </span>
                            <button
                                type="button"
                                className="btn btn-outline-secondary"
                                disabled={safePage === lastPage}
                                onClick={onNext}
                            >
                                {t('map.next_records')}
                            </button>
                        </nav>
                    ) : null}
                </>
            ) : (
                <div className="pmc-property-map-no-results">
                    <strong>{t('map.no_results')}</strong>
                    <p>{t('map.no_results_description')}</p>
                    <button
                        type="button"
                        className="btn btn-outline-secondary"
                        onClick={onReset}
                    >
                        {t('map.show_all')}
                    </button>
                </div>
            )}
        </section>
    );
}
