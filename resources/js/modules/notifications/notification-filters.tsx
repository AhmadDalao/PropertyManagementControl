import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { NotificationIndexPageProps } from './types';

type NotificationFiltersProps = Pick<
    NotificationIndexPageProps,
    'filters' | 'counts' | 'typeCounts'
>;

export function NotificationFilters({
    filters,
    counts,
    typeCounts,
}: NotificationFiltersProps) {
    const { t } = useTranslator();
    const [search, setSearch] = useState(filters.search);
    const statuses = [
        ['all', t('notifications.all'), counts.all],
        ['unread', t('notifications.unread'), counts.unread],
        ['read', t('notifications.read'), counts.read],
    ] as const;
    const types = [
        ['all', t('notifications.types.all'), typeCounts.all],
        [
            'maintenance_request',
            t('notifications.types.maintenance_request'),
            typeCounts.maintenance_request,
        ],
        ['payment', t('notifications.types.payment'), typeCounts.payment],
        ['lease', t('notifications.types.lease'), typeCounts.lease],
        ['document', t('notifications.types.document'), typeCounts.document],
    ] as const;

    function href(overrides: Partial<typeof filters>) {
        const next = { ...filters, ...overrides };
        const query = new URLSearchParams();

        if (next.status !== 'all') {
            query.set('status', next.status);
        }

        if (next.type !== 'all') {
            query.set('type', next.type);
        }

        if (next.search) {
            query.set('search', next.search);
        }

        return `/notifications${query.size ? `?${query}` : ''}`;
    }

    function submitSearch(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            '/notifications',
            { ...filters, search, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <div className="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
                <div
                    className="pmc-filter-chips"
                    aria-label={t('notifications.filter')}
                >
                    {statuses.map(([status, label, count]) => (
                        <Link
                            key={status}
                            href={href({ status })}
                            preserveScroll
                            preserveState
                            className={`pmc-filter-chip ${filters.status === status ? 'active' : ''}`}
                        >
                            {label} <strong>{count}</strong>
                        </Link>
                    ))}
                </div>

                {counts.unread > 0 ? (
                    <button
                        type="button"
                        className="btn btn-outline-dark"
                        onClick={() => router.post('/notifications/read-all')}
                    >
                        <i className="bi bi-check2 me-2" aria-hidden="true" />
                        {t('notifications.mark_all_read')}
                    </button>
                ) : null}
            </div>

            <div className="pmc-notification-filters rounded-4 bg-light-subtle p-3 mb-4 d-grid gap-3 border">
                <form
                    className="d-flex gap-2 flex-column flex-md-row"
                    onSubmit={submitSearch}
                >
                    <label
                        className="visually-hidden"
                        htmlFor="notification-search"
                    >
                        {t('notifications.search')}
                    </label>
                    <input
                        id="notification-search"
                        type="search"
                        className="form-control form-control-lg"
                        value={search}
                        placeholder={t('notifications.search_placeholder')}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <button type="submit" className="btn btn-dark btn-lg">
                        <i className="bi bi-search me-2" aria-hidden="true" />
                        {t('actions.search')}
                    </button>
                    {filters.search ? (
                        <Link
                            href={href({ search: '' })}
                            className="btn btn-outline-dark btn-lg"
                        >
                            {t('actions.clear')}
                        </Link>
                    ) : null}
                </form>

                <div
                    className="pmc-filter-chips"
                    aria-label={t('notifications.type_filter')}
                >
                    {types.map(([type, label, count]) => (
                        <Link
                            key={type}
                            href={href({ type })}
                            preserveScroll
                            preserveState
                            className={`pmc-filter-chip ${filters.type === type ? 'active' : ''}`}
                        >
                            {label} <strong>{count}</strong>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
