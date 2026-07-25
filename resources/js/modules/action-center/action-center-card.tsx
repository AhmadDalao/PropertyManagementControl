import { Link } from '@inertiajs/react';

import { ShowcaseBadge } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { ActionCenterItem } from './types';

export function ActionCenterCard({ item }: { item: ActionCenterItem }) {
    const { locale, t } = useTranslator();
    const asset = localized(item.asset?.title_en, item.asset?.title_ar, locale);
    const portfolio = localized(
        item.portfolio?.name_en,
        item.portfolio?.name_ar,
        locale,
    );

    return (
        <article
            className={`pmc-action-card is-${item.priority}`}
            data-action-type={item.type}
        >
            <header className="pmc-action-card-head">
                <div className="pmc-action-card-badges">
                    <span className={`pmc-action-type is-${item.type}`}>
                        <i
                            className={`bi ${typeIcon(item.type)}`}
                            aria-hidden="true"
                        />
                        {t(`action_center.type_${item.type}`)}
                    </span>
                    <span className={`pmc-action-priority is-${item.priority}`}>
                        {t(`action_center.priority_${item.priority}`)}
                    </span>
                    {item.is_showcase ? (
                        <ShowcaseBadge label={t('showcase.badge')} />
                    ) : null}
                </div>
                <Link href={item.href}>
                    <h3>{item.title}</h3>
                </Link>
                {item.subtitle ? <p>{item.subtitle}</p> : null}
            </header>

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
                {portfolio ? (
                    <ContextValue
                        icon="bi-buildings"
                        label={t('action_center.portfolio')}
                        value={portfolio}
                    />
                ) : null}
            </dl>

            <div className="pmc-action-card-timing">
                <div>
                    <span>{t('action_center.due')}</span>
                    <strong>
                        {item.due_on
                            ? humanDate(item.due_on, locale)
                            : t('action_center.no_due_date')}
                    </strong>
                </div>
                <span className={`is-${item.due_state}`}>
                    {t(`action_center.due_state_${item.due_state}`)}
                </span>
            </div>

            <div className="pmc-action-card-state">
                <div>
                    <span>{t('action_center.status')}</span>
                    <strong>{t(`action_center.status_${item.status}`)}</strong>
                </div>
                {item.amount !== null && item.amount !== undefined ? (
                    <div>
                        <span>{t('action_center.amount')}</span>
                        <strong>
                            {currency(
                                item.amount,
                                locale,
                                item.currency ?? 'SAR',
                            )}
                        </strong>
                    </div>
                ) : null}
            </div>

            <footer className="pmc-action-card-footer">
                <div
                    className={
                        item.assigned_to
                            ? 'pmc-action-assignee'
                            : 'pmc-action-assignee is-unassigned'
                    }
                >
                    <i
                        className={
                            item.assigned_to
                                ? 'bi bi-person-check'
                                : 'bi bi-person-exclamation'
                        }
                        aria-hidden="true"
                    />
                    <span>
                        {item.assigned_to?.name ??
                            t('action_center.assignee_unassigned')}
                    </span>
                </div>
                <Link href={item.href} className="pmc-action-open">
                    <span>{actionLabel(item.type, t)}</span>
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                </Link>
            </footer>
        </article>
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

function typeIcon(type: ActionCenterItem['type']): string {
    return {
        collection: 'bi-cash-stack',
        maintenance: 'bi-tools',
        renewal: 'bi-calendar-event',
        move_out: 'bi-box-arrow-right',
    }[type];
}

function actionLabel(
    type: ActionCenterItem['type'],
    t: ReturnType<typeof useTranslator>['t'],
): string {
    return t(`action_center.action_${type}`);
}
