import { contentIcon, contentItems } from '../content';
import type { CmsContent } from '../types';
import { SectionHeading } from './section-heading';

export function MetricsSection({ content }: { content: CmsContent }) {
    return (
        <section id="overview" className="pmc-section-band">
            <div className="pmc-metric-grid">
                {contentItems(content, 'items').map((metric) => (
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

export function RoleCardsSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band">
            <SectionHeading content={content} />
            <div className="pmc-role-grid">
                {contentItems(content, 'items').map((item) => (
                    <article key={String(item.title)} className="pmc-role-card">
                        <i
                            className={`bi ${contentIcon(item, 'bi-person')}`}
                            aria-hidden="true"
                        />
                        <h3>{String(item.title ?? '')}</h3>
                        <p>{String(item.body ?? '')}</p>
                    </article>
                ))}
            </div>
        </section>
    );
}

export function FeatureGridSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band" id="features">
            <SectionHeading content={content} />
            <div className="pmc-feature-grid">
                {contentItems(content, 'items').map((item) => (
                    <article
                        key={String(item.title)}
                        className="pmc-feature-item"
                    >
                        <i
                            className={`bi ${contentIcon(item)}`}
                            aria-hidden="true"
                        />
                        <h3>{String(item.title ?? '')}</h3>
                        <p>{String(item.body ?? '')}</p>
                    </article>
                ))}
            </div>
        </section>
    );
}
