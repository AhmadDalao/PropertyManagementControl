import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { PropertyReportPageProps, PropertyReportTab } from './types';

export function PropertyReportPeriod({
    props,
    activeTab,
}: {
    props: PropertyReportPageProps;
    activeTab: PropertyReportTab;
}) {
    const { t } = useTranslator();
    const [period, setPeriod] = useState({
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            `/reports/properties/${props.property.id}`,
            { ...period, tab: activeTab },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <form className="pmc-property-report-period" onSubmit={submit}>
            <div>
                <i className="bi bi-calendar-range" aria-hidden="true" />
                <span>
                    <strong>{t('reports.statement_period')}</strong>
                    <small>{t('reports.property_period_help')}</small>
                </span>
            </div>
            <label>
                <span>{t('reports.date_from')}</span>
                <input
                    type="date"
                    className="form-control"
                    value={period.date_from}
                    max={period.date_to}
                    onChange={(event) =>
                        setPeriod((current) => ({
                            ...current,
                            date_from: event.target.value,
                        }))
                    }
                />
            </label>
            <label>
                <span>{t('reports.date_to')}</span>
                <input
                    type="date"
                    className="form-control"
                    value={period.date_to}
                    min={period.date_from}
                    onChange={(event) =>
                        setPeriod((current) => ({
                            ...current,
                            date_to: event.target.value,
                        }))
                    }
                />
            </label>
            <button type="submit" className="btn btn-primary">
                <i className="bi bi-funnel" aria-hidden="true" />
                {t('reports.update_period')}
            </button>
        </form>
    );
}
