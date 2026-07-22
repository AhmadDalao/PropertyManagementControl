import { contentItems, contentText } from '../content';
import type { CmsContent } from '../types';
import { SectionHeading } from './section-heading';

export function DashboardPreviewSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band pmc-section-split">
            <div>
                <SectionHeading content={content} />
                <p className="pmc-section-copy">
                    {contentText(content, 'body')}
                </p>
            </div>
            <div className="pmc-control-list">
                {contentItems(content, 'metrics').map((metric) => (
                    <div key={String(metric.label)}>
                        <span>{String(metric.label ?? '')}</span>
                        <strong>{String(metric.value ?? '')}</strong>
                    </div>
                ))}
            </div>
        </section>
    );
}
