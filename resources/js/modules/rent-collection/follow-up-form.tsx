import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { CollectionFollowUpPageData } from './types';

type FollowUpFormValues = CollectionFollowUpPageData['initial_values'];

export function FollowUpForm({
    collection,
}: {
    collection: CollectionFollowUpPageData;
}) {
    const { t } = useTranslator();
    const form = useForm<FollowUpFormValues>(collection.initial_values);
    const isPromise = form.data.outcome === 'promise_to_pay';
    const errors = Object.values(form.errors).filter(Boolean);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(collection.links.store, {
            preserveScroll: true,
            onSuccess: () =>
                form.reset('note', 'promised_amount', 'promised_on', 'outcome'),
        });
    };

    if (!collection.can_record) {
        return (
            <section className="pmc-collection-closed">
                <i className="bi bi-check-circle" aria-hidden="true" />
                <div>
                    <strong>{t('rent_collection.installment_settled')}</strong>
                    <p>{t('rent_collection.installment_settled_help')}</p>
                </div>
            </section>
        );
    }

    return (
        <section className="pmc-collection-form-panel">
            <header>
                <span>{t('rent_collection.next_contact')}</span>
                <h2>{t('rent_collection.record_follow_up')}</h2>
                <p>{t('rent_collection.record_follow_up_help')}</p>
            </header>

            <form onSubmit={submit}>
                {errors.length > 0 ? (
                    <div
                        className="pmc-form-error-summary"
                        role="alert"
                        aria-live="assertive"
                    >
                        <i
                            className="bi bi-exclamation-circle"
                            aria-hidden="true"
                        />
                        <div>
                            <strong>{t('resource.validation_title')}</strong>
                            <ul>
                                {errors.map((error) => (
                                    <li key={String(error)}>{String(error)}</li>
                                ))}
                            </ul>
                        </div>
                    </div>
                ) : null}

                <Field label={t('rent_collection.contact_method')} required>
                    <select
                        className="form-select"
                        value={form.data.contact_method}
                        onChange={(event) =>
                            form.setData('contact_method', event.target.value)
                        }
                    >
                        {collection.contact_method_options.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <ErrorText error={form.errors.contact_method} />
                </Field>

                <Field label={t('rent_collection.follow_up_outcome')} required>
                    <select
                        className="form-select"
                        value={form.data.outcome}
                        onChange={(event) =>
                            form.setData('outcome', event.target.value)
                        }
                    >
                        {collection.outcome_options.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <ErrorText error={form.errors.outcome} />
                </Field>

                <Field label={t('rent_collection.contacted_at')} required>
                    <input
                        className="form-control"
                        type="datetime-local"
                        value={form.data.contacted_at}
                        onChange={(event) =>
                            form.setData('contacted_at', event.target.value)
                        }
                    />
                    <ErrorText error={form.errors.contacted_at} />
                </Field>

                <Field label={t('rent_collection.assigned_to')} required>
                    <select
                        className="form-select"
                        value={form.data.assigned_to_user_id}
                        onChange={(event) =>
                            form.setData(
                                'assigned_to_user_id',
                                Number(event.target.value),
                            )
                        }
                    >
                        {collection.assignee_options.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <ErrorText error={form.errors.assigned_to_user_id} />
                </Field>

                <Field label={t('rent_collection.next_follow_up')} required>
                    <input
                        className="form-control"
                        type="date"
                        value={form.data.next_follow_up_on}
                        onChange={(event) =>
                            form.setData(
                                'next_follow_up_on',
                                event.target.value,
                            )
                        }
                    />
                    <ErrorText error={form.errors.next_follow_up_on} />
                </Field>

                {isPromise ? (
                    <>
                        <Field
                            label={t('rent_collection.promised_amount')}
                            required
                        >
                            <input
                                className="form-control"
                                type="number"
                                min="0.01"
                                max={collection.installment.outstanding_amount}
                                step="0.01"
                                value={form.data.promised_amount}
                                onChange={(event) =>
                                    form.setData(
                                        'promised_amount',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <ErrorText error={form.errors.promised_amount} />
                        </Field>
                        <Field
                            label={t('rent_collection.promised_on')}
                            required
                        >
                            <input
                                className="form-control"
                                type="date"
                                value={form.data.promised_on}
                                onChange={(event) =>
                                    form.setData(
                                        'promised_on',
                                        event.target.value,
                                    )
                                }
                            />
                            <ErrorText error={form.errors.promised_on} />
                        </Field>
                    </>
                ) : null}

                <Field
                    label={t('rent_collection.follow_up_note')}
                    required
                    wide
                >
                    <textarea
                        className="form-control"
                        rows={5}
                        value={form.data.note}
                        placeholder={t(
                            'rent_collection.follow_up_note_placeholder',
                        )}
                        onChange={(event) =>
                            form.setData('note', event.target.value)
                        }
                    />
                    <small>{t('rent_collection.follow_up_note_help')}</small>
                    <ErrorText error={form.errors.note} />
                </Field>

                <div className="pmc-collection-form-actions">
                    <button
                        className="btn btn-primary"
                        disabled={form.processing}
                    >
                        <i className="bi bi-check2-circle" aria-hidden="true" />
                        {t('rent_collection.save_follow_up')}
                    </button>
                </div>
            </form>
        </section>
    );
}

function Field({
    label,
    required = false,
    wide = false,
    children,
}: {
    label: string;
    required?: boolean;
    wide?: boolean;
    children: React.ReactNode;
}) {
    return (
        <label className={`pmc-resource-field${wide ? 'is-wide' : ''}`}>
            <span>
                {label}
                {required ? <strong aria-hidden="true">*</strong> : null}
            </span>
            {children}
        </label>
    );
}

function ErrorText({ error }: { error?: string }) {
    return error ? <em>{error}</em> : null;
}
