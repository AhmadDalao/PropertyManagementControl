import { useTranslator } from '@/lib/i18n';

import { usePortalAccessLink } from './use-portal-access-link';

export function PortalAccessGenerator({
    endpoint,
    canGenerate,
    expiresInMinutes,
}: {
    endpoint: string;
    canGenerate: boolean;
    expiresInMinutes: number;
}) {
    const { t } = useTranslator();
    const { busy, copy, error, generate, link, status } =
        usePortalAccessLink(endpoint);

    return (
        <section className="pmc-card pmc-portal-access-generator">
            <div className="pmc-portal-access-icon" aria-hidden="true">
                <i className="bi bi-person-lock" />
            </div>
            <div className="pmc-portal-access-copy">
                <span>{t('users.portal_access_secure_handoff')}</span>
                <h2>{t('users.portal_access_generator_title')}</h2>
                <p>
                    {t('users.portal_access_generator_help', undefined, {
                        minutes: expiresInMinutes,
                    })}
                </p>
            </div>

            {!canGenerate ? (
                <div className="pmc-portal-access-alert is-danger">
                    <i
                        className="bi bi-exclamation-triangle"
                        aria-hidden="true"
                    />
                    <span>{t('users.portal_access_inactive_help')}</span>
                </div>
            ) : null}

            {link ? (
                <div className="pmc-portal-access-result">
                    <label htmlFor="portal-access-link">
                        {t('users.portal_access_link_label')}
                    </label>
                    <div className="pmc-portal-access-link">
                        <input
                            id="portal-access-link"
                            type="text"
                            value={link.url}
                            readOnly
                            dir="ltr"
                            onFocus={(event) => event.currentTarget.select()}
                        />
                        <button type="button" onClick={copy}>
                            <i
                                className={`bi ${status === 'copied' ? 'bi-check2' : 'bi-copy'}`}
                                aria-hidden="true"
                            />
                            {t(
                                status === 'copied'
                                    ? 'users.portal_access_copied'
                                    : 'users.portal_access_copy',
                            )}
                        </button>
                    </div>
                    <div className="pmc-portal-access-result-meta">
                        <span>
                            <i className="bi bi-clock" aria-hidden="true" />
                            {t('users.portal_access_expires', undefined, {
                                minutes: link.expires_in_minutes,
                            })}
                        </span>
                        <a href={link.url} target="_blank" rel="noreferrer">
                            {t('users.portal_access_open')}
                            <i
                                className="bi bi-box-arrow-up-right"
                                aria-hidden="true"
                            />
                        </a>
                    </div>
                </div>
            ) : null}

            {error ? (
                <div className="pmc-portal-access-alert is-danger" role="alert">
                    {error}
                </div>
            ) : null}

            <button
                className="pmc-portal-access-generate"
                type="button"
                onClick={generate}
                disabled={busy || !canGenerate}
            >
                <i
                    className={`bi ${busy ? 'bi-arrow-repeat' : 'bi-link-45deg'}`}
                    aria-hidden="true"
                />
                {t(
                    busy
                        ? 'users.portal_access_generating'
                        : link
                          ? 'users.portal_access_generate_new'
                          : 'users.portal_access_generate',
                )}
            </button>
            <p className="pmc-portal-access-revoke-note">
                {t('users.portal_access_revoke_note')}
            </p>

            <span className="visually-hidden" aria-live="polite">
                {status === 'copied'
                    ? t('users.portal_access_copied')
                    : status === 'copy_failed'
                      ? t('users.portal_access_copy_failed')
                      : ''}
            </span>
        </section>
    );
}
