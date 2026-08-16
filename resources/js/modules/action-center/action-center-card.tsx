import { Link } from '@inertiajs/react';

import { ShowcaseBadge } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { ActionCenterCardContext } from './action-center-card-context';
import { ActionCenterCardFacts } from './action-center-card-facts';
import type { ActionCenterItem } from './types';

export function ActionCenterCard({
    item,
    showPortfolio,
}: {
    item: ActionCenterItem;
    showPortfolio: boolean;
}) {
    const { t } = useTranslator();

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
                    <span
                        className={`pmc-action-due-state is-${item.due_state}`}
                    >
                        {t(`action_center.due_state_${item.due_state}`)}
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

            <ActionCenterCardContext
                item={item}
                showPortfolio={showPortfolio}
            />
            <ActionCenterCardFacts item={item} />

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
                    <span>
                        {item.work_order
                            ? t('action_center.action_work_order')
                            : actionLabel(item.type, t)}
                    </span>
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                </Link>
            </footer>
        </article>
    );
}

function typeIcon(type: ActionCenterItem['type']): string {
    return {
        collection: 'bi-cash-stack',
        maintenance: 'bi-tools',
        renewal: 'bi-calendar-event',
        move_out: 'bi-box-arrow-right',
        document_expiry: 'bi-file-earmark-text',
    }[type];
}

function actionLabel(
    type: ActionCenterItem['type'],
    t: ReturnType<typeof useTranslator>['t'],
): string {
    return t(`action_center.action_${type}`);
}
