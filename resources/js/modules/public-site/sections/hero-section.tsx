import { useTranslator } from '@/lib/i18n';

import { contentItems, contentText } from '../content';
import type { CmsContent } from '../types';

export function HeroSection({ content }: { content: CmsContent }) {
    const { t } = useTranslator();
    const stats = contentItems(content, 'stats');
    const preview = contentItems(content, 'preview');
    const image = contentText(content, 'image');

    return (
        <section className="pmc-landing-hero" id="top">
            <div className="pmc-hero-copy">
                <div className="pmc-kicker mb-3">
                    {contentText(content, 'eyebrow', t('public.operations'))}
                </div>
                <h1>{contentText(content, 'headline')}</h1>
                <p>{contentText(content, 'subheadline')}</p>
                <div className="pmc-hero-actions">
                    <a href="/login" className="btn btn-primary btn-lg">
                        <i
                            className="bi bi-box-arrow-in-right me-2"
                            aria-hidden="true"
                        />
                        {contentText(
                            content,
                            'ctaPrimary',
                            t('public.open_portal'),
                        )}
                    </a>
                    <a
                        href="#features"
                        className="btn btn-outline-secondary btn-lg"
                    >
                        <i className="bi bi-compass me-2" aria-hidden="true" />
                        {contentText(
                            content,
                            'ctaSecondary',
                            t('public.explore_features'),
                        )}
                    </a>
                </div>
                <div className="pmc-hero-stat-row">
                    {stats.map((stat) => (
                        <div key={String(stat.label)} className="pmc-hero-stat">
                            <span>{String(stat.label ?? '')}</span>
                            <strong>{String(stat.value ?? '')}</strong>
                        </div>
                    ))}
                </div>
            </div>

            <div className={`pmc-hero-visual ${image ? 'has-image' : ''}`}>
                {image ? (
                    <img
                        className="pmc-managed-hero-image"
                        src={image}
                        alt={contentText(content, 'imageAlt')}
                    />
                ) : null}
                <div
                    className="pmc-dashboard-preview"
                    aria-label={t('public.dashboard_preview')}
                >
                    <div className="pmc-preview-topbar">
                        <span />
                        <span />
                        <span />
                        <strong>{t('public.portfolio_control')}</strong>
                    </div>
                    <div className="pmc-preview-grid">
                        {preview.map((item) => (
                            <div
                                key={String(item.label)}
                                className="pmc-preview-tile"
                            >
                                <span>{String(item.label ?? '')}</span>
                                <strong>{String(item.value ?? '')}</strong>
                            </div>
                        ))}
                    </div>
                    <div className="pmc-preview-table">
                        <PreviewRow
                            label={t('public.preview_property')}
                            value={t('status.occupied')}
                        />
                        <PreviewRow
                            label={t('public.lease_balance')}
                            value={t('public.tracked')}
                        />
                        <PreviewRow
                            label={t('nav.maintenance')}
                            value={t('status.open')}
                        />
                    </div>
                </div>
            </div>
        </section>
    );
}

function PreviewRow({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <span>{label}</span>
            <strong>{value}</strong>
        </div>
    );
}
