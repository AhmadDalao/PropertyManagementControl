import { useTranslator } from '@/lib/i18n';

import { contentText } from '../content';
import type { CmsContent } from '../types';

export function FinalCtaSection({ content }: { content: CmsContent }) {
    const { t } = useTranslator();

    return (
        <section className="pmc-final-cta">
            <div>
                <h2>{contentText(content, 'headline')}</h2>
                <p>{contentText(content, 'body')}</p>
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
            </div>
        </section>
    );
}
