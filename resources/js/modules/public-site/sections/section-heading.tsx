import { contentText } from '../content';
import type { CmsContent } from '../types';

export function SectionHeading({ content }: { content: CmsContent }) {
    const eyebrow = contentText(content, 'eyebrow');

    return (
        <div className="pmc-section-heading">
            {eyebrow ? <div className="pmc-kicker mb-2">{eyebrow}</div> : null}
            <h2>{contentText(content, 'headline')}</h2>
        </div>
    );
}
