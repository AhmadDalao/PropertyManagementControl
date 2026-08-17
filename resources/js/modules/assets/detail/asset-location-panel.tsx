import { Link } from '@inertiajs/react';

import { ActionLink } from '@/components/resource-cycle/action-link';
import { useTranslator } from '@/lib/i18n';

import type { AssetDetailPage } from './types';

export function AssetLocationPanel({
    spotlight,
}: Pick<AssetDetailPage, 'spotlight'>) {
    const { t, text } = useTranslator();

    return (
        <article className="pmc-asset-location-panel">
            <header>
                <div>
                    <small>
                        {text(
                            spotlight.eyebrow ??
                                t('assets.clicked_land_record'),
                        )}
                    </small>
                    <h2>{text(spotlight.title)}</h2>
                    {spotlight.subtitle ? (
                        <strong>{text(spotlight.subtitle)}</strong>
                    ) : null}
                    {spotlight.description ? (
                        <p>{text(spotlight.description)}</p>
                    ) : null}
                </div>
                {spotlight.status ? <em>{text(spotlight.status)}</em> : null}
            </header>
            {spotlight.items?.length ? (
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
            {spotlight.actions?.length ? (
                <footer>
                    {spotlight.actions.map((action) => (
                        <ActionLink
                            key={`${action.href}-${action.label}`}
                            action={action}
                        />
                    ))}
                </footer>
            ) : null}
        </article>
    );
}
