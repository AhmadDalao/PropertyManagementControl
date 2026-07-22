import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { ModuleStatus, QuickStart, RoleGuide } from './types';

type DocumentationCommandProps = {
    audience: string;
    roleGuide: RoleGuide | null;
    moduleStatus: ModuleStatus[];
    quickStarts: QuickStart[];
    query: string;
    onQueryChange: (query: string) => void;
};

export function DocumentationCommand({
    audience,
    roleGuide,
    moduleStatus,
    quickStarts,
    query,
    onQueryChange,
}: DocumentationCommandProps) {
    const { t } = useTranslator();
    const enabledModules = moduleStatus.filter(
        (module) => module.enabled,
    ).length;

    return (
        <section className="pmc-doc-command">
            <div className="pmc-doc-role-card">
                <span className="pmc-doc-role-icon" aria-hidden="true">
                    <i className={`bi ${roleGuide?.icon ?? 'bi-person'}`} />
                </span>
                <div>
                    <span>{t('docs.your_access')}</span>
                    <strong>{roleGuide?.title ?? audience}</strong>
                    <small>{roleGuide?.summary ?? t('docs.description')}</small>
                    <em>
                        {t('docs.active_modules', undefined, {
                            count: enabledModules,
                        })}
                    </em>
                </div>
                <div className="pmc-doc-role-responsibilities">
                    {(roleGuide?.responsibilities ?? [])
                        .slice(0, 3)
                        .map((responsibility) => (
                            <span key={responsibility}>{responsibility}</span>
                        ))}
                </div>
            </div>

            <div className="pmc-doc-discovery">
                <label htmlFor="documentation-search">
                    <span>{t('docs.search')}</span>
                    <div>
                        <i className="bi bi-search" />
                        <input
                            id="documentation-search"
                            type="search"
                            className="form-control"
                            placeholder={t('docs.search_placeholder')}
                            value={query}
                            onChange={(event) =>
                                onQueryChange(event.currentTarget.value)
                            }
                        />
                    </div>
                </label>

                {quickStarts.length > 0 ? (
                    <nav aria-label={t('docs.recommended_start')}>
                        <span>{t('docs.recommended_start')}</span>
                        <div>
                            {quickStarts.slice(0, 3).map((item) => (
                                <Link key={item.title} href={item.route}>
                                    <i className={`bi ${item.icon}`} />
                                    <span>{item.title}</span>
                                    <i className="bi bi-arrow-up-right" />
                                </Link>
                            ))}
                        </div>
                    </nav>
                ) : null}
            </div>
        </section>
    );
}
