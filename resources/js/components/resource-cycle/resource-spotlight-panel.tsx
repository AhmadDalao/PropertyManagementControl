import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import { ActionLink } from './action-link';
import type { ResourceSpotlight } from './types';

export function ResourceSpotlightPanel({
    spotlight,
}: {
    spotlight: ResourceSpotlight;
}) {
    const { text } = useTranslator();

    return (
        <section className="pmc-resource-spotlight">
            {spotlight.image ? (
                <img
                    className="pmc-resource-spotlight-image"
                    src={spotlight.image.src}
                    alt={spotlight.image.alt}
                />
            ) : null}
            <div className="pmc-resource-spotlight-main">
                <div>
                    <div className="pmc-kicker mb-2">
                        {text(spotlight.eyebrow ?? 'Record focus')}
                    </div>
                    <h2>{text(spotlight.title)}</h2>
                    {spotlight.subtitle ? (
                        <strong>{text(spotlight.subtitle)}</strong>
                    ) : null}
                    {spotlight.description ? (
                        <p>{text(spotlight.description)}</p>
                    ) : null}
                </div>
                {spotlight.status ? <em>{text(spotlight.status)}</em> : null}
            </div>

            {spotlight.items && spotlight.items.length > 0 ? (
                <dl>
                    {spotlight.items.map((item) => (
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
            ) : null}

            {spotlight.actions && spotlight.actions.length > 0 ? (
                <div className="pmc-resource-spotlight-actions">
                    {spotlight.actions.map((action) => (
                        <ActionLink
                            key={`${action.href}-${action.label}`}
                            action={action}
                        />
                    ))}
                </div>
            ) : null}
        </section>
    );
}
