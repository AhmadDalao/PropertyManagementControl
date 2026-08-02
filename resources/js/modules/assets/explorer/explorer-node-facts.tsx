import { useTranslator } from '@/lib/i18n';
import { currency, localizedNumber } from '@/lib/utils';

import { ExplorerFact } from './explorer-fact';
import type { PropertyExplorerSelected } from './types';

export function ExplorerNodeFacts({
    selected,
}: {
    selected: PropertyExplorerSelected;
}) {
    const { locale, t } = useTranslator();

    return (
        <article
            className="pmc-explorer-facts"
            data-explorer-focus-section="overview"
        >
            <span>{t('assets.explorer.node_overview')}</span>
            <dl>
                <ExplorerFact
                    label={t('assets.explorer.child_records')}
                    value={localizedNumber(selected.children_count, locale)}
                />
                <ExplorerFact
                    label={t('assets.value')}
                    value={currency(
                        selected.valuation_amount,
                        locale,
                        selected.currency,
                    )}
                />
                <ExplorerFact
                    label={t('assets.area')}
                    value={
                        selected.area
                            ? `${localizedNumber(selected.area, locale)} m²`
                            : '-'
                    }
                />
                <ExplorerFact
                    label={t('assets.explorer.manager')}
                    value={selected.manager?.name ?? '-'}
                />
                <ExplorerFact
                    label={t('assets.explorer.owner')}
                    value={selected.owner?.name ?? '-'}
                />
                <ExplorerFact
                    label={t('assets.address')}
                    value={selected.address ?? '-'}
                />
            </dl>
        </article>
    );
}
