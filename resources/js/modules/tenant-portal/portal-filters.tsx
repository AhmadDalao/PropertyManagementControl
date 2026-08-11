import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { TenantLeaseOption, TenantPortalFilters } from './types';

export function PortalFilters({
    basePath,
    filters,
    leases,
    mode,
    types = [],
}: {
    basePath: string;
    filters: TenantPortalFilters;
    leases: TenantLeaseOption[];
    mode: 'payments' | 'documents';
    types?: string[];
}) {
    const { locale, t } = useTranslator();
    const [values, setValues] = useState(filters);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(basePath, values, { preserveState: true, replace: true });
    };

    return (
        <form className="pmc-portal-filters" onSubmit={submit}>
            <label className="is-search">
                <span>{t('actions.search')}</span>
                <input
                    type="search"
                    value={values.search}
                    placeholder={t(`tenant_portal.${mode}_search_placeholder`)}
                    onChange={(event) =>
                        setValues({
                            ...values,
                            search: event.currentTarget.value,
                        })
                    }
                />
            </label>
            {mode === 'payments' ? (
                <label>
                    <span>{t('tenant_portal.status')}</span>
                    <select
                        value={values.status}
                        onChange={(event) =>
                            setValues({
                                ...values,
                                status: event.currentTarget.value,
                            })
                        }
                    >
                        {['all', 'posted', 'pending', 'void'].map((status) => (
                            <option value={status} key={status}>
                                {status === 'all'
                                    ? t('common.all')
                                    : t(`status.${status}`)}
                            </option>
                        ))}
                    </select>
                </label>
            ) : (
                <label>
                    <span>{t('tenant_portal.document_type')}</span>
                    <select
                        value={values.type}
                        onChange={(event) =>
                            setValues({
                                ...values,
                                type: event.currentTarget.value,
                            })
                        }
                    >
                        <option value="all">{t('common.all')}</option>
                        {types.map((type) => (
                            <option value={type} key={type}>
                                {t(`documents.options.${type}`)}
                            </option>
                        ))}
                    </select>
                </label>
            )}
            <label>
                <span>{t('tenant_portal.lease')}</span>
                <select
                    value={values.lease_id ?? ''}
                    onChange={(event) =>
                        setValues({
                            ...values,
                            lease_id: event.currentTarget.value
                                ? Number(event.currentTarget.value)
                                : null,
                        })
                    }
                >
                    <option value="">{t('tenant_portal.all_leases')}</option>
                    {leases.map((lease) => (
                        <option value={lease.id} key={lease.id}>
                            {lease.code} ·{' '}
                            {locale === 'ar'
                                ? lease.asset_title_ar || lease.asset_title_en
                                : lease.asset_title_en || lease.asset_title_ar}
                        </option>
                    ))}
                </select>
            </label>
            <label>
                <span>{t('reports.date_from')}</span>
                <input
                    type="date"
                    value={values.date_from}
                    max={values.date_to || undefined}
                    onChange={(event) =>
                        setValues({
                            ...values,
                            date_from: event.currentTarget.value,
                        })
                    }
                />
            </label>
            <label>
                <span>{t('reports.date_to')}</span>
                <input
                    type="date"
                    value={values.date_to}
                    min={values.date_from || undefined}
                    onChange={(event) =>
                        setValues({
                            ...values,
                            date_to: event.currentTarget.value,
                        })
                    }
                />
            </label>
            <div className="pmc-portal-filter-actions">
                <button type="submit">
                    <i className="bi bi-funnel" />
                    {t('actions.filter')}
                </button>
                <button
                    type="button"
                    className="is-quiet"
                    onClick={() => router.get(basePath)}
                >
                    <i className="bi bi-arrow-counterclockwise" />
                    {t('actions.reset')}
                </button>
            </div>
        </form>
    );
}
