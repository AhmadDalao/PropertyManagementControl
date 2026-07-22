import { contentText } from '../content';
import type { CmsContent } from '../types';

export function GenericSection({ content }: { content: CmsContent }) {
    const image = contentText(content, 'image');

    return (
        <section className="pmc-section-band">
            {image ? (
                <img
                    className="pmc-managed-section-image"
                    src={image}
                    alt={contentText(content, 'imageAlt')}
                />
            ) : null}
            <h2>
                {contentText(
                    content,
                    'headline',
                    contentText(content, 'title'),
                )}
            </h2>
            <p className="pmc-section-copy">{contentText(content, 'body')}</p>
        </section>
    );
}
