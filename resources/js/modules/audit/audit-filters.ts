import type { TableFilterField } from '@/components/data-table';
import type { Translator } from '@/lib/i18n';

import type { AuditIndexPageProps } from './types';

export function auditFilterFields(
    props: AuditIndexPageProps,
    t: Translator,
): TableFilterField[] {
    const fields: TableFilterField[] = [
        {
            name: 'event',
            label: t('audit.event'),
            options: [
                { label: t('audit.events.all'), value: 'all' },
                { label: t('audit.events.created'), value: 'created' },
                { label: t('audit.events.updated'), value: 'updated' },
                { label: t('audit.events.deleted'), value: 'deleted' },
            ],
        },
        {
            name: 'subject_type',
            label: t('audit.subject_type'),
            options: [
                { label: t('audit.all_subjects'), value: 'all' },
                ...props.subjectTypeOptions,
            ],
        },
        {
            name: 'causer_id',
            label: t('audit.changed_by'),
            options: [
                { label: t('audit.all_people'), value: 'all' },
                ...props.causerOptions.map((user) => ({
                    label: user.name,
                    value: user.id,
                })),
            ],
        },
        { name: 'date_from', label: t('audit.date_from'), type: 'date' },
        { name: 'date_to', label: t('audit.date_to'), type: 'date' },
    ];

    if (props.auth.user?.roles.includes('superadmin')) {
        fields.push({
            name: 'portfolio_id',
            label: t('audit.portfolio'),
            options: [
                { label: t('audit.all_portfolios'), value: 'all' },
                ...props.portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}
