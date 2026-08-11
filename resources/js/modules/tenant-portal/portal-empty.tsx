import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

export function PortalEmpty({
    kind,
}: {
    kind: 'lease' | 'payment' | 'document';
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-portal-empty">
            <i className="bi bi-house-check" aria-hidden="true" />
            <h2>{t(`tenant_portal.empty_${kind}_title`)}</h2>
            <p>{t(`tenant_portal.empty_${kind}_description`)}</p>
            <Link href="/documentation">
                {t('tenant_portal.open_help')}
                <i className="bi bi-arrow-up-right" aria-hidden="true" />
            </Link>
        </section>
    );
}
