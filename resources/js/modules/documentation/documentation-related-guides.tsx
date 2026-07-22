import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { Guide } from './types';

export function DocumentationRelatedGuides({ guides }: { guides: Guide[] }) {
    const { t } = useTranslator();

    if (guides.length === 0) {
        return null;
    }

    return (
        <section className="pmc-doc-related">
            <div>
                <span>{t('docs.keep_learning')}</span>
                <h2>{t('docs.related_guides')}</h2>
            </div>
            <div>
                {guides.map((guide) => (
                    <Link
                        key={guide.slug}
                        href={`/documentation/${guide.slug}`}
                    >
                        <i className={`bi ${guide.icon}`} />
                        <strong>{guide.title}</strong>
                        <i className="bi bi-arrow-up-right" />
                    </Link>
                ))}
            </div>
        </section>
    );
}
