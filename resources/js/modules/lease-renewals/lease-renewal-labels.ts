import type { Translator, UiTranslationKey } from '@/lib/i18n';
import { humanDate, localizedNumber } from '@/lib/utils';

import type { LeaseRenewalRecord } from './types';

export type RenewalStateTone =
    'success' | 'warning' | 'danger' | 'neutral' | 'blue';

export function localizedRenewalRecord(
    record:
        | { title_en?: string | null; title_ar?: string | null }
        | null
        | undefined,
    locale: string,
): string | null {
    if (!record) {
        return null;
    }

    return locale === 'ar'
        ? record.title_ar || record.title_en || null
        : record.title_en || record.title_ar || null;
}

export function renewalStateLabel(
    lease: LeaseRenewalRecord,
    t: Translator,
): string {
    if (lease.renewal) {
        return t('lease_renewals.state_prepared_code', undefined, {
            code: lease.renewal.code,
        });
    }

    return t(`lease_renewals.state_${lease.renewal_state}` as UiTranslationKey);
}

export function renewalStateTone(state: string): RenewalStateTone {
    if (state === 'prepared') {
        return 'success';
    }

    if (state === 'attention' || state === 'expired') {
        return 'danger';
    }

    return state === 'upcoming' ? 'warning' : 'neutral';
}

export function renewalEndTiming(
    lease: LeaseRenewalRecord,
    t: Translator,
    locale: string,
): string {
    const days = lease.days_remaining ?? 0;

    if (days < 0) {
        return t('lease_renewals.ended_days_ago', undefined, {
            count: localizedNumber(Math.abs(days), locale),
        });
    }

    if (days > 0) {
        return t('lease_renewals.ends_in_days', undefined, {
            count: localizedNumber(days, locale),
        });
    }

    return t('lease_renewals.ends_today');
}

export function renewalNoticeLabel(
    lease: LeaseRenewalRecord,
    t: Translator,
    locale: string,
): string {
    if (lease.renewal) {
        return t('lease_renewals.renewal_prepared');
    }

    if (lease.notice_due) {
        return t('lease_renewals.contact_now');
    }

    return t('lease_renewals.contact_by', undefined, {
        date: humanDate(lease.contact_due_on, locale),
    });
}
