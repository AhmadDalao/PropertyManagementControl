import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { DetailSection } from './types';

export function DetailCard({ section }: { section: DetailSection }) {
    const { text } = useTranslator();

    return (
        <article className="pmc-card p-4 pmc-resource-detail-card">
            <header>
                <div>
                    <div className="pmc-kicker mb-2">{text(section.title)}</div>
                    {section.description ? (
                        <p>{text(section.description)}</p>
                    ) : null}
                </div>
            </header>
            <dl>
                {section.items.map((item) => (
                    <div key={item.label}>
                        <dt>{text(item.label)}</dt>
                        <dd>
                            {item.href ? (
                                <Link href={item.href}>
                                    {item.value ?? '-'}
                                </Link>
                            ) : (
                                (item.value ?? '-')
                            )}
                        </dd>
                    </div>
                ))}
            </dl>
        </article>
    );
}
