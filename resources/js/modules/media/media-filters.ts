import type { TableFilterField } from '@/components/data-table';
import type { Translator } from '@/lib/i18n';

import type { MediaIndexPageProps } from './types';

export function mediaFilterFields(
    props: MediaIndexPageProps,
    t: Translator,
): TableFilterField[] {
    const fields: TableFilterField[] = [
        {
            name: 'visibility',
            label: t('media.visibility'),
            options: [
                { label: t('table.all'), value: 'all' },
                { label: t('media.public'), value: 'public' },
                { label: t('media.private'), value: 'private' },
            ],
        },
        {
            name: 'collection',
            label: t('media.collection'),
            options: [
                { label: t('media.all_collections'), value: 'all' },
                ...props.collectionOptions.map((collection) => ({
                    label: collection,
                    value: collection,
                })),
            ],
        },
    ];

    if (props.auth.user?.roles.includes('superadmin')) {
        fields.push({
            name: 'portfolio_id',
            label: t('media.portfolio'),
            options: [
                { label: t('media.all_portfolios'), value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}
