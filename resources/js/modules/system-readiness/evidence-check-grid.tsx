import { useTranslator } from '@/lib/i18n';

import type { ReadinessConfirmation } from './types';

export function EvidenceCheckGrid({
    checks,
    mailTarget,
    mailEnabled,
    mailBusy,
    onConfirm,
    onReset,
    onTestEmail,
}: {
    checks: ReadinessConfirmation[];
    mailTarget?: string;
    mailEnabled?: boolean;
    mailBusy?: boolean;
    onConfirm: (check: ReadinessConfirmation) => void;
    onReset: (check: ReadinessConfirmation) => void;
    onTestEmail?: () => void;
}) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-readiness-evidence-grid" role="list">
            {checks.map((check) => (
                <article
                    key={check.key}
                    className={
                        check.is_confirmed ? 'is-confirmed' : 'is-pending'
                    }
                    role="listitem"
                >
                    <header>
                        <span>
                            <i
                                className={`bi ${
                                    check.is_confirmed
                                        ? 'bi-check2-circle'
                                        : 'bi-clock-history'
                                }`}
                                aria-hidden="true"
                            />
                        </span>
                        <div>
                            <h3>{check.label}</h3>
                            <small>
                                {check.is_confirmed
                                    ? t('readiness.confirmed')
                                    : t('readiness.evidence_required')}
                            </small>
                        </div>
                    </header>
                    <p>{check.description}</p>
                    {check.is_confirmed ? (
                        <div className="pmc-readiness-evidence">
                            <strong>{check.evidence}</strong>
                            <small>
                                {t('readiness.confirmed_by', undefined, {
                                    name:
                                        check.confirmed_by ??
                                        t('readiness.system_user'),
                                    date: check.confirmed_at
                                        ? new Intl.DateTimeFormat(locale, {
                                              dateStyle: 'medium',
                                              timeStyle: 'short',
                                          }).format(
                                              new Date(check.confirmed_at),
                                          )
                                        : '',
                                })}
                            </small>
                        </div>
                    ) : null}
                    {check.key === 'smtp_delivery' && mailTarget ? (
                        <div className="pmc-readiness-mail-test">
                            <small>
                                {t('readiness.test_target', undefined, {
                                    email: mailTarget,
                                })}
                            </small>
                            <button
                                type="button"
                                className="btn btn-outline-secondary btn-sm"
                                disabled={!mailEnabled || mailBusy}
                                onClick={onTestEmail}
                            >
                                <i
                                    className="bi bi-arrow-right-circle"
                                    aria-hidden="true"
                                />
                                {mailBusy
                                    ? t('readiness.sending_test')
                                    : t('readiness.send_test')}
                            </button>
                        </div>
                    ) : null}
                    <footer>
                        {check.is_confirmed ? (
                            <button
                                type="button"
                                className="btn btn-outline-secondary btn-sm"
                                onClick={() => onReset(check)}
                            >
                                {t('readiness.reset_confirmation')}
                            </button>
                        ) : (
                            <button
                                type="button"
                                className="btn btn-primary btn-sm"
                                onClick={() => onConfirm(check)}
                            >
                                <i
                                    className="bi bi-check2"
                                    aria-hidden="true"
                                />
                                {t('readiness.add_evidence')}
                            </button>
                        )}
                    </footer>
                </article>
            ))}
        </div>
    );
}
