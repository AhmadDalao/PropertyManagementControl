import { DocumentStrip } from '@/components/resource-cycle/document-strip';
import type { ResourceDocument } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

import { PaymentProofCard } from './payment-proof-card';
import { PaymentProofUpload } from './payment-proof-upload';
import type { PaymentEvidence } from './types';

export function PaymentEvidencePanel({
    evidence,
    documents,
}: {
    evidence: PaymentEvidence;
    documents: ResourceDocument[];
}) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-payment-evidence-layout">
            <main>
                {evidence.can_submit ? (
                    <PaymentProofUpload uploadUrl={evidence.upload_url} />
                ) : evidence.proofs.length > 0 ? (
                    <div className="pmc-payment-proof-locked">
                        <i className="bi bi-shield-check" />
                        <p>{t('payments.proof_locked_help')}</p>
                    </div>
                ) : null}

                <section className="pmc-payment-proof-list">
                    <header>
                        <div>
                            <span>{t('payments.evidence_tab')}</span>
                            <h2>{t('payments.payment_proof')}</h2>
                        </div>
                        <strong>{evidence.proofs.length}</strong>
                    </header>
                    {evidence.proofs.length > 0 ? (
                        evidence.proofs.map((proof) => (
                            <PaymentProofCard
                                key={proof.id}
                                proof={proof}
                                locale={locale}
                            />
                        ))
                    ) : (
                        <div className="pmc-payment-proof-empty">
                            <i className="bi bi-file-earmark-pdf" />
                            <p>{t('payments.no_proofs')}</p>
                        </div>
                    )}
                </section>
                {documents.length > 0 ? (
                    <DocumentStrip documents={documents} />
                ) : null}
            </main>
            <aside>
                <div className="pmc-payment-receipt-card">
                    <div className="pmc-payment-evidence-icon">
                        <i className="bi bi-receipt" />
                    </div>
                    <h2>{t('payments.receipt_is_authoritative')}</h2>
                    <p>{t('payments.receipt_is_authoritative_help')}</p>
                    {evidence.receipt_url ? (
                        <a
                            className="btn btn-primary"
                            href={evidence.receipt_url}
                        >
                            <i className="bi bi-download" />
                            {t('payments.download_receipt')}
                        </a>
                    ) : (
                        <span>{t('payments.workflow_pending_title')}</span>
                    )}
                </div>
            </aside>
        </div>
    );
}
