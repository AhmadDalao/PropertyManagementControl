import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { currency, dateTime, humanDate, localizedNumber } from '@/lib/utils';

import type {
    CollectionFollowUpPageData,
    CollectionFollowUpRecord,
} from './types';

export function FollowUpHistory({
    collection,
}: {
    collection: CollectionFollowUpPageData;
}) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-collection-history">
            <header>
                <div>
                    <span>{t('rent_collection.accountability')}</span>
                    <h2>{t('rent_collection.follow_up_history')}</h2>
                    <p>{t('rent_collection.follow_up_history_help')}</p>
                </div>
                <strong>
                    {localizedNumber(
                        collection.latest_follow_up.history_count ?? 0,
                        locale,
                    )}
                </strong>
            </header>

            {collection.history.length > 0 ? (
                <div className="pmc-collection-timeline">
                    {collection.history.map((followUp) => (
                        <HistoryEntry
                            key={followUp.id}
                            followUp={followUp}
                            currencyCode={collection.installment.currency}
                            locale={locale}
                        />
                    ))}
                </div>
            ) : (
                <div className="pmc-collection-history-empty">
                    <i className="bi bi-telephone" aria-hidden="true" />
                    <strong>{t('rent_collection.no_follow_up_history')}</strong>
                    <p>{t('rent_collection.no_follow_up_history_help')}</p>
                </div>
            )}

            {collection.history_truncated ? (
                <p className="pmc-collection-history-note">
                    {t('rent_collection.history_recent_limit')}
                </p>
            ) : null}
        </section>
    );
}

function HistoryEntry({
    followUp,
    currencyCode,
    locale,
}: {
    followUp: CollectionFollowUpRecord;
    currencyCode: string;
    locale: string;
}) {
    const { t } = useTranslator();

    return (
        <article>
            <div className="pmc-collection-timeline-marker">
                <i
                    className={`bi ${methodIcon(followUp.contact_method)}`}
                    aria-hidden="true"
                />
            </div>
            <div>
                <header>
                    <StatusBadge
                        value={followUp.outcome ?? 'contacted'}
                        label={t(
                            `rent_collection.outcome_${followUp.outcome ?? 'contacted'}` as UiTranslationKey,
                        )}
                        tone={outcomeTone(followUp.outcome)}
                    />
                    <time>{dateTime(followUp.contacted_at, locale)}</time>
                </header>
                <p>{followUp.note}</p>
                <dl>
                    <div>
                        <dt>{t('rent_collection.contact_method')}</dt>
                        <dd>
                            {t(
                                `rent_collection.contact_method_${followUp.contact_method ?? 'other'}` as UiTranslationKey,
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt>{t('rent_collection.assigned_to')}</dt>
                        <dd>
                            {followUp.assigned_to?.name ??
                                t('rent_collection.not_assigned')}
                        </dd>
                    </div>
                    <div>
                        <dt>{t('rent_collection.next_follow_up')}</dt>
                        <dd>{humanDate(followUp.next_follow_up_on, locale)}</dd>
                    </div>
                    {followUp.promised_amount !== null &&
                    followUp.promised_amount !== undefined ? (
                        <div>
                            <dt>{t('rent_collection.promise')}</dt>
                            <dd>
                                {currency(
                                    followUp.promised_amount,
                                    locale,
                                    currencyCode,
                                )}{' '}
                                · {humanDate(followUp.promised_on, locale)}
                            </dd>
                        </div>
                    ) : null}
                </dl>
                <small>
                    {t('rent_collection.recorded_by', undefined, {
                        name:
                            followUp.recorded_by?.name ?? t('resource.system'),
                    })}
                </small>
            </div>
        </article>
    );
}

function methodIcon(method?: string | null): string {
    return (
        {
            phone: 'bi-telephone',
            email: 'bi-envelope',
            whatsapp: 'bi-whatsapp',
            in_person: 'bi-person',
        }[method ?? ''] ?? 'bi-chat-left-text'
    );
}

function outcomeTone(
    outcome?: string | null,
): 'success' | 'warning' | 'danger' | 'neutral' | 'blue' {
    if (outcome === 'payment_arranged') {
        return 'success';
    }

    if (outcome === 'disputed') {
        return 'danger';
    }

    if (outcome === 'promise_to_pay') {
        return 'blue';
    }

    return outcome === 'no_answer' ? 'warning' : 'neutral';
}
