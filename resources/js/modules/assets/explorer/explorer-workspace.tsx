import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import { ExplorerFocusPanel } from './explorer-focus-panel';
import { ExplorerRecords } from './explorer-records';
import { ExplorerSummary } from './explorer-summary';
import { ExplorerViewTabs } from './explorer-view-tabs';
import type { PropertyExplorerPayload, PropertyExplorerView } from './types';

export function ExplorerWorkspace({
    explorer,
    canCreate,
}: {
    explorer: PropertyExplorerPayload;
    canCreate: boolean;
}) {
    const { t } = useTranslator();
    const structureAvailable =
        explorer.selected !== null &&
        (explorer.selected.children_count > 0 || hasDirectoryFilters(explorer));
    const available: PropertyExplorerView[] = [
        ...(structureAvailable ? (['structure'] as const) : []),
        'overview',
        'tenancy',
    ];
    const fallback = defaultView(explorer, structureAvailable);
    const [requested, setRequested] = useState<PropertyExplorerView>(() =>
        initialView(available, fallback),
    );
    const active = available.includes(requested) ? requested : fallback;
    const select = (view: PropertyExplorerView) => {
        setRequested(view);
        const url = new URL(window.location.href);
        url.searchParams.set('view', view);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ExplorerViewTabs
                active={active}
                options={[
                    ...(structureAvailable
                        ? [
                              {
                                  key: 'structure' as const,
                                  label: t('assets.explorer.view_structure'),
                                  count: explorer.records?.total ?? 0,
                              },
                          ]
                        : []),
                    {
                        key: 'overview',
                        label: t('assets.explorer.view_overview'),
                    },
                    {
                        key: 'tenancy',
                        label: t('assets.explorer.view_tenancy'),
                        count: explorer.active_lease ? 1 : 0,
                    },
                ]}
                onSelect={select}
            />

            <div
                className={viewPanelClass('overview', active)}
                data-explorer-view-panel="overview"
            >
                <ExplorerSummary explorer={explorer} />
            </div>
            <div
                className={`pmc-explorer-detail-panel${active !== 'structure' ? 'is-active' : ''}`}
                data-explorer-view-panel="record"
            >
                <ExplorerFocusPanel
                    explorer={explorer}
                    canCreate={canCreate}
                    activeView={active}
                />
            </div>
            {structureAvailable ? (
                <div
                    className={viewPanelClass('structure', active)}
                    data-explorer-view-panel="structure"
                >
                    <ExplorerRecords
                        explorer={explorer}
                        canCreate={canCreate}
                    />
                </div>
            ) : null}
        </>
    );
}

function initialView(
    available: PropertyExplorerView[],
    fallback: PropertyExplorerView,
): PropertyExplorerView {
    const requested =
        typeof window === 'undefined'
            ? null
            : new URLSearchParams(window.location.search).get('view');

    return available.includes(requested as PropertyExplorerView)
        ? (requested as PropertyExplorerView)
        : fallback;
}

function defaultView(
    explorer: PropertyExplorerPayload,
    structureAvailable: boolean,
): PropertyExplorerView {
    if (structureAvailable) {
        return 'structure';
    }

    return explorer.active_lease ? 'tenancy' : 'overview';
}

function hasDirectoryFilters(explorer: PropertyExplorerPayload): boolean {
    return (
        explorer.filters.search !== '' ||
        explorer.filters.asset_type !== 'all' ||
        explorer.filters.occupancy_status !== 'all'
    );
}

function viewPanelClass(
    view: PropertyExplorerView,
    active: PropertyExplorerView,
): string {
    return `pmc-explorer-view-panel${view === active ? ' is-active' : ''}`;
}
