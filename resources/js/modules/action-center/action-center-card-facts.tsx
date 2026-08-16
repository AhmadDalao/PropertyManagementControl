import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { ActionCenterItem } from './types';

export function ActionCenterCardFacts({ item }: { item: ActionCenterItem }) {
    const { locale, t } = useTranslator();
    const amount = item.amount;
    const hasAmount = amount !== null && amount !== undefined;

    return (
        <dl
            className={`pmc-action-card-facts ${hasAmount ? 'has-amount' : ''}`}
        >
            <Fact
                label={t('action_center.due')}
                value={
                    item.due_on
                        ? humanDate(item.due_on, locale)
                        : t('action_center.no_due_date')
                }
            />
            <Fact
                label={t('action_center.status')}
                value={t(`action_center.status_${item.status}`)}
            />
            {hasAmount ? (
                <Fact
                    label={t('action_center.amount')}
                    value={currency(amount, locale, item.currency ?? 'SAR')}
                />
            ) : null}
        </dl>
    );
}

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt>{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}
