import type { TableFilterField } from '@/components/data-table';
import type { Translator } from '@/lib/i18n';

import type { EmailDeliveryIndexPageProps } from './types';

export function emailDeliveryFilterFields(
    props: EmailDeliveryIndexPageProps,
    t: Translator,
): TableFilterField[] {
    return [
        {
            name: 'email_type',
            label: t('email_delivery.type'),
            options: [
                {
                    label: t('email_delivery.all_types'),
                    value: 'all',
                },
                ...props.typeOptions,
            ],
        },
        {
            name: 'date_from',
            label: t('email_delivery.date_from'),
            type: 'date',
        },
        {
            name: 'date_to',
            label: t('email_delivery.date_to'),
            type: 'date',
        },
    ];
}
