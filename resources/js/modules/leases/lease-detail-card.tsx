import { Link } from '@inertiajs/react';

import type { DetailSection } from '@/components/resource-cycle';
import { useTranslator } from '@/lib/i18n';

export function LeaseDetailCard({ section }: { section: DetailSection }) {
    const { text } = useTranslator();

    return (
        <article className="pmc-lease-detail-card">
            <header>
                <h2>{text(section.title)}</h2>
                {section.description ? (
                    <p>{text(section.description)}</p>
                ) : null}
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
