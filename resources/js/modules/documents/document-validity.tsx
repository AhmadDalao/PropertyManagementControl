import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import type { DocumentRecord } from './types';

export function DocumentValidity({ document }: { document: DocumentRecord }) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <StatusBadge
                value={document.expiry_status}
                label={t(`documents.expiry_${document.expiry_status}`)}
                tone={expiryTone(document.expiry_status)}
            />
            <span>
                {document.expires_on
                    ? `${humanDate(document.expires_on, locale)} · ${expiryDays(document, t)}`
                    : t('documents.expiry_no_expiry')}
            </span>
        </div>
    );
}

function expiryTone(
    status: DocumentRecord['expiry_status'],
): 'success' | 'warning' | 'danger' | 'neutral' {
    if (status === 'expired') {
        return 'danger';
    }

    if (status === 'due_30' || status === 'due_90') {
        return 'warning';
    }

    return status === 'current' ? 'success' : 'neutral';
}

function expiryDays(
    document: DocumentRecord,
    translate: ReturnType<typeof useTranslator>['t'],
): string {
    const days = document.expiry_days ?? 0;

    return days < 0
        ? translate('documents.days_expired', undefined, {
              count: Math.abs(days),
          })
        : translate('documents.days_remaining', undefined, { count: days });
}
