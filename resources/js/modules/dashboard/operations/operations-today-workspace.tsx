import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps, OperationsWorkSection } from '../types';
import { OperationsActionQueue } from './action-queue';
import { OperationsPriorityPanels } from './operations-priority-panels';
import { workPanelClass } from './work-panel';

const sectionOrder: OperationsWorkSection[] = [
    'actions',
    'collections',
    'maintenance',
    'move_outs',
];

export function OperationsTodayWorkspace({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t, text } = useTranslator();
    const counts: Record<OperationsWorkSection, number> = {
        actions: props.nextActions.length,
        collections: props.collectionQueue.length,
        maintenance: props.stats.openRequests,
        move_outs: props.moveOutQueue.attention + props.moveOutQueue.ready,
    };
    const [active, setActive] = useState<OperationsWorkSection>(() =>
        resolveWorkSection(
            typeof window === 'undefined'
                ? null
                : new URLSearchParams(window.location.search).get('work'),
            counts,
        ),
    );
    const labels: Record<OperationsWorkSection, string> = {
        actions: text('Next actions'),
        collections: t('dashboard.collections'),
        maintenance: t('dashboard.service'),
        move_outs: t('dashboard.handover'),
    };
    const select = (section: OperationsWorkSection) => {
        setActive(section);
        const url = new URL(window.location.href);
        url.searchParams.set('work', section);
        window.history.replaceState({}, '', url);
    };

    return (
        <div className="pmc-dashboard-today-workspace">
            <nav
                className="pmc-dashboard-today-tabs"
                aria-label={t('dashboard.mobile_today_title')}
            >
                {sectionOrder.map((section) => (
                    <button
                        key={section}
                        type="button"
                        data-dashboard-work-tab={section}
                        aria-pressed={active === section}
                        className={active === section ? 'is-active' : ''}
                        onClick={() => select(section)}
                    >
                        <span>{labels[section]}</span>
                        <strong>
                            {localizedNumber(counts[section], locale)}
                        </strong>
                    </button>
                ))}
            </nav>

            <div
                className={workPanelClass('actions', active)}
                data-dashboard-work-panel="actions"
            >
                <OperationsActionQueue actions={props.nextActions} />
            </div>
            <OperationsPriorityPanels active={active} props={props} />
        </div>
    );
}

function resolveWorkSection(
    requested: string | null,
    counts: Record<OperationsWorkSection, number>,
): OperationsWorkSection {
    if (sectionOrder.includes(requested as OperationsWorkSection)) {
        return requested as OperationsWorkSection;
    }

    return sectionOrder.find((section) => counts[section] > 0) ?? 'actions';
}
