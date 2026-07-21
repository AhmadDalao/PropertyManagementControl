import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { PropertyMapAsset } from './types';

export function PropertyMapSetupStatus({
    assets,
    setupAssets,
}: {
    assets: PropertyMapAsset[];
    setupAssets: PropertyMapAsset[];
}) {
    const { t } = useTranslator();

    if (setupAssets.length > 0) {
        return (
            <div className="pmc-property-map-setup" role="status">
                <span>
                    <i className="bi bi-exclamation-triangle" />
                </span>
                <div>
                    <strong>
                        {t('map.setup_title', undefined, {
                            count: setupAssets.length,
                        })}
                    </strong>
                    <p>{t('map.setup_description')}</p>
                </div>
                <Link
                    href={setupAssets[0].edit_href}
                    className="btn btn-outline-secondary"
                >
                    {t('map.complete_setup')}
                    <i className="bi bi-arrow-right" />
                </Link>
            </div>
        );
    }

    return assets.length > 0 ? (
        <div className="pmc-property-map-setup is-ready" role="status">
            <span>
                <i className="bi bi-check2-circle" />
            </span>
            <div>
                <strong>{t('map.setup_complete')}</strong>
                <p>{t('map.setup_complete_description')}</p>
            </div>
        </div>
    ) : null;
}
