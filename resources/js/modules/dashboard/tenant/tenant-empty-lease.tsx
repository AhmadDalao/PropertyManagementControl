import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { NextAction } from '../types';

export function TenantEmptyLease({ actions }: { actions: NextAction[] }) {
    const { t, text } = useTranslator();

    return (
        <section className="pmc-tenant-empty-lease">
            <i className="bi bi-hourglass-split" aria-hidden="true" />
            <div>
                <span>{t('tenant_portal.contract')}</span>
                <h2>{t('tenant_portal.empty_lease_title')}</h2>
                <p>{t('tenant_portal.empty_lease_description')}</p>
            </div>
            <nav aria-label={t('dashboard.next_action')}>
                {actions.slice(0, 2).map((action) => (
                    <Link key={action.label} href={action.href}>
                        <i className={`bi ${action.icon}`} aria-hidden="true" />
                        <span>
                            <strong>{text(action.label)}</strong>
                            <small>{text(action.description)}</small>
                        </span>
                    </Link>
                ))}
            </nav>
        </section>
    );
}
