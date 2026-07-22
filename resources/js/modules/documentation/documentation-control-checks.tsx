import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { ControlCheck } from './types';

export function DocumentationControlChecks({
    checks,
    searchActive,
}: {
    checks: ControlCheck[];
    searchActive: boolean;
}) {
    const { t } = useTranslator();

    if (checks.length === 0) {
        return null;
    }

    return (
        <details className="pmc-doc-controls" open={searchActive}>
            <summary>
                <div>
                    <i className="bi bi-shield-check" />
                    <span>{t('docs.regulation_checks')}</span>
                    <strong>{checks.length}</strong>
                </div>
                <i className="bi bi-chevron-down" />
            </summary>
            <div>
                {checks.map((check) => (
                    <article key={check.title}>
                        <i className={`bi ${check.icon}`} />
                        <div>
                            <strong>{check.title}</strong>
                            <span>{check.summary}</span>
                            <ul>
                                {check.checks.map((item) => (
                                    <li key={item}>{item}</li>
                                ))}
                            </ul>
                        </div>
                        <Link
                            href={check.route}
                            className="btn btn-outline-secondary btn-sm"
                        >
                            {t('actions.open')}
                        </Link>
                    </article>
                ))}
            </div>
        </details>
    );
}
