type CmsContent = Record<string, unknown>;

type SectionRecord = {
    id: number;
    section?: {
        id?: number;
        section_type: string;
        content_en?: CmsContent | null;
        content_ar?: CmsContent | null;
    } | null;
};

type CmsRendererProps = {
    sections: SectionRecord[];
    locale: 'en' | 'ar';
};

type CmsItem = Record<string, unknown>;

function text(content: CmsContent, key: string, fallback = '') {
    const value = content[key];

    return typeof value === 'string' ? value : fallback;
}

function items(content: CmsContent, key: string) {
    const value = content[key];

    return Array.isArray(value) ? (value as CmsItem[]) : [];
}

function iconClass(item: CmsItem, fallback = 'bi-grid') {
    const icon = item.icon;

    return typeof icon === 'string' && icon.startsWith('bi-') ? icon : fallback;
}

export function CmsRenderer({ sections, locale }: CmsRendererProps) {
    return (
        <>
            {sections.map((item) => {
                const section = item.section;
                const content =
                    (locale === 'ar'
                        ? section?.content_ar
                        : section?.content_en) ?? {};

                if (!section) {
                    return null;
                }

                if (section.section_type === 'hero') {
                    return <HeroSection key={item.id} content={content} />;
                }

                if (section.section_type === 'metrics') {
                    return <MetricsSection key={item.id} content={content} />;
                }

                if (section.section_type === 'role_cards') {
                    return <RoleCardsSection key={item.id} content={content} />;
                }

                if (section.section_type === 'workflow') {
                    return <WorkflowSection key={item.id} content={content} />;
                }

                if (section.section_type === 'dashboard_preview') {
                    return (
                        <DashboardPreviewSection
                            key={item.id}
                            content={content}
                        />
                    );
                }

                if (section.section_type === 'feature_grid') {
                    return (
                        <FeatureGridSection key={item.id} content={content} />
                    );
                }

                if (section.section_type === 'operations_strip') {
                    return (
                        <OperationsStripSection
                            key={item.id}
                            content={content}
                        />
                    );
                }

                if (section.section_type === 'faq') {
                    return <FaqSection key={item.id} content={content} />;
                }

                if (section.section_type === 'final_cta') {
                    return <FinalCtaSection key={item.id} content={content} />;
                }

                return <GenericSection key={item.id} content={content} />;
            })}
        </>
    );
}

function HeroSection({ content }: { content: CmsContent }) {
    const { t } = useTranslator();
    const stats = items(content, 'stats');
    const preview = items(content, 'preview');

    return (
        <section className="pmc-landing-hero" id="top">
            <div className="pmc-hero-copy">
                <div className="pmc-kicker mb-3">
                    {text(content, 'eyebrow', t('public.operations'))}
                </div>
                <h1>{text(content, 'headline')}</h1>
                <p>{text(content, 'subheadline')}</p>
                <div className="pmc-hero-actions">
                    <a href="/login" className="btn btn-primary btn-lg">
                        <i className="bi bi-box-arrow-in-right me-2" />
                        {text(content, 'ctaPrimary', t('public.open_portal'))}
                    </a>
                    <a
                        href="#features"
                        className="btn btn-outline-secondary btn-lg"
                    >
                        <i className="bi bi-compass me-2" />
                        {text(
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
                    <div>
                        <span>{t('public.preview_property')}</span>
                        <strong>{t('status.occupied')}</strong>
                    </div>
                    <div>
                        <span>{t('public.lease_balance')}</span>
                        <strong>{t('public.tracked')}</strong>
                    </div>
                    <div>
                        <span>{t('nav.maintenance')}</span>
                        <strong>{t('status.open')}</strong>
                    </div>
                </div>
            </div>
        </section>
    );
}

function MetricsSection({ content }: { content: CmsContent }) {
    return (
        <section id="overview" className="pmc-section-band">
            <div className="pmc-metric-grid">
                {items(content, 'items').map((metric) => (
                    <div
                        key={String(metric.label)}
                        className="pmc-metric-panel"
                    >
                        <span>{String(metric.label ?? '')}</span>
                        <strong>{String(metric.value ?? '')}</strong>
                    </div>
                ))}
            </div>
        </section>
    );
}

function RoleCardsSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band">
            <SectionHeading content={content} />
            <div className="pmc-role-grid">
                {items(content, 'items').map((item) => (
                    <article key={String(item.title)} className="pmc-role-card">
                        <i className={`bi ${iconClass(item, 'bi-person')}`} />
                        <h3>{String(item.title ?? '')}</h3>
                        <p>{String(item.body ?? '')}</p>
                    </article>
                ))}
            </div>
        </section>
    );
}

function WorkflowSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band" id="workflow">
            <SectionHeading content={content} />
            <div className="pmc-workflow">
                {items(content, 'steps').map((step, index) => (
                    <article
                        key={String(step.title)}
                        className="pmc-workflow-step"
                    >
                        <span>{index + 1}</span>
                        <h3>{String(step.title ?? '')}</h3>
                        <p>{String(step.body ?? '')}</p>
                    </article>
                ))}
            </div>
        </section>
    );
}

function DashboardPreviewSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band pmc-section-split">
            <div>
                <SectionHeading content={content} />
                <p className="pmc-section-copy">{text(content, 'body')}</p>
            </div>
            <div className="pmc-control-list">
                {items(content, 'metrics').map((metric) => (
                    <div key={String(metric.label)}>
                        <span>{String(metric.label ?? '')}</span>
                        <strong>{String(metric.value ?? '')}</strong>
                    </div>
                ))}
            </div>
        </section>
    );
}

function FeatureGridSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band" id="features">
            <SectionHeading content={content} />
            <div className="pmc-feature-grid">
                {items(content, 'items').map((item) => (
                    <article
                        key={String(item.title)}
                        className="pmc-feature-item"
                    >
                        <i className={`bi ${iconClass(item)}`} />
                        <h3>{String(item.title ?? '')}</h3>
                        <p>{String(item.body ?? '')}</p>
                    </article>
                ))}
            </div>
        </section>
    );
}

function OperationsStripSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-operations-strip">
            <div>
                <h2>{text(content, 'headline')}</h2>
                <p>{text(content, 'body')}</p>
            </div>
            <div className="pmc-strip-items">
                {items(content, 'items').map((item) => (
                    <div key={String(item.label)}>
                        <span>{String(item.label ?? '')}</span>
                        <strong>{String(item.value ?? '')}</strong>
                    </div>
                ))}
            </div>
        </section>
    );
}

function FaqSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band" id="faq">
            <SectionHeading content={content} />
            <div className="pmc-faq-list">
                {items(content, 'items').map((item) => (
                    <details
                        key={String(item.question)}
                        className="pmc-faq-item"
                    >
                        <summary>{String(item.question ?? '')}</summary>
                        <p>{String(item.answer ?? '')}</p>
                    </details>
                ))}
            </div>
        </section>
    );
}

function FinalCtaSection({ content }: { content: CmsContent }) {
    const { t } = useTranslator();

    return (
        <section className="pmc-final-cta">
            <h2>{text(content, 'headline')}</h2>
            <p>{text(content, 'body')}</p>
            <a href="/login" className="btn btn-primary btn-lg">
                <i className="bi bi-box-arrow-in-right me-2" />
                {text(content, 'ctaPrimary', t('public.open_portal'))}
            </a>
        </section>
    );
}

function GenericSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band">
            <h2>{text(content, 'headline', text(content, 'title'))}</h2>
            <p className="pmc-section-copy">{text(content, 'body')}</p>
        </section>
    );
}

function SectionHeading({ content }: { content: CmsContent }) {
    return (
        <div className="pmc-section-heading">
            {text(content, 'eyebrow') ? (
                <div className="pmc-kicker mb-2">
                    {text(content, 'eyebrow')}
                </div>
            ) : null}
            <h2>{text(content, 'headline')}</h2>
        </div>
    );
}
import { useTranslator } from '@/lib/i18n';
