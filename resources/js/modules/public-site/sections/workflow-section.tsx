import { contentItems } from '../content';
import type { CmsContent } from '../types';
import { SectionHeading } from './section-heading';

export function WorkflowSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-section-band" id="workflow">
            <SectionHeading content={content} />
            <div className="pmc-workflow">
                {contentItems(content, 'steps').map((step, index) => (
                    <article
                        key={String(step.title)}
                        className="pmc-workflow-step"
                    >
                        <span className="pmc-workflow-index">
                            {String(index + 1).padStart(2, '0')}
                        </span>
                        <h3>{String(step.title ?? '')}</h3>
                        <p>{String(step.body ?? '')}</p>
                    </article>
                ))}
            </div>
        </section>
    );
}
