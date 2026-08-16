import { useState } from 'react';

import { DetailCard } from '@/components/resource-cycle/detail-card';
import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { RelatedRecordsTable } from '@/components/resource-cycle/related-records-table';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';
import { WorkflowActionPanel } from '@/components/resource-cycle/workflow-action-panel';
import { useTranslator } from '@/lib/i18n';

import {
    PaymentDetailTabs,
    paymentTabs,
    requestedPaymentTab,
} from './payment-detail-tabs';
import type { PaymentDetailTab } from './payment-detail-tabs';
import { PaymentEvidencePanel } from './payment-evidence-panel';
import type { PaymentDetail } from './types';

export function PaymentDetailWorkspace({ detail }: { detail: PaymentDetail }) {
    const { t, text } = useTranslator();
    const tabs = paymentTabs(detail);
    const [active, setActive] = useState<PaymentDetailTab>(() =>
        requestedPaymentTab(tabs.map((tab) => tab.key)),
    );

    const selectTab = (tab: PaymentDetailTab) => {
        setActive(tab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    return (
        <>
            <ResourceHeader {...detail.header} />
            <section
                className="pmc-payment-detail-metrics"
                aria-label={t('payments.payment_record')}
            >
                {detail.stats.map((stat) => (
                    <article
                        className={`is-${stat.tone ?? 'muted'}`}
                        key={stat.label}
                    >
                        <span>{text(stat.label)}</span>
                        <strong>{stat.value ?? '-'}</strong>
                    </article>
                ))}
            </section>
            <PaymentDetailTabs
                tabs={tabs}
                active={active}
                onSelect={selectTab}
            />
            <section
                className="pmc-payment-detail-panel"
                role="tabpanel"
                tabIndex={0}
            >
                {active === 'overview' ? (
                    <div className="pmc-payment-overview-grid">
                        <main>
                            {detail.sections.map((section) => (
                                <DetailCard
                                    key={section.title}
                                    section={section}
                                />
                            ))}
                        </main>
                        <aside>
                            <WorkflowActionPanel workflow={detail.workflow} />
                        </aside>
                    </div>
                ) : null}
                {active === 'allocations' ? (
                    detail.related[0] ? (
                        <RelatedRecordsTable table={detail.related[0]} />
                    ) : null
                ) : null}
                {active === 'evidence' ? (
                    <PaymentEvidencePanel
                        evidence={detail.evidence}
                        documents={detail.documents}
                    />
                ) : null}
                {active === 'history' ? (
                    <HistoryTimeline timeline={detail.timeline} />
                ) : null}
            </section>
        </>
    );
}
