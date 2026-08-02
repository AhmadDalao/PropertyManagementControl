import { Link } from '@inertiajs/react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { ExplorerNodeFacts } from './explorer-node-facts';
import { ExplorerTenancyPanel } from './explorer-tenancy-panel';
import type { PropertyExplorerPayload, PropertyExplorerView } from './types';

export function ExplorerFocusPanel({
    explorer,
    canCreate,
    activeView,
}: {
    explorer: PropertyExplorerPayload;
    canCreate: boolean;
    activeView: PropertyExplorerView;
}) {
    const { locale, t } = useTranslator();
    const selected = explorer.selected;

    if (!selected) {
        return null;
    }

    return (
        <section className={`pmc-explorer-focus is-${activeView}`}>
            <header>
                <div>
                    <span>{selected.code}</span>
                    <h2>{localizedTitle(selected, locale)}</h2>
                    <div className="pmc-explorer-focus-badges">
                        <StatusBadge value={selected.occupancy_status} />
                        <em>{t(`assets.types.${selected.asset_type}`)}</em>
                    </div>
                </div>
                <div className="pmc-explorer-focus-actions">
                    <Link href={selected.detail_href} className="btn btn-light">
                        {t('assets.explorer.full_details')}
                    </Link>
                    {canCreate ? (
                        <>
                            <Link
                                href={selected.edit_href}
                                className="btn btn-light"
                            >
                                <i
                                    className="bi bi-pencil"
                                    aria-hidden="true"
                                />
                                {t('actions.edit')}
                            </Link>
                            <Link
                                href={selected.add_child_href}
                                className="btn btn-primary"
                            >
                                <i
                                    className="bi bi-plus-lg"
                                    aria-hidden="true"
                                />
                                {t('assets.explorer.add_inside')}
                            </Link>
                        </>
                    ) : null}
                </div>
            </header>

            <div className="pmc-explorer-focus-body">
                <ExplorerNodeFacts selected={selected} />
                <ExplorerTenancyPanel
                    selected={selected}
                    lease={explorer.active_lease}
                    modules={explorer.modules}
                    canCreate={canCreate}
                />
            </div>
        </section>
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
