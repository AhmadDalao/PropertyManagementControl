import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/system-readiness.css';
import { AutomaticCheckGrid } from './automatic-check-grid';
import { ConfirmationDialog } from './confirmation-dialog';
import { EvidenceCheckGrid } from './evidence-check-grid';
import { PortfolioReadinessPanel } from './portfolio-readiness-panel';
import { ReadinessHeader } from './readiness-header';
import { ReadinessSummary } from './readiness-summary';
import type { ReadinessConfirmation, SystemReadinessPageProps } from './types';

export default function SystemReadinessIndexPage() {
    const { props } = usePage<SystemReadinessPageProps>();
    const { t } = useTranslator();
    const [activeCheck, setActiveCheck] =
        useState<ReadinessConfirmation | null>(null);
    const [mailBusy, setMailBusy] = useState(false);

    const reset = (check: ReadinessConfirmation) => {
        router.put(
            '/system/readiness/checks',
            {
                key: check.key,
                confirmed: false,
                evidence: null,
                portfolio_id: check.portfolio_id,
            },
            { preserveScroll: true },
        );
    };

    const testEmail = () => {
        setMailBusy(true);
        router.post(
            '/system/readiness/test-email',
            {},
            {
                preserveScroll: true,
                onFinish: () => setMailBusy(false),
            },
        );
    };

    const evidenceGrid = (
        checks: ReadinessConfirmation[],
        includeMail = false,
    ) => (
        <EvidenceCheckGrid
            checks={checks}
            mailTarget={includeMail ? props.mailTest.target : undefined}
            mailEnabled={includeMail ? props.mailTest.enabled : undefined}
            mailBusy={includeMail ? mailBusy : undefined}
            onConfirm={setActiveCheck}
            onReset={reset}
            onTestEmail={includeMail ? testEmail : undefined}
        />
    );

    return (
        <AdminLayout>
            <Head title={t('readiness.title')} />
            <ReadinessHeader
                portfolioId={props.portfolioReadiness?.portfolio.id}
            />
            <ReadinessSummary
                summary={props.summary}
                checkedAt={props.checkedAt}
            />

            <section className="pmc-readiness-section">
                <div className="pmc-readiness-section-heading">
                    <span>{t('readiness.automatic_scope')}</span>
                    <h2>{t('readiness.infrastructure_title')}</h2>
                    <p>{t('readiness.infrastructure_description')}</p>
                </div>
                <AutomaticCheckGrid checks={props.systemChecks} />
            </section>

            <section className="pmc-readiness-section">
                <div className="pmc-readiness-section-heading">
                    <span>{t('readiness.evidence_scope')}</span>
                    <h2>{t('readiness.recovery_title')}</h2>
                    <p>{t('readiness.recovery_description')}</p>
                </div>
                {evidenceGrid(props.systemConfirmations, true)}
            </section>

            <PortfolioReadinessPanel
                options={props.portfolioOptions}
                readiness={props.portfolioReadiness}
                confirmations={props.portfolioConfirmations}
                launch={props.portfolioLaunch}
                renderConfirmations={(checks) => evidenceGrid(checks)}
            />

            {activeCheck ? (
                <ConfirmationDialog
                    check={activeCheck}
                    onClose={() => setActiveCheck(null)}
                />
            ) : null}
        </AdminLayout>
    );
}
