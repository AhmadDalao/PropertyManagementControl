import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

export function PaymentProofUpload({ uploadUrl }: { uploadUrl: string }) {
    const { t } = useTranslator();
    const form = useForm<{ proof: File | null; note: string }>({
        proof: null,
        note: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(uploadUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form className="pmc-payment-proof-upload" onSubmit={submit}>
            <header>
                <div className="pmc-payment-evidence-icon">
                    <i className="bi bi-file-earmark-arrow-up" />
                </div>
                <div>
                    <h2>{t('payments.upload_payment_proof')}</h2>
                    <p>{t('payments.upload_proof_help')}</p>
                </div>
            </header>
            {Object.keys(form.errors).length > 0 ? (
                <div className="pmc-payment-proof-errors" role="alert">
                    {Object.values(form.errors).map((error) => (
                        <span key={error}>{error}</span>
                    ))}
                </div>
            ) : null}
            <label>
                <span>{t('payments.payment_proof_pdf')} *</span>
                <input
                    type="file"
                    className="form-control"
                    accept="application/pdf,.pdf"
                    required
                    onChange={(event) =>
                        form.setData(
                            'proof',
                            event.currentTarget.files?.[0] ?? null,
                        )
                    }
                />
                <small>{t('payments.proof_file_help')}</small>
            </label>
            <label>
                <span>{t('payments.submission_note')}</span>
                <textarea
                    className="form-control"
                    rows={3}
                    maxLength={1000}
                    value={form.data.note}
                    onChange={(event) =>
                        form.setData('note', event.currentTarget.value)
                    }
                />
                <small>{t('payments.submission_note_help')}</small>
            </label>
            <button className="btn btn-primary" disabled={form.processing}>
                <i className="bi bi-upload" />
                {t('payments.submit_proof')}
            </button>
        </form>
    );
}
