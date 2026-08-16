import { Link, router } from '@inertiajs/react';
import type { Dispatch, FormEvent, SetStateAction } from 'react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { ActionCenterFilterSelect } from './action-center-filter-select';
import { actionCenterUrl } from './action-center-query';
import type { ActionCenterFilters, ActionCenterPageProps } from './types';

type ControlsProps = Pick<
    ActionCenterPageProps,
    'assigneeOptions' | 'auth' | 'portfolioOptions' | 'propertyOptions'
> & {
    draft: ActionCenterFilters;
    filtersOpen: boolean;
    setDraft: Dispatch<SetStateAction<ActionCenterFilters>>;
};

export function ActionCenterFilterControls({
    assigneeOptions,
    auth,
    draft,
    filtersOpen,
    portfolioOptions,
    propertyOptions,
    setDraft,
}: ControlsProps) {
    const { locale, t } = useTranslator();
    const isSuperadmin = auth.user?.roles.includes('superadmin') ?? false;
    const properties = propertyOptions.filter(
        (property) =>
            !draft.portfolio_id || property.portfolio_id === draft.portfolio_id,
    );
    const update = <Key extends keyof ActionCenterFilters>(
        key: Key,
        value: ActionCenterFilters[Key],
    ) => {
        setDraft((current) => ({
            ...current,
            [key]: value,
            page: 1,
        }));
    };
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.visit(actionCenterUrl(draft, { page: 1 }), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <form
            id="action-center-filter-form"
            className={`pmc-action-filter-form ${filtersOpen ? 'is-open' : ''}`}
            onSubmit={submit}
        >
            <label className="pmc-action-search-field">
                <span>{t('action_center.search')}</span>
                <div>
                    <i className="bi bi-search" aria-hidden="true" />
                    <input
                        type="search"
                        className="form-control"
                        value={draft.search}
                        placeholder={t('action_center.search_placeholder')}
                        onChange={(event) =>
                            update('search', event.target.value)
                        }
                    />
                </div>
            </label>

            <ActionCenterFilterSelect
                label={t('action_center.priority')}
                value={draft.priority}
                onChange={(value) =>
                    update('priority', value as ActionCenterFilters['priority'])
                }
                options={['all', 'critical', 'high', 'normal'].map(
                    (priority) => ({
                        value: priority,
                        label: t(`action_center.priority_${priority}`),
                    }),
                )}
            />

            {isSuperadmin ? (
                <ActionCenterFilterSelect
                    label={t('action_center.portfolio')}
                    value={draft.portfolio_id ?? 'all'}
                    onChange={(value) =>
                        setDraft((current) => ({
                            ...current,
                            portfolio_id:
                                value === 'all' ? null : Number(value),
                            property_id: null,
                            assignee: 'all',
                            page: 1,
                        }))
                    }
                    options={[
                        {
                            value: 'all',
                            label: t('action_center.all_portfolios'),
                        },
                        ...portfolioOptions.map((portfolio) => ({
                            value: portfolio.id,
                            label: portfolio.name,
                        })),
                    ]}
                />
            ) : null}

            <ActionCenterFilterSelect
                label={t('action_center.property')}
                value={draft.property_id ?? 'all'}
                onChange={(value) =>
                    update(
                        'property_id',
                        value === 'all' ? null : Number(value),
                    )
                }
                options={[
                    {
                        value: 'all',
                        label: t('action_center.all_properties'),
                    },
                    ...properties.map((property) => ({
                        value: property.id,
                        label: property.name,
                    })),
                ]}
            />

            <ActionCenterFilterSelect
                label={t('action_center.assignee')}
                value={draft.assignee}
                onChange={(value) => update('assignee', value)}
                options={[
                    {
                        value: 'all',
                        label: t('action_center.assignee_all'),
                    },
                    {
                        value: 'me',
                        label: t('action_center.assignee_me'),
                    },
                    {
                        value: 'unassigned',
                        label: t('action_center.assignee_unassigned'),
                    },
                    ...assigneeOptions.map((assignee) => ({
                        value: assignee.id,
                        label: assignee.label,
                    })),
                ]}
            />

            <ActionCenterFilterSelect
                label={t('action_center.page_size')}
                value={draft.per_page}
                onChange={(value) => update('per_page', Number(value))}
                options={[6, 12, 24].map((size) => ({
                    value: size,
                    label: localizedNumber(size, locale),
                }))}
            />

            <div className="pmc-action-filter-actions">
                <button type="submit" className="btn pmc-action-apply">
                    <i className="bi bi-funnel" aria-hidden="true" />
                    {t('actions.filter')}
                </button>
                <Link href="/action-center" className="btn pmc-action-reset">
                    <i
                        className="bi bi-arrow-counterclockwise"
                        aria-hidden="true"
                    />
                    {t('actions.reset')}
                </Link>
            </div>
        </form>
    );
}
