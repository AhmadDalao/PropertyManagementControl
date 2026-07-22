import { useTranslator } from '@/lib/i18n';
import { CmsRenderer } from '@/modules/public-site/cms-renderer';

import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderPreviewPane({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { t } = useTranslator();

    return (
        <main className="pmc-cms-preview-pane">
            <header className="pmc-cms-preview-toolbar">
                <div>
                    <span>{t('cms.live_canvas')}</span>
                    <strong>
                        {builder.previewLocale === 'ar'
                            ? builder.page.title_ar || builder.page.title_en
                            : builder.page.title_en || builder.page.title_ar}
                    </strong>
                </div>
                <div>
                    <div role="group" aria-label={t('cms.preview_language')}>
                        {(['en', 'ar'] as const).map((locale) => (
                            <button
                                key={locale}
                                type="button"
                                className={
                                    builder.previewLocale === locale
                                        ? 'active'
                                        : ''
                                }
                                aria-pressed={builder.previewLocale === locale}
                                onClick={() => builder.setPreviewLocale(locale)}
                            >
                                {locale.toUpperCase()}
                            </button>
                        ))}
                    </div>
                    <div role="group" aria-label={t('cms.preview_width')}>
                        <button
                            type="button"
                            className={
                                builder.previewWidth === 'desktop'
                                    ? 'active'
                                    : ''
                            }
                            aria-label={t('cms.desktop_preview')}
                            aria-pressed={builder.previewWidth === 'desktop'}
                            onClick={() => builder.setPreviewWidth('desktop')}
                        >
                            <i className="bi bi-display" />
                        </button>
                        <button
                            type="button"
                            className={
                                builder.previewWidth === 'mobile'
                                    ? 'active'
                                    : ''
                            }
                            aria-label={t('cms.mobile_preview')}
                            aria-pressed={builder.previewWidth === 'mobile'}
                            onClick={() => builder.setPreviewWidth('mobile')}
                        >
                            <i className="bi bi-phone" />
                        </button>
                    </div>
                </div>
            </header>
            <div
                className={`pmc-cms-preview-frame is-${builder.previewWidth}`}
                dir={builder.previewLocale === 'ar' ? 'rtl' : 'ltr'}
                lang={builder.previewLocale}
            >
                <div className="pmc-cms-preview-document">
                    {builder.visibleSections.length > 0 ? (
                        <CmsRenderer
                            sections={builder.visibleSections}
                            locale={builder.previewLocale}
                        />
                    ) : (
                        <div className="pmc-empty-state">
                            <i className="bi bi-layout-text-window" />
                            <strong>{t('cms.no_visible_sections')}</strong>
                            <span>{t('cms.no_visible_sections_help')}</span>
                        </div>
                    )}
                </div>
            </div>
        </main>
    );
}
