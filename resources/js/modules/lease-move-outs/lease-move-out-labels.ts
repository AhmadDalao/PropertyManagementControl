import type { Translator, UiTranslationKey } from '@/lib/i18n';

import type { LeaseMoveOutRecord } from './types';

export type MoveOutTone = 'success' | 'warning' | 'danger' | 'neutral' | 'blue';

export function localizedMoveOutRecord(
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

export function moveOutStateLabel(
    moveOut: LeaseMoveOutRecord,
    t: Translator,
): string {
    return t(`lease_move_outs.state_${moveOut.state}` as UiTranslationKey);
}

export function moveOutStateTone(state: string): MoveOutTone {
    if (state === 'completed' || state === 'ready') {
        return 'success';
    }

    if (state === 'overdue' || state === 'cancelled') {
        return 'danger';
    }

    if (state === 'due_today' || state === 'scheduled') {
        return 'warning';
    }

    return 'neutral';
}

export function requirementCount(moveOut: LeaseMoveOutRecord): number {
    return [
        moveOut.notice_uploaded,
        moveOut.inspection_uploaded,
        moveOut.keys_returned,
        moveOut.deposit_disposition !== 'pending',
    ].filter(Boolean).length;
}
