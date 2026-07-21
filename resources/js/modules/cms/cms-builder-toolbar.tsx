import { useTranslator } from '@/lib/i18n';

import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderToolbar({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { t } = useTranslator();
    const statusLabel =
        builder.saveState === 'saving'
            ? t('cms.saving')
            : builder.saveState === 'error'
              ? t('cms.save_failed')
              : t('cms.saved');

    return (
        <div className="pmc-cms-builder-toolbar">
            <div className="pmc-cms-builder-status" aria-live="polite">
                <span className={`is-${builder.saveState}`} />
                <strong>{statusLabel}</strong>
            </div>

            <div className="pmc-cms-mobile-tabs">
                {(['sections', 'preview', 'settings'] as const).map((panel) => (
                    <button
                        key={panel}
                        type="button"
                        className={
                            builder.mobilePanel === panel ? 'active' : ''
                        }
                        aria-pressed={builder.mobilePanel === panel}
                        onClick={() => builder.setMobilePanel(panel)}
                    >
                        {t(`cms.panel_${panel}`)}
                    </button>
                ))}
            </div>

            <div className="pmc-cms-builder-pills">
                <span>{t(`status.${builder.page.status}`)}</span>
                <span>
                    {builder.page.is_homepage
                        ? t('cms.homepage')
                        : t('cms.standard_page')}
                </span>
                <span>
                    {t('cms.visible_count', undefined, {
                        count: builder.visibleSections.length,
                    })}
                </span>
            </div>
        </div>
    );
}
