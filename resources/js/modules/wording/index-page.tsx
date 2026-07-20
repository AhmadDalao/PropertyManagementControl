import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useDeferredValue, useState } from 'react';
import type { FormEvent } from 'react';

import {
    MetricGrid,
    WorkspaceHeader,
    humanLabel,
} from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import type { SharedProps } from '@/types';

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

type WordingPageProps = SharedProps & {
    entries: WordingEntry[];
    groups: string[];
    customizedCount: number;
};

export default function WordingIndexPage() {
    const { props } = usePage<WordingPageProps>();
    const { locale, t } = useTranslator();
    const defaultGroup = props.groups.includes('nav')
        ? 'nav'
        : (props.groups[0] ?? 'all');
    const [group, setGroup] = useState(defaultGroup);
    const [search, setSearch] = useState('');
    const deferredSearch = useDeferredValue(
        search.trim().toLocaleLowerCase(locale),
    );
    const visibleEntries = props.entries.filter((entry) => {
        const matchesGroup = group === 'all' || entry.group === group;
        const matchesSearch =
            deferredSearch === '' ||
            [
                entry.group,
                entry.key,
                entry.english,
                entry.arabic,
                entry.default_english,
                entry.default_arabic,
            ].some((value) =>
                value.toLocaleLowerCase(locale).includes(deferredSearch),
            );

        return matchesGroup && matchesSearch;
    });

    const groupLabel = (value: string) =>
        value === 'all'
            ? t('wording.all_areas', 'All page areas')
            : t(
                  `wording.group_${value}` as UiTranslationKey,
                  humanLabel(value),
              );

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
                        value: props.entries.length,
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
                        label: t('wording.languages'),
                        value: '2',
                        detail: t('wording.languages_detail'),
                        icon: 'bi-translate',
                        tone: 'blue',
                    },
                ]}
            />

            <section className="pmc-wording-help-grid">
                <article>
                    <span className="pmc-wording-help-icon">
                        <i className="bi bi-window" />
                    </span>
                    <div>
                        <strong>{t('wording.website_title')}</strong>
                        <p>{t('wording.website_description')}</p>
                    </div>
                    <Link href="/cms" className="btn btn-outline-secondary">
                        {t('wording.open_website_builder')}
                        <i className="bi bi-arrow-up-right" />
                    </Link>
                </article>
                <article className="is-active">
                    <span className="pmc-wording-help-icon">
                        <i className="bi bi-speedometer2" />
                    </span>
                    <div>
                        <strong>{t('wording.portal_title')}</strong>
                        <p>{t('wording.portal_description')}</p>
                    </div>
                    <span className="pmc-wording-current">
                        <i className="bi bi-check-circle-fill" />
                        {t('wording.current_workspace', 'Current workspace')}
                    </span>
                </article>
            </section>

            <section className="pmc-wording-workspace">
                <header>
                    <div>
                        <span>{t('wording.portal_title')}</span>
                        <strong>
                            {t('wording.showing', undefined, {
                                count: visibleEntries.length,
                            })}
                        </strong>
                    </div>
                </header>

                <div className="pmc-wording-filters">
                    <label>
                        <span>{t('wording.search')}</span>
                        <div className="pmc-wording-search">
                            <i className="bi bi-search" />
                            <input
                                type="search"
                                value={search}
                                placeholder={t('wording.search_placeholder')}
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
                            value={group}
                            onChange={(event) =>
                                setGroup(event.currentTarget.value)
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
                </div>

                <div className="pmc-wording-list" aria-live="polite">
                    {visibleEntries.map((entry) => (
                        <WordingCard
                            key={`${entry.group}:${entry.key}:${entry.english}:${entry.arabic}`}
                            entry={entry}
                            groupLabel={groupLabel(entry.group)}
                        />
                    ))}
                </div>

                {visibleEntries.length === 0 ? (
                    <div className="pmc-wording-empty">
                        <i className="bi bi-search" />
                        <strong>{t('wording.no_results')}</strong>
                        <p>{t('wording.no_results_description')}</p>
                        <button
                            type="button"
                            className="btn btn-outline-secondary"
                            onClick={() => {
                                setSearch('');
                                setGroup('all');
                            }}
                        >
                            {t('actions.reset')}
                        </button>
                    </div>
                ) : null}
            </section>
        </AdminLayout>
    );
}

function WordingCard({
    entry,
    groupLabel,
}: {
    entry: WordingEntry;
    groupLabel: string;
}) {
    const { t } = useTranslator();
    const form = useForm({
        group: entry.group,
        key: entry.key,
        english: entry.english,
        arabic: entry.arabic,
    });
    const changed =
        form.data.english !== entry.english ||
        form.data.arabic !== entry.arabic;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/wording', {
            preserveScroll: true,
        });
    };

    const reset = () => {
        if (!window.confirm(t('wording.reset_confirm'))) {
            return;
        }

        router.delete('/wording', {
            data: {
                group: entry.group,
                key: entry.key,
            },
            preserveScroll: true,
        });
    };

    return (
        <form className="pmc-wording-card" onSubmit={submit}>
            <header>
                <div>
                    <span>{groupLabel}</span>
                    <strong>{humanLabel(entry.key)}</strong>
                    <code>
                        {entry.group}.{entry.key}
                    </code>
                </div>
                <span
                    className={`pmc-wording-state ${entry.customized ? 'is-custom' : ''}`}
                >
                    {entry.customized
                        ? t('wording.custom_badge')
                        : t('wording.system_default')}
                </span>
            </header>

            <div className="pmc-wording-language-grid">
                <label>
                    <span>
                        <i className="bi bi-translate" />
                        {t('wording.english')}
                    </span>
                    <textarea
                        dir="ltr"
                        rows={2}
                        value={form.data.english}
                        onChange={(event) =>
                            form.setData('english', event.currentTarget.value)
                        }
                    />
                    {form.errors.english ? (
                        <small className="text-danger">
                            {form.errors.english}
                        </small>
                    ) : null}
                    {entry.customized ? (
                        <small>
                            {t('wording.original')}: {entry.default_english}
                        </small>
                    ) : null}
                </label>
                <label>
                    <span>
                        <i className="bi bi-translate" />
                        {t('wording.arabic')}
                    </span>
                    <textarea
                        dir="rtl"
                        rows={2}
                        value={form.data.arabic}
                        onChange={(event) =>
                            form.setData('arabic', event.currentTarget.value)
                        }
                    />
                    {form.errors.arabic ? (
                        <small className="text-danger">
                            {form.errors.arabic}
                        </small>
                    ) : null}
                    {entry.customized ? (
                        <small dir="rtl">
                            {t('wording.original')}: {entry.default_arabic}
                        </small>
                    ) : null}
                </label>
            </div>

            <footer>
                {entry.customized ? (
                    <button
                        type="button"
                        className="btn btn-link pmc-wording-reset"
                        onClick={reset}
                    >
                        <i className="bi bi-arrow-counterclockwise" />
                        {t('wording.reset')}
                    </button>
                ) : (
                    <span />
                )}
                <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={!changed || form.processing}
                >
                    {form.processing ? (
                        <>
                            <span
                                className="spinner-border spinner-border-sm"
                                aria-hidden="true"
                            />
                            {t('wording.saving')}
                        </>
                    ) : (
                        <>
                            <i className="bi bi-check-lg" />
                            {t('wording.save')}
                        </>
                    )}
                </button>
            </footer>
        </form>
    );
}
