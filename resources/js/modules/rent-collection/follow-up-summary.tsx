import { Link } from '@inertiajs/react';

import { ShowcaseBadge } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import type { CollectionFollowUpPageData } from './types';

export function FollowUpSummary({
    collection,
}: {
    collection: CollectionFollowUpPageData;
}) {
    const { locale, t } = useTranslator();
    const state = collection.latest_follow_up.state ?? 'untracked';
    const property = localizedAsset(collection.property, locale);
    const asset = localizedAsset(collection.asset, locale);

    return (
        <section className="pmc-collection-summary">
            <div className={`pmc-collection-state is-${state}`}>
                <span>{t('rent_collection.current_collection_state')}</span>
                <StatusBadge
                    value={state}
                    label={t(
                        `rent_collection.follow_up_state_${state}` as UiTranslationKey,
                    )}
                    tone={stateTone(state)}
                />
                <strong>
                    {currency(
                        collection.installment.outstanding_amount,
                        locale,
                        collection.installment.currency,
                    )}
                </strong>
                <small>{nextAction(collection, t, locale)}</small>
                {collection.installment.is_showcase ? (
                    <ShowcaseBadge label={t('showcase.badge')} />
                ) : null}
            </div>

            <dl>
                <div>
                    <dt>{t('rent_collection.tenant')}</dt>
                    <dd>{collection.tenant.name}</dd>
                    <small>
                        {collection.tenant.phone ||
                            collection.tenant.email ||
                            t('rent_collection.no_contact_details')}
                    </small>
                </div>
                <div>
                    <dt>{t('rent_collection.property_asset')}</dt>
                    <dd>{property ?? t('rent_collection.no_property')}</dd>
                    <small>
                        {asset ?? t('rent_collection.no_asset')}
                        {collection.asset?.code
                            ? ` · ${collection.asset.code}`
                            : ''}
                    </small>
                </div>
                <div>
                    <dt>{t('rent_collection.lease')}</dt>
                    <dd>
                        <Link href={collection.links.lease}>
                            {collection.lease.code}
                        </Link>
                    </dd>
                    <small>{collection.installment.label}</small>
                </div>
                <div>
                    <dt>{t('rent_collection.due_date')}</dt>
                    <dd>
                        {humanDate(collection.installment.due_date, locale)}
                    </dd>
                    <small>
                        {collection.installment.days_overdue > 0
                            ? t('rent_collection.days_overdue', undefined, {
                                  count: localizedNumber(
                                      collection.installment.days_overdue,
                                      locale,
                                  ),
                              })
                            : t('rent_collection.not_overdue')}
                    </small>
                </div>
                <div>
                    <dt>{t('rent_collection.amount_paid')}</dt>
                    <dd>
                        {currency(
                            collection.installment.amount_paid,
                            locale,
                            collection.installment.currency,
                        )}
                    </dd>
                    <small>
                        {t('rent_collection.amount_due_value', undefined, {
                            amount: currency(
                                collection.installment.amount_due,
                                locale,
                                collection.installment.currency,
                            ),
                        })}
                    </small>
                </div>
                <div>
                    <dt>{t('rent_collection.follow_up_owner')}</dt>
                    <dd>
                        {collection.latest_follow_up.assigned_to?.name ??
                            t('rent_collection.not_assigned')}
                    </dd>
                    <small>
                        {collection.latest_follow_up.next_follow_up_on
                            ? t('rent_collection.follow_up_on', undefined, {
                                  date: humanDate(
                                      collection.latest_follow_up
                                          .next_follow_up_on,
                                      locale,
                                  ),
                              })
                            : t('rent_collection.follow_up_not_started')}
                    </small>
                </div>
            </dl>
        </section>
    );
}

function localizedAsset(
    asset: CollectionFollowUpPageData['asset'],
    locale: string,
): string | null {
    if (!asset) {
        return null;
    }

    return locale === 'ar'
        ? asset.title_ar || asset.title_en
        : asset.title_en || asset.title_ar || null;
}

function nextAction(
    collection: CollectionFollowUpPageData,
    t: ReturnType<typeof useTranslator>['t'],
    locale: string,
): string {
    const followUp = collection.latest_follow_up;

    if (!collection.can_record) {
        return t('rent_collection.next_action_settled');
    }

    if (followUp.state === 'broken') {
        return t('rent_collection.next_action_broken');
    }

    if (followUp.state === 'due') {
        return t('rent_collection.next_action_due');
    }

    if (followUp.state === 'promised' && followUp.promised_on) {
        return t('rent_collection.next_action_promised', undefined, {
            date: humanDate(followUp.promised_on, locale),
        });
    }

    if (followUp.state === 'scheduled' && followUp.next_follow_up_on) {
        return t('rent_collection.next_action_scheduled', undefined, {
            date: humanDate(followUp.next_follow_up_on, locale),
        });
    }

    return t('rent_collection.next_action_untracked');
}

function stateTone(
    state: CollectionFollowUpRecordState,
): 'success' | 'warning' | 'danger' | 'neutral' | 'blue' {
    if (state === 'settled') {
        return 'success';
    }

    if (state === 'broken' || state === 'due') {
        return 'danger';
    }

    if (state === 'promised') {
        return 'blue';
    }

    return state === 'untracked' ? 'warning' : 'neutral';
}

type CollectionFollowUpRecordState = NonNullable<
    CollectionFollowUpPageData['latest_follow_up']['state']
>;
