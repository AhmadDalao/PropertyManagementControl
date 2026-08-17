import { Link } from '@inertiajs/react';

import { DocumentStrip } from '@/components/resource-cycle/document-strip';
import { useTranslator } from '@/lib/i18n';

import type { ExpenseEvidence } from './types';

export function ExpenseEvidencePanel({
    evidence,
}: {
    evidence: ExpenseEvidence;
}) {
    const { t } = useTranslator();

    return (
        <div className="pmc-expense-evidence-layout">
            <main>
                {evidence.documents.length > 0 ? (
                    <DocumentStrip documents={evidence.documents} />
                ) : (
                    <article className="pmc-expense-evidence-empty">
                        <i className="bi bi-file-earmark-pdf" />
                        <div>
                            <h2>{t('expenses.evidence_empty')}</h2>
                            <p>{t('expenses.evidence_empty_help')}</p>
                        </div>
                    </article>
                )}
            </main>
            <aside>
                <article className="pmc-expense-evidence-guide">
                    <span>
                        <i className="bi bi-shield-lock" />
                    </span>
                    <h2>{t('expenses.evidence_title')}</h2>
                    <p>{t('expenses.evidence_help')}</p>
                    {evidence.can_upload && evidence.upload_url ? (
                        <Link
                            className="btn btn-primary"
                            href={evidence.upload_url}
                        >
                            <i className="bi bi-upload" />
                            {t('expenses.upload_evidence')}
                        </Link>
                    ) : (
                        <small>{t('expenses.evidence_unavailable')}</small>
                    )}
                </article>
            </aside>
        </div>
    );
}
