import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';
import { LaunchReadinessPanel } from './launch-readiness-panel';
import { PlatformActivityPanel } from './platform-activity-panel';
import { PlatformCompositionPanel } from './platform-composition-panel';
import { PlatformStatusPanel } from './platform-status-panel';

type SystemSection = 'readiness' | 'activity' | 'company' | 'website';

export function OperationsSystemWorkspace({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const available: SystemSection[] = [
        ...(props.readinessStatus ? (['readiness'] as const) : []),
        'activity',
        ...(props.platformComposition ? (['company'] as const) : []),
        ...(props.cmsStatus ? (['website'] as const) : []),
    ];
    const [requested, setRequested] = useState<SystemSection>(() =>
        initialSection(available, props),
    );
    const active = available.includes(requested)
        ? requested
        : (available[0] ?? 'activity');
    const select = (section: SystemSection) => {
        setRequested(section);
        const url = new URL(window.location.href);
        url.searchParams.set('control', section);
        window.history.replaceState({}, '', url);
    };
    const counts: Record<SystemSection, number> = {
        readiness:
            (props.readinessStatus?.automatic_blocked ?? 0) +
            (props.readinessStatus?.evidence_remaining ?? 0),
        activity: props.platformActivity.length,
        company: props.platformComposition?.portfolios.live_active ?? 0,
        website:
            (props.cmsStatus?.published ?? 0) + (props.cmsStatus?.draft ?? 0),
    };
    const labels: Record<SystemSection, string> = {
        readiness: t('dashboard.launch_control_title'),
        activity: t('dashboard.platform_activity_title'),
        company: t('dashboard.company_composition_title'),
        website: t('cms.website_control'),
    };

    return (
        <div className="pmc-dashboard-system-workspace">
            <nav
                className="pmc-dashboard-system-tabs"
                aria-label={t('dashboard.system_workspace_navigation')}
            >
                {available.map((section) => (
                    <button
                        key={section}
                        id={`dashboard-system-tab-${section}`}
                        type="button"
                        className={active === section ? 'is-active' : ''}
                        data-dashboard-system-tab={section}
                        aria-pressed={active === section}
                        onClick={() => select(section)}
                    >
                        <span>{labels[section]}</span>
                        <strong>
                            {localizedNumber(counts[section], locale)}
                        </strong>
                    </button>
                ))}
            </nav>

            {active === 'readiness' && props.readinessStatus ? (
                <div
                    id="dashboard-system-panel-readiness"
                    className="pmc-dashboard-system-panel is-active"
                    data-dashboard-system-panel="readiness"
                    role="region"
                    aria-labelledby="dashboard-system-tab-readiness"
                >
                    <LaunchReadinessPanel status={props.readinessStatus} />
                </div>
            ) : null}
            {active === 'activity' ? (
                <div
                    id="dashboard-system-panel-activity"
                    className="pmc-dashboard-system-panel is-active"
                    data-dashboard-system-panel="activity"
                    role="region"
                    aria-labelledby="dashboard-system-tab-activity"
                >
                    <PlatformActivityPanel
                        activities={props.platformActivity}
                    />
                </div>
            ) : null}
            {active === 'company' && props.platformComposition ? (
                <div
                    id="dashboard-system-panel-company"
                    className="pmc-dashboard-system-panel is-active"
                    data-dashboard-system-panel="company"
                    role="region"
                    aria-labelledby="dashboard-system-tab-company"
                >
                    <PlatformCompositionPanel
                        composition={props.platformComposition}
                    />
                </div>
            ) : null}
            {active === 'website' && props.cmsStatus ? (
                <div
                    id="dashboard-system-panel-website"
                    className="pmc-dashboard-system-panel is-active"
                    data-dashboard-system-panel="website"
                    role="region"
                    aria-labelledby="dashboard-system-tab-website"
                >
                    <PlatformStatusPanel status={props.cmsStatus} />
                </div>
            ) : null}
        </div>
    );
}

function initialSection(
    available: SystemSection[],
    props: OperationsDashboardProps,
): SystemSection {
    if (typeof window !== 'undefined') {
        const requested = new URLSearchParams(window.location.search).get(
            'control',
        );

        if (available.includes(requested as SystemSection)) {
            return requested as SystemSection;
        }
    }

    if (
        props.readinessStatus &&
        (props.readinessStatus.automatic_blocked > 0 ||
            props.readinessStatus.evidence_remaining > 0)
    ) {
        return 'readiness';
    }

    return props.platformActivity.length > 0 ? 'activity' : 'company';
}
