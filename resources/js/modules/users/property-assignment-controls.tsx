import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

export function PropertyAssignmentOverview({
    available,
    selected,
    children,
}: {
    available: number;
    selected: number;
    children: number;
}) {
    const { t } = useTranslator();

    return (
        <>
            <MetricGrid
                metrics={[
                    {
                        label: t('users.available_properties'),
                        value: available,
                        detail: t('users.available_properties_help'),
                        icon: 'bi-buildings',
                        tone: 'ink',
                    },
                    {
                        label: t('users.selected_properties'),
                        value: selected,
                        detail: t('users.selected_properties_help'),
                        icon: 'bi-building-check',
                        tone: 'teal',
                    },
                    {
                        label: t('users.child_assets_covered'),
                        value: children,
                        detail: t('users.child_assets_covered_help'),
                        icon: 'bi-diagram-3',
                        tone: 'amber',
                    },
                ]}
            />
            <section className="pmc-property-assignment-guide">
                <i className="bi bi-shield-check" />
                <span>
                    <strong>{t('users.assignment_rule')}</strong>
                    <small>{t('users.assignment_rule_help')}</small>
                </span>
            </section>
        </>
    );
}

export function PropertyAssignmentToolbar({
    search,
    onSearch,
    selected,
    total,
}: {
    search: string;
    onSearch: (value: string) => void;
    selected: number;
    total: number;
}) {
    const { t } = useTranslator();

    return (
        <div className="pmc-property-assignment-toolbar">
            <label>
                <span>{t('users.search_properties')}</span>
                <div className="pmc-property-assignment-search">
                    <i className="bi bi-search" />
                    <input
                        type="search"
                        value={search}
                        onChange={(event) =>
                            onSearch(event.currentTarget.value)
                        }
                        placeholder={t('users.search_properties_placeholder')}
                    />
                </div>
            </label>
            <p>
                {t('users.assignment_selection_summary', undefined, {
                    selected,
                    total,
                })}
            </p>
        </div>
    );
}
