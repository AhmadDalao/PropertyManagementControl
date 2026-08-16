import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import type { PaymentProof } from './types';

export function PaymentProofCard({
    proof,
    locale,
}: {
    proof: PaymentProof;
    locale: string;
}) {
    const { t } = useTranslator();
    const reject = useForm({ status: 'rejected', review_note: '' });

    const accept = () => {
        if (proof.review_url) {
            router.put(
                proof.review_url,
                { status: 'accepted', review_note: '' },
                { preserveScroll: true },
            );
        }
    };

    const rejectProof = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (proof.review_url) {
            reject.put(proof.review_url, { preserveScroll: true });
        }
    };

    return (
        <article className={`pmc-payment-proof-card is-${proof.status}`}>
            <div className="pmc-payment-proof-file">
                <i className="bi bi-file-earmark-pdf" />
                <div>
                    <h3>{proof.title}</h3>
                    <p>
                        {proof.original_name} · {formatBytes(proof.file_size)}
                    </p>
                </div>
                <span>{proof.status_label}</span>
            </div>
            <dl>
                <div>
                    <dt>{t('payments.proof_submitted_by')}</dt>
                    <dd>{proof.submitted_by || '-'}</dd>
                </div>
                <div>
                    <dt>{t('payments.proof_submitted_on')}</dt>
                    <dd>{humanDate(proof.submitted_at, locale)}</dd>
                </div>
            </dl>
            {proof.submission_note ? <p>{proof.submission_note}</p> : null}
            {proof.review_note ? (
                <div className="pmc-payment-review-note">
                    <strong>{t('payments.proof_review_note')}</strong>
                    <span>{proof.review_note}</span>
                </div>
            ) : null}
            <div className="pmc-payment-proof-actions">
                <a className="btn btn-light" href={proof.download_url}>
                    <i className="bi bi-download" />
                    {t('payments.download_proof')}
                </a>
                {proof.review_url ? (
                    <button
                        className="btn btn-primary"
                        type="button"
                        onClick={accept}
                    >
                        <i className="bi bi-check2" />
                        {t('payments.accept_proof')}
                    </button>
                ) : null}
            </div>
            {proof.review_url ? (
                <form
                    className="pmc-payment-reject-form"
                    onSubmit={rejectProof}
                >
                    <label>
                        <span>{t('payments.review_note')}</span>
                        <textarea
                            className="form-control"
                            required
                            maxLength={1000}
                            rows={2}
                            placeholder={t('payments.review_note_placeholder')}
                            value={reject.data.review_note}
                            onChange={(event) =>
                                reject.setData(
                                    'review_note',
                                    event.currentTarget.value,
                                )
                            }
                        />
                    </label>
                    {reject.errors.review_note ? (
                        <em>{reject.errors.review_note}</em>
                    ) : null}
                    <button
                        className="btn btn-outline-danger"
                        disabled={reject.processing}
                    >
                        {t('payments.reject_proof')}
                    </button>
                </form>
            ) : null}
        </article>
    );
}

function formatBytes(value: number): string {
    if (value < 1024) {
        return `${value} B`;
    }

    if (value < 1024 * 1024) {
        return `${(value / 1024).toFixed(1)} KB`;
    }

    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}
