import type { TableFilterField } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';

type DocumentFilterOptions = {
    types: string[];
    attachments: string[];
    visibilities: string[];
    portfolios: Array<{ id: number; name: string }>;
    includePortfolio: boolean;
};

export function useDocumentFilterFields({
    types,
    attachments,
    visibilities,
    portfolios,
    includePortfolio,
}: DocumentFilterOptions): TableFilterField[] {
    const { t } = useTranslator();
    const fields: TableFilterField[] = [
        selectField(
            'type',
            t('documents.filter_type'),
            types,
            t('documents.all'),
            t,
        ),
        selectField(
            'attachment',
            t('documents.attached_to'),
            attachments,
            t('documents.all'),
            t,
        ),
        {
            name: 'visibility',
            label: t('documents.tenant_portal'),
            options: [
                { label: t('documents.all'), value: 'all' },
                ...visibilities.map((visibility) => ({
                    label:
                        visibility === 'public'
                            ? t('documents.portal_visible')
                            : t('documents.internal'),
                    value: visibility,
                })),
            ],
        },
        { name: 'date_from', label: t('documents.from'), type: 'date' },
        { name: 'date_to', label: t('documents.to'), type: 'date' },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: t('documents.portfolio'),
            options: [
                { label: t('documents.all'), value: 'all' },
                ...portfolios.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}

function selectField(
    name: string,
    label: string,
    options: string[],
    allLabel: string,
    translate: ReturnType<typeof useTranslator>['t'],
): TableFilterField {
    return {
        name,
        label,
        options: [
            { label: allLabel, value: 'all' },
            ...options.map((option) => ({
                label: translate(
                    `documents.options.${option}` as UiTranslationKey,
                    option.replaceAll('_', ' '),
                ),
                value: option,
            })),
        ],
    };
}
