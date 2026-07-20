import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import {
    MetricGrid,
    WorkspaceHeader,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import type { PaginatedData, SharedProps } from '@/types';

import '../../../css/styles/wording.css';

type WordingEntry = {
    group: string;
    key: string;
    english: string;
    arabic: string;
    default_english: string;
    default_arabic: string;
    customized: boolean;
};

type MissingContent = {
    module: string;
    title: string;
    subtitle: string;
    missing: string;
    href: string;
};

type WordingPageProps = SharedProps & {
    entries: PaginatedData<WordingEntry>;
    groups: string[];
    customizedCount: number;
    totalLabels: number;
    filters: {
        search: string;
        group: string;
        state: string;
        perPage: number;
        contentModule: string;
    };
    contentTranslations: {
        items: MissingContent[];
        total: number;
        counts: Record<string, number>;
        modules: string[];
    };
};

export default function WordingIndexPage() {
    const { props } = usePage<WordingPageProps>();
    const { t } = useTranslator();
    const [tab, setTab] = useState<'wording' | 'content'>('wording');
    const [selected, setSelected] = useState<WordingEntry | null>(null);
    const [search, setSearch] = useState(props.filters.search);
    const groupLabel = (value: string) =>
        value === 'all'
            ? t('wording.all_areas')
            : t(
                  `wording.group_${value}` as UiTranslationKey,
                  humanLabel(value),
              );

    const applyFilters = (overrides: Record<string, string | number> = {}) => {
        router.get(
            '/wording',
            {
                search,
                group: props.filters.group,
                state: props.filters.state,
                per_page: props.filters.perPage,
                content_module: props.filters.contentModule,
                ...overrides,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <AdminLayout>
            <Head title={t('wording.title')} />

            <WorkspaceHeader
                eyebrow={t('wording.eyebrow')}
                title={t('wording.title')}
                description={t('wording.description')}
                actions={[
                    {
                        label: t('wording.open_website_builder'),
                        href: '/cms',
                        icon: 'bi-layout-wtf',
                    },
                ]}
            />

            <MetricGrid
                metrics={[
                    {
                        label: t('wording.total_labels'),
                        value: props.totalLabels,
                        detail: t('wording.total_labels_detail'),
                        icon: 'bi-type',
                        tone: 'ink',
                    },
                    {
                        label: t('wording.customized'),
                        value: props.customizedCount,
                        detail: t('wording.customized_detail'),
                        icon: 'bi-pencil-square',
                        tone: props.customizedCount > 0 ? 'amber' : 'teal',
                    },
                    {
                        label: t('wording.content_queue_title'),
                        value: props.contentTranslations.total,
                        detail: t('wording.content_queue_description'),
                        icon: 'bi-translate',
                        tone:
                            props.contentTranslations.total > 0
                                ? 'amber'
                                : 'teal',
                    },
                ]}
            />

            <nav className="pmc-wording-tabs" aria-label={t('wording.title')}>
                <button
                    type="button"
                    className={tab === 'wording' ? 'is-active' : ''}
                    onClick={() => setTab('wording')}
                >
                    <i className="bi bi-type" />
                    {t('wording.system_tab')}
                    <span>{props.totalLabels}</span>
                </button>
                <button
                    type="button"
                    className={tab === 'content' ? 'is-active' : ''}
                    onClick={() => setTab('content')}
                >
                    <i className="bi bi-translate" />
                    {t('wording.content_tab')}
                    <span>{props.contentTranslations.total}</span>
                </button>
            </nav>

            {tab === 'wording' ? (
                <section className="pmc-wording-workspace">
                    <header>
                        <div>
                            <span>{t('wording.portal_title')}</span>
                            <strong>
                                {t('wording.showing', undefined, {
                                    count: props.entries.total,
                                })}
                            </strong>
                        </div>
                    </header>

                    <form
                        className="pmc-wording-filters"
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters({ page: 1 });
                        }}
                    >
                        <label className="pmc-wording-filter-search">
                            <span>{t('wording.search')}</span>
                            <div className="pmc-wording-search">
                                <i className="bi bi-search" />
                                <input
                                    type="search"
                                    value={search}
                                    placeholder={t(
                                        'wording.search_placeholder',
                                    )}
                                    onChange={(event) =>
                                        setSearch(event.currentTarget.value)
                                    }
                                />
                            </div>
                        </label>
                        <label>
                            <span>{t('wording.area')}</span>
                            <select
                                className="form-select"
                                value={props.filters.group}
                                onChange={(event) =>
                                    applyFilters({
                                        group: event.currentTarget.value,
                                        page: 1,
                                    })
                                }
                            >
                                <option value="all">{groupLabel('all')}</option>
                                {props.groups.map((item) => (
                                    <option key={item} value={item}>
                                        {groupLabel(item)}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            <span>{t('wording.customized')}</span>
                            <select
                                className="form-select"
                                value={props.filters.state}
                                onChange={(event) =>
                                    applyFilters({
                                        state: event.currentTarget.value,
                                        page: 1,
                                    })
                                }
                            >
                                <option value="all">
                                    {t('wording.all_states')}
                                </option>
                                <option value="customized">
                                    {t('wording.customized_only')}
                                </option>
                                <option value="default">
                                    {t('wording.defaults_only')}
                                </option>
                            </select>
                        </label>
                        <button type="submit" className="btn btn-primary">
                            <i className="bi bi-funnel" />
                            {t('actions.filter')}
                        </button>
                    </form>

                    <div className="pmc-wording-list" aria-live="polite">
                        {props.entries.data.map((entry) => (
                            <button
                                key={`${entry.group}:${entry.key}`}
                                type="button"
                                className="pmc-wording-row"
                                onClick={() => setSelected(entry)}
                            >
                                <span
                                    className={`pmc-wording-state ${entry.customized ? 'is-custom' : ''}`}
                                >
                                    {entry.customized
                                        ? t('wording.custom_badge')
                                        : t('wording.system_default')}
                                </span>
                                <span>
                                    <small>{groupLabel(entry.group)}</small>
                                    <strong>{entry.english}</strong>
                                    <em dir="rtl">{entry.arabic}</em>
                                </span>
                                <code>
                                    {entry.group}.{entry.key}
                                </code>
                                <i className="bi bi-chevron-right" />
                            </button>
                        ))}
                    </div>

                    {props.entries.data.length === 0 ? (
                        <EmptyWording />
                    ) : (
                        <Pagination
                            current={props.entries.current_page}
                            last={props.entries.last_page}
                            onPage={(page) => applyFilters({ page })}
                        />
                    )}
                </section>
            ) : (
                <ContentTranslationQueue
                    content={props.contentTranslations}
                    selectedModule={props.filters.contentModule}
                    onModule={(contentModule) =>
                        applyFilters({ content_module: contentModule })
                    }
                />
            )}

            {selected ? (
                <WordingEditor
                    entry={selected}
                    groupLabel={groupLabel(selected.group)}
                    onClose={() => setSelected(null)}
                />
            ) : null}
        </AdminLayout>
    );
}

function ContentTranslationQueue({
    content,
    selectedModule,
    onModule,
}: {
    content: WordingPageProps['contentTranslations'];
    selectedModule: string;
    onModule: (module: string) => void;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-wording-workspace">
            <header>
                <div>
                    <span>{t('wording.content_tab')}</span>
                    <strong>{t('wording.content_queue_title')}</strong>
                </div>
                <span className="pmc-wording-queue-count">{content.total}</span>
            </header>
            <div className="pmc-wording-module-chips">
                <button
                    type="button"
                    className={selectedModule === 'all' ? 'is-active' : ''}
                    onClick={() => onModule('all')}
                >
                    {t('common.overview')} {content.total}
                </button>
                {content.modules.map((module) => (
                    <button
                        key={module}
                        type="button"
                        className={selectedModule === module ? 'is-active' : ''}
                        onClick={() => onModule(module)}
                    >
                        {humanLabel(module)} {content.counts[module] ?? 0}
                    </button>
                ))}
            </div>
            {content.items.length > 0 ? (
                <div className="pmc-wording-content-grid">
                    {content.items.map((item, index) => (
                        <article key={`${item.module}:${item.href}:${index}`}>
                            <span>{humanLabel(item.module)}</span>
                            <strong>{item.title}</strong>
                            <p>{item.subtitle}</p>
                            <small>
                                {t('wording.missing_field', undefined, {
                                    field: humanLabel(item.missing),
                                })}
                            </small>
                            <Link
                                href={item.href}
                                className="btn btn-outline-secondary"
                            >
                                {t('wording.open_record')}
                                <i className="bi bi-arrow-up-right" />
                            </Link>
                        </article>
                    ))}
                </div>
            ) : (
                <div className="pmc-wording-empty">
                    <i className="bi bi-check2-circle" />
                    <strong>{t('wording.content_complete')}</strong>
                    <p>{t('wording.content_complete_description')}</p>
                </div>
            )}
        </section>
    );
}

function WordingEditor({
    entry,
    groupLabel,
    onClose,
}: {
    entry: WordingEntry;
    groupLabel: string;
    onClose: () => void;
}) {
    const { t } = useTranslator();
    const form = useForm({
        group: entry.group,
        key: entry.key,
        english: entry.english,
        arabic: entry.arabic,
    });
    const tokens = Array.from(
        new Set(
            `${entry.default_english} ${entry.default_arabic}`.match(
                /:[A-Za-z_]+/g,
            ) ?? [],
        ),
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/wording', {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const reset = () => {
        if (!window.confirm(t('wording.reset_confirm'))) {
            return;
        }

        router.delete('/wording', {
            data: { group: entry.group, key: entry.key },
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div className="pmc-wording-editor-layer" role="presentation">
            <button
                type="button"
                className="pmc-wording-editor-backdrop"
                aria-label={t('actions.close')}
                onClick={onClose}
            />
            <aside
                className="pmc-wording-editor"
                role="dialog"
                aria-modal="true"
                aria-labelledby="wording-editor-title"
            >
                <header>
                    <div>
                        <span>{groupLabel}</span>
                        <h2 id="wording-editor-title">
                            {t('wording.edit_wording')}
                        </h2>
                        <code>
                            {entry.group}.{entry.key}
                        </code>
                    </div>
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary"
                        onClick={onClose}
                        aria-label={t('actions.close')}
                    >
                        <i className="bi bi-x-lg" />
                    </button>
                </header>
                <form onSubmit={submit}>
                    <p>{t('wording.edit_description')}</p>
                    {tokens.length > 0 ? (
                        <div className="pmc-wording-tokens">
                            {t('wording.required_tokens', undefined, {
                                tokens: tokens.join(', '),
                            })}
                        </div>
                    ) : null}
                    <label>
                        <span>{t('wording.english')}</span>
                        <textarea
                            dir="ltr"
                            rows={5}
                            value={form.data.english}
                            onChange={(event) =>
                                form.setData(
                                    'english',
                                    event.currentTarget.value,
                                )
                            }
                        />
                        {form.errors.english ? (
                            <small className="text-danger">
                                {form.errors.english}
                            </small>
                        ) : null}
                    </label>
                    <label>
                        <span>{t('wording.arabic')}</span>
                        <textarea
                            dir="rtl"
                            rows={5}
                            value={form.data.arabic}
                            onChange={(event) =>
                                form.setData(
                                    'arabic',
                                    event.currentTarget.value,
                                )
                            }
                        />
                        {form.errors.arabic ? (
                            <small className="text-danger">
                                {form.errors.arabic}
                            </small>
                        ) : null}
                    </label>
                    <footer>
                        {entry.customized ? (
                            <button
                                type="button"
                                className="btn btn-outline-secondary"
                                onClick={reset}
                            >
                                {t('wording.reset')}
                            </button>
                        ) : (
                            <span />
                        )}
                        <button
                            type="submit"
                            className="btn btn-primary"
                            disabled={form.processing}
                        >
                            {form.processing
                                ? t('wording.saving')
                                : t('wording.save')}
                        </button>
                    </footer>
                </form>
            </aside>
        </div>
    );
}

function Pagination({
    current,
    last,
    onPage,
}: {
    current: number;
    last: number;
    onPage: (page: number) => void;
}) {
    const { t } = useTranslator();

    return (
        <nav className="pmc-wording-pagination" aria-label={t('wording.title')}>
            <button
                type="button"
                className="btn btn-outline-secondary"
                disabled={current <= 1}
                onClick={() => onPage(current - 1)}
            >
                {t('wording.previous_page')}
            </button>
            <span>
                {t('wording.page_of', undefined, {
                    page: current,
                    pages: last,
                })}
            </span>
            <button
                type="button"
                className="btn btn-outline-secondary"
                disabled={current >= last}
                onClick={() => onPage(current + 1)}
            >
                {t('wording.next_page')}
            </button>
        </nav>
    );
}

function EmptyWording() {
    const { t } = useTranslator();

    return (
        <div className="pmc-wording-empty">
            <i className="bi bi-search" />
            <strong>{t('wording.no_results')}</strong>
            <p>{t('wording.no_results_description')}</p>
        </div>
    );
}
