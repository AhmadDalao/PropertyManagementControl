import { useTranslator } from '@/lib/i18n';

import { ActionCenterWorkOrderContext } from './action-center-work-order-context';
import type { ActionCenterItem } from './types';

export function ActionCenterCardContext({
    item,
    showPortfolio,
}: {
    item: ActionCenterItem;
    showPortfolio: boolean;
}) {
    const { locale, t } = useTranslator();
    const asset = localized(item.asset?.title_en, item.asset?.title_ar, locale);
    const portfolio = localized(
        item.portfolio?.name_en,
        item.portfolio?.name_ar,
        locale,
    );

    return (
        <dl className="pmc-action-card-context">
            {item.tenant ? (
                <ContextValue
                    icon="bi-person"
                    label={t('action_center.tenant')}
                    value={item.tenant}
                />
            ) : null}
            {asset ? (
                <ContextValue
                    icon="bi-building"
                    label={t('action_center.asset')}
                    value={[asset, item.asset?.code]
                        .filter(Boolean)
                        .join(' · ')}
                />
            ) : null}
            {showPortfolio && portfolio ? (
                <ContextValue
                    icon="bi-buildings"
                    label={t('action_center.portfolio')}
                    value={portfolio}
                />
            ) : null}
            {item.work_order ? (
                <ActionCenterWorkOrderContext workOrder={item.work_order} />
            ) : null}
        </dl>
    );
}

function ContextValue({
    icon,
    label,
    value,
}: {
    icon: string;
    label: string;
    value: string;
}) {
    return (
        <div>
            <dt>
                <i className={`bi ${icon}`} aria-hidden="true" />
                {label}
            </dt>
            <dd>{value}</dd>
        </div>
    );
}

function localized(
    english: string | null | undefined,
    arabic: string | null | undefined,
    locale: string,
): string | null {
    return locale === 'ar'
        ? arabic || english || null
        : english || arabic || null;
}
