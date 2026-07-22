import { Link, router } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

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
                    <Link href={preset.url}>{t('actions.open')}</Link>
                    {preset.can_delete ? (
                        <button
                            type="button"
                            onClick={() =>
                                router.delete(`/reports/presets/${preset.id}`, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            {t('reports.remove')}
                        </button>
                    ) : null}
                </article>
            ))}
        </div>
    );
}
