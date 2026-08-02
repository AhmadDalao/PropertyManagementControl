import { router } from '@inertiajs/react';
import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { DailyReportIndexProps } from './types';

export function ReportGenerator({ props }: { props: DailyReportIndexProps }) {
    const { t } = useTranslator();
    const [busy, setBusy] = useState(false);
    const [scope, setScope] = useState(
        props.filters.portfolio_id
            ? String(props.filters.portfolio_id)
            : props.canSelectGlobal
              ? 'global'
              : String(props.portfolioOptions[0]?.id ?? ''),
    );

    const generate = () => {
        setBusy(true);
        router.post(
            '/reports/daily-operations',
            { portfolio_id: scope === 'global' ? null : Number(scope) },
            {
                preserveScroll: true,
                onFinish: () => setBusy(false),
            },
        );
    };

    return (
        <section className="pmc-daily-report-command">
            <div>
                <span>{t('daily_reports.command_eyebrow')}</span>
                <h2>{t('daily_reports.command_title')}</h2>
                <p>{t('daily_reports.command_description')}</p>
            </div>
            <div className="pmc-daily-report-generate">
                <label>
                    <span>{t('daily_reports.scope')}</span>
                    <select
                        value={scope}
                        onChange={(event) => setScope(event.target.value)}
                    >
                        {props.canSelectGlobal ? (
                            <option value="global">
                                {t('daily_reports.global_scope')}
                            </option>
                        ) : null}
                        {props.portfolioOptions.map((portfolio) => (
                            <option key={portfolio.id} value={portfolio.id}>
                                {portfolio.name}
                            </option>
                        ))}
                    </select>
                </label>
                <button
                    type="button"
                    className="btn btn-warning"
                    disabled={busy || !scope}
                    onClick={generate}
                >
                    <i className="bi bi-file-earmark-lock" aria-hidden="true" />
                    {busy
                        ? t('daily_reports.generating')
                        : t('daily_reports.generate')}
                </button>
                <small>
                    {t('daily_reports.schedule_help', undefined, {
                        time: props.summary.schedule_time,
                    })}
                </small>
            </div>
        </section>
    );
}
