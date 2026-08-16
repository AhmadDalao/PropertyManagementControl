import { useTranslator } from '@/lib/i18n';

import type { PaymentDetail } from './types';

export type PaymentDetailTab =
    'overview' | 'allocations' | 'evidence' | 'history';

export function paymentTabs(detail: PaymentDetail) {
    return [
        { key: 'overview' as const, count: null },
        {
            key: 'allocations' as const,
            count: detail.related[0]?.rows.length ?? 0,
        },
        { key: 'evidence' as const, count: detail.evidence.proofs.length },
        ...(detail.timeline.length > 0
            ? [{ key: 'history' as const, count: detail.timeline.length }]
            : []),
    ];
}

export function requestedPaymentTab(
    available: PaymentDetailTab[],
): PaymentDetailTab {
    if (typeof window === 'undefined') {
        return 'overview';
    }

    const requested = new URL(window.location.href).searchParams.get('tab');

    return available.includes(requested as PaymentDetailTab)
        ? (requested as PaymentDetailTab)
        : 'overview';
}

export function PaymentDetailTabs({
    tabs,
    active,
    onSelect,
}: {
    tabs: ReturnType<typeof paymentTabs>;
    active: PaymentDetailTab;
    onSelect: (tab: PaymentDetailTab) => void;
}) {
    const { t } = useTranslator();

    return (
        <div
            className="pmc-payment-detail-tabs"
            role="tablist"
            aria-label={t('payments.detail_navigation')}
        >
            {tabs.map((tab) => (
                <button
                    key={tab.key}
                    type="button"
                    role="tab"
                    aria-selected={active === tab.key}
                    className={active === tab.key ? 'is-active' : ''}
                    onClick={() => onSelect(tab.key)}
                >
                    <span>
                        {t(`payments.${tab.key.replace('-', '_')}_tab`)}
                    </span>
                    {tab.count !== null ? <strong>{tab.count}</strong> : null}
                </button>
            ))}
        </div>
    );
}
