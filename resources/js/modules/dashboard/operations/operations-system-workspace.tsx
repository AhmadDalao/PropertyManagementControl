import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { OperationsDashboardProps } from '../types';
import { LaunchReadinessPanel } from './launch-readiness-panel';
import { PlatformActivityPanel } from './platform-activity-panel';
import { PlatformCompositionPanel } from './platform-composition-panel';
import { PlatformStatusPanel } from './platform-status-panel';
import { SystemWorkspaceTabs } from './system-workspace-tabs';
import type { SystemWorkspaceSection } from './system-workspace-tabs';

export function OperationsSystemWorkspace({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t } = useTranslator();
    const available: SystemWorkspaceSection[] = [
        ...(props.readinessStatus ? (['readiness'] as const) : []),
        'activity',
        ...(props.platformComposition ? (['company'] as const) : []),
        ...(props.cmsStatus ? (['website'] as const) : []),
    ];
    const [requested, setRequested] = useState<SystemWorkspaceSection>(() =>
        initialSection(available, props),
    );
    const active = available.includes(requested)
        ? requested
        : (available[0] ?? 'activity');
    const select = (section: SystemWorkspaceSection) => {
        setRequested(section);
        const url = new URL(window.location.href);
        url.searchParams.set('control', section);
        window.history.replaceState({}, '', url);
    };
    const counts: Record<SystemWorkspaceSection, number> = {
        readiness:
            (props.readinessStatus?.automatic_blocked ?? 0) +
            (props.readinessStatus?.evidence_remaining ?? 0),
        activity: props.platformActivity.length,
        company: props.platformComposition?.portfolios.live_active ?? 0,
        website:
            (props.cmsStatus?.published ?? 0) + (props.cmsStatus?.draft ?? 0),
    };
    const labels: Record<SystemWorkspaceSection, string> = {
        readiness: t('dashboard.launch_control_title'),
        activity: t('dashboard.platform_activity_title'),
        company: t('dashboard.company_composition_title'),
        website: t('cms.website_control'),
    };

    return (
        <div className="pmc-dashboard-system-workspace">
            <SystemWorkspaceTabs
                active={active}
                label={t('dashboard.system_workspace_navigation')}
                locale={locale}
                options={available.map((section) => ({
                    key: section,
                    label: labels[section],
                    count: counts[section],
                }))}
                onSelect={select}
            />

            {active === 'readiness' && props.readinessStatus ? (
                <div
                    id="dashboard-system-panel-readiness"
                    className="pmc-dashboard-system-panel is-active"
                    data-dashboard-system-panel="readiness"
                    role="tabpanel"
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
                    role="tabpanel"
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
                    role="tabpanel"
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
                    role="tabpanel"
                    aria-labelledby="dashboard-system-tab-website"
                >
                    <PlatformStatusPanel status={props.cmsStatus} />
                </div>
            ) : null}
        </div>
    );
}

function initialSection(
    available: SystemWorkspaceSection[],
    props: OperationsDashboardProps,
): SystemWorkspaceSection {
    if (typeof window !== 'undefined') {
        const requested = new URLSearchParams(window.location.search).get(
            'control',
        );

        if (available.includes(requested as SystemWorkspaceSection)) {
            return requested as SystemWorkspaceSection;
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
