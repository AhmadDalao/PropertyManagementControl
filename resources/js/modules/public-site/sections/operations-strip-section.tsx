import { contentItems, contentText } from '../content';
import type { CmsContent } from '../types';

export function OperationsStripSection({ content }: { content: CmsContent }) {
    return (
        <section className="pmc-operations-strip">
            <div className="pmc-operations-inner">
                <div className="pmc-operations-copy">
                    <h2>{contentText(content, 'headline')}</h2>
                    <p>{contentText(content, 'body')}</p>
                </div>
                <div className="pmc-strip-items">
                    {contentItems(content, 'items').map((item) => (
                        <div key={String(item.label)}>
                            <strong>{String(item.value ?? '')}</strong>
                            <span>{String(item.label ?? '')}</span>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
