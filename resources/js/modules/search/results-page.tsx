import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import '../../../css/styles/search-results.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

import { groupSearchResults } from './group-results';
import type { GlobalSearchResponse } from './types';

type SearchResultsPageProps = SharedProps & { search: GlobalSearchResponse };

export default function SearchResultsPage() {
    const { props } = usePage<SearchResultsPageProps>();
    const { t } = useTranslator();
    const [query, setQuery] = useState(props.search.query);
    const groups = groupSearchResults(props.search.results);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get('/search', { q: query.trim() }, { preserveState: false });
    };

    return (
        <AdminLayout>
            <Head title={t('search.page_title')} />
            <WorkspaceHeader
                eyebrow={t('search.page_eyebrow')}
                title={t('search.page_title')}
                description={t('search.page_description')}
            />
            <form
                className="pmc-search-page-form"
                onSubmit={submit}
                role="search"
            >
                <label htmlFor="global-search-page">
                    <i className="bi bi-search" />
                    <span className="visually-hidden">
                        {t('actions.search')}
                    </span>
                </label>
                <input
                    id="global-search-page"
                    type="search"
                    value={query}
                    autoFocus
                    placeholder={t('shell.search_placeholder')}
                    onChange={(event) => setQuery(event.currentTarget.value)}
                />
                <button type="submit">{t('actions.search')}</button>
            </form>

            <section className="pmc-search-page-summary" aria-live="polite">
                <strong>
                    {t('search.results_found', undefined, {
                        count: props.search.results.length,
                    })}
                </strong>
                <span>
                    {props.search.query
                        ? t('search.results_for', undefined, {
                              query: props.search.query,
                          })
                        : t('search.enter_query')}
                </span>
            </section>

            {props.search.message ? (
                <p className="pmc-search-page-message">
                    {props.search.message}
                </p>
            ) : null}
            {Object.keys(groups).length > 0 ? (
                <div className="pmc-search-page-layout">
                    <main className="pmc-search-page-groups">
                        {Object.entries(groups).map(([group, results]) => (
                            <section key={group}>
                                <header>
                                    <div>
                                        <i className="bi bi-folder2-open" />
                                        <h2>{group}</h2>
                                    </div>
                                    <span>{results.length}</span>
                                </header>
                                {results.map((result) => (
                                    <Link
                                        href={result.url}
                                        key={`${result.group}-${result.url}`}
                                    >
                                        <div>
                                            <strong>{result.title}</strong>
                                            <small>{result.subtitle}</small>
                                        </div>
                                        {result.badge ? (
                                            <span>{result.badge}</span>
                                        ) : null}
                                        <i className="bi bi-arrow-up-right" />
                                    </Link>
                                ))}
                            </section>
                        ))}
                    </main>
                    <aside className="pmc-search-page-scope">
                        <span>{t('search.scope_title')}</span>
                        <h2>{t('search.scope_heading')}</h2>
                        <p>{t('search.scope_description')}</p>
                        <dl>
                            <div>
                                <dt>{t('search.role')}</dt>
                                <dd>
                                    {props.auth.user?.roles
                                        .map((role) => t(`roles.${role}`))
                                        .join(', ')}
                                </dd>
                            </div>
                            <div>
                                <dt>{t('search.language')}</dt>
                                <dd>{t(`locales.${props.app.locale}`)}</dd>
                            </div>
                            <div>
                                <dt>{t('search.result_limit')}</dt>
                                <dd>30</dd>
                            </div>
                        </dl>
                    </aside>
                </div>
            ) : null}
        </AdminLayout>
    );
}
