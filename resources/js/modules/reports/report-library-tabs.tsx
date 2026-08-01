import { useTranslator } from '@/lib/i18n';

import type { ReportLibraryGroup } from './types';

export function ReportLibraryTabs({
    active,
    groups,
    onSelect,
}: {
    active: string;
    groups: ReportLibraryGroup[];
    onSelect: (group: string) => void;
}) {
    const { t } = useTranslator();

    return (
        <nav
            className="pmc-report-library-tabs"
            aria-label={t('reports.library_title')}
            role="tablist"
        >
            {groups.map((group) => (
                <button
                    key={group.key}
                    id={`report-library-tab-${group.key}`}
                    type="button"
                    role="tab"
                    aria-controls="report-library-panel"
                    aria-selected={active === group.key}
                    className={active === group.key ? 'is-active' : ''}
                    onClick={() => onSelect(group.key)}
                >
                    <span>{group.title}</span>
                    <strong>{group.cards.length}</strong>
                </button>
            ))}
        </nav>
    );
}

export function resolveReportLibraryGroup(
    requested: string | null,
    groups: ReportLibraryGroup[],
): string {
    return (
        groups.find((group) => group.key === requested)?.key ??
        groups[0]?.key ??
        ''
    );
}
