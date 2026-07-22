import { contentItems } from '../content';
import type { CmsContent } from '../types';
import { SectionHeading } from './section-heading';

export function FaqSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band" id="faq">
            <SectionHeading content={content} />
            <div className="pmc-faq-list">
                {contentItems(content, 'items').map((item) => (
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
