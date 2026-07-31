import { Link } from '@inertiajs/react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { humanDate, localizedNumber } from '@/lib/utils';

import type { PropertyExplorerAsset } from './types';

export function ExplorerRecordCard({
    asset,
}: {
    asset: PropertyExplorerAsset;
}) {
    const { locale, t } = useTranslator();
    const parentTitle = asset.parent
        ? localizedTitle(asset.parent, locale)
        : null;

    return (
        <article
            className="pmc-explorer-record"
            data-testid="property-explorer-record"
        >
            <header>
                <div>
                    <span>{asset.code}</span>
                    <Link href={asset.browse_href}>
                        {localizedTitle(asset, locale)}
                    </Link>
                    <small>
                        {t(`assets.types.${asset.asset_type}`)}
                        {parentTitle ? ` · ${parentTitle}` : ''}
                    </small>
                </div>
                <StatusBadge value={asset.occupancy_status} />
            </header>

            <div className="pmc-explorer-record-context">
                {asset.lease ? (
                    <>
                        <span>{t('assets.explorer.tenant')}</span>
                        <strong>
                            {asset.lease.tenant_name ??
                                t('assets.explorer.unknown_tenant')}
                        </strong>
                        <small>
                            {asset.lease.code} ·{' '}
                            {humanDate(asset.lease.ends_at, locale)}
                        </small>
                    </>
                ) : asset.children_count > 0 ? (
                    <>
                        <span>{t('assets.explorer.structure')}</span>
                        <strong>
                            {t('assets.explorer.child_count', undefined, {
                                count: localizedNumber(
                                    asset.children_count,
                                    locale,
                                ),
                            })}
                        </strong>
                        <small>{t('assets.explorer.open_to_continue')}</small>
                    </>
                ) : (
                    <>
                        <span>{t('assets.explorer.availability')}</span>
                        <strong>{t(`status.${asset.occupancy_status}`)}</strong>
                        <small>
                            {asset.rentable
                                ? t('assets.explorer.ready_for_lease')
                                : t('assets.explorer.non_rentable')}
                        </small>
                    </>
                )}
            </div>

            <dl>
                <div>
                    <dt>{t('assets.explorer.owner')}</dt>
                    <dd>{asset.owner?.name ?? '-'}</dd>
                </div>
                <div>
                    <dt>{t('assets.explorer.manager')}</dt>
                    <dd>{asset.manager?.name ?? '-'}</dd>
                </div>
            </dl>

            <footer>
                <Link href={asset.browse_href} className="is-primary">
                    {asset.children_count > 0
                        ? t('assets.explorer.browse_inside')
                        : t('assets.explorer.open_unit')}
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                </Link>
                <Link href={asset.detail_href}>
                    {t('assets.explorer.full_details')}
                </Link>
            </footer>
        </article>
    );
}

function localizedTitle(
    record: { title_en: string; title_ar?: string | null },
    locale: string,
) {
    return locale === 'ar'
        ? record.title_ar || record.title_en
        : record.title_en || record.title_ar || '';
}
