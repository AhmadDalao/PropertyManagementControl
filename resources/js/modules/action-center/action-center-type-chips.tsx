import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { actionCenterUrl } from './action-center-query';
import type { ActionCenterFilters, ActionCenterTypeCount } from './types';

export function ActionCenterTypeChips({
    counts,
    filters,
}: {
    counts: ActionCenterTypeCount[];
    filters: ActionCenterFilters;
}) {
    const { locale, t } = useTranslator();

    return (
        <div
            className="pmc-action-type-chips"
            aria-label={t('action_center.type_filter')}
        >
            {counts.map((count) => (
                <Link
                    key={count.type}
                    href={actionCenterUrl(filters, {
                        type: count.type,
                        page: 1,
                    })}
                    className={count.active ? 'is-active' : ''}
                    aria-current={count.active ? 'page' : undefined}
                >
                    <span>
                        {count.type === 'all'
                            ? t('action_center.type_all')
                            : t(`action_center.type_${count.type}`)}
                    </span>
                    <strong>{localizedNumber(count.value, locale)}</strong>
                </Link>
            ))}
        </div>
    );
}
