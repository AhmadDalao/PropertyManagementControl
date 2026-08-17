import { useState } from 'react';
import type { KeyboardEvent } from 'react';

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
    const selectFromKeyboard = (
        event: KeyboardEvent<HTMLButtonElement>,
        index: number,
    ) => {
        let nextIndex = index;
        const direction = locale === 'ar' ? -1 : 1;

        if (event.key === 'ArrowRight') {
            nextIndex =
                (index + direction + sectionOrder.length) % sectionOrder.length;
        } else if (event.key === 'ArrowLeft') {
            nextIndex =
                (index - direction + sectionOrder.length) % sectionOrder.length;
        } else if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = sectionOrder.length - 1;
        } else {
            return;
        }

        event.preventDefault();
        const next = sectionOrder[nextIndex];
        select(next);
        document.getElementById(`dashboard-work-tab-${next}`)?.focus();
    };

    return (
        <div className="pmc-dashboard-today-workspace">
            <div
                className="pmc-dashboard-today-tabs"
                role="tablist"
                aria-label={t('dashboard.mobile_today_title')}
            >
                {sectionOrder.map((section, index) => (
                    <button
                        key={section}
                        id={`dashboard-work-tab-${section}`}
                        type="button"
                        role="tab"
                        data-dashboard-work-tab={section}
                        aria-selected={active === section}
                        aria-controls={`dashboard-work-panel-${section}`}
                        tabIndex={active === section ? 0 : -1}
                        className={active === section ? 'is-active' : ''}
                        onClick={() => select(section)}
                        onKeyDown={(event) => selectFromKeyboard(event, index)}
                    >
                        <span>{labels[section]}</span>
                        <strong>
                            {localizedNumber(counts[section], locale)}
                        </strong>
                    </button>
                ))}
            </div>

            {active === 'actions' ? (
                <div
                    id="dashboard-work-panel-actions"
                    className={workPanelClass('actions', active)}
                    data-dashboard-work-panel="actions"
                    role="tabpanel"
                    aria-labelledby="dashboard-work-tab-actions"
                >
                    <OperationsActionQueue actions={props.nextActions} />
                </div>
            ) : null}
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
