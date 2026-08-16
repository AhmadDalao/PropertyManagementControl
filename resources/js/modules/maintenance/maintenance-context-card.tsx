import { Link } from '@inertiajs/react';

import type { DetailItem } from '@/components/resource-cycle';
import { useTranslator } from '@/lib/i18n';

export function MaintenanceContextCard({
    items,
    title,
    description,
}: {
    items: DetailItem[];
    title?: string;
    description?: string;
}) {
    const { t, text } = useTranslator();

    return (
        <section className="pmc-maintenance-panel pmc-maintenance-context">
            <header>
                <h2>
                    {title ? text(title) : t('maintenance.request_context')}
                </h2>
                {description ? <p>{text(description)}</p> : null}
            </header>
            <dl>
                {items.map((item) => (
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
        </section>
    );
}
