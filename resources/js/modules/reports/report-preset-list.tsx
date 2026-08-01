import { Link, router } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import type { ReportPreset } from './types';

export function ReportPresetList({ presets }: { presets: ReportPreset[] }) {
    const { locale, t } = useTranslator();

    if (presets.length === 0) {
        return (
            <div className="pmc-report-preset-list">
                <p>{t('reports.no_saved_views')}</p>
            </div>
        );
    }

    return (
        <div className="pmc-report-preset-list">
            {presets.map((preset) => (
                <article key={preset.id}>
                    <header>
                        <span className="pmc-report-preset-icon">
                            <i
                                className="bi bi-bar-chart-line"
                                aria-hidden="true"
                            />
                        </span>
                        <div>
                            <strong>
                                {locale === 'ar'
                                    ? preset.title_ar || preset.title_en
                                    : preset.title_en || preset.title_ar}
                            </strong>
                            <span>
                                {t(`reports.visibility_${preset.visibility}`)}
                                {preset.is_default
                                    ? ` · ${t('reports.default_view')}`
                                    : ''}
                            </span>
                        </div>
                    </header>
                    <dl>
                        <div>
                            <dt>{t('reports.report_period')}</dt>
                            <dd>
                                {t(`reports.period_${preset.period}`)}
                                <small>
                                    {humanDate(preset.date_from, locale)}
                                    {' – '}
                                    {humanDate(preset.date_to, locale)}
                                </small>
                            </dd>
                        </div>
                        <div>
                            <dt>{t('reports.report_scope')}</dt>
                            <dd>{preset.scope_label}</dd>
                        </div>
                    </dl>
                    <div className="pmc-report-preset-actions">
                        <Link href={preset.url}>
                            <i
                                className="bi bi-arrow-up-right"
                                aria-hidden="true"
                            />
                            {t('reports.open_saved_view')}
                        </Link>
                        <a href={preset.export_url}>
                            <i
                                className="bi bi-file-earmark-excel"
                                aria-hidden="true"
                            />
                            {t('reports.download_saved_xlsx')}
                        </a>
                        {preset.can_edit ||
                        preset.can_duplicate ||
                        preset.can_delete ? (
                            <details className="pmc-report-preset-menu">
                                <summary>
                                    <i
                                        className="bi bi-three-dots"
                                        aria-hidden="true"
                                    />
                                    {t('reports.manage_saved_report')}
                                </summary>
                                <div>
                                    {preset.can_edit ? (
                                        <Link href={preset.edit_url}>
                                            <i
                                                className="bi bi-pencil"
                                                aria-hidden="true"
                                            />
                                            {t('actions.edit')}
                                        </Link>
                                    ) : null}
                                    {preset.can_duplicate ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                router.post(
                                                    `/reports/saved/${preset.id}/duplicate`,
                                                    {},
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
                                        >
                                            <i
                                                className="bi bi-copy"
                                                aria-hidden="true"
                                            />
                                            {t('reports.duplicate')}
                                        </button>
                                    ) : null}
                                    {preset.can_delete ? (
                                        <button
                                            type="button"
                                            className="is-danger"
                                            onClick={() => {
                                                if (
                                                    window.confirm(
                                                        t(
                                                            'reports.remove_saved_confirm',
                                                        ),
                                                    )
                                                ) {
                                                    router.delete(
                                                        `/reports/saved/${preset.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    );
                                                }
                                            }}
                                        >
                                            <i
                                                className="bi bi-trash3"
                                                aria-hidden="true"
                                            />
                                            {t('reports.remove')}
                                        </button>
                                    ) : null}
                                </div>
                            </details>
                        ) : null}
                    </div>
                </article>
            ))}
        </div>
    );
}
