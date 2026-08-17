import type { ResourceField } from '@/components/resource-cycle';
import { isRequiredFieldComplete } from '@/components/resource-cycle/resource-form-helpers';
import type { ResourceFormValues } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

export function PaymentFormGuide({
    fields,
    values,
}: {
    fields: ResourceField[];
    values: ResourceFormValues;
}) {
    const { t, text } = useTranslator();
    const required = fields.filter((field) => field.required);
    const complete = required.filter((field) =>
        isRequiredFieldComplete(field, values[field.name]),
    );
    const next = required.find(
        (field) => !isRequiredFieldComplete(field, values[field.name]),
    );
    const pending = values.status === 'pending';

    return (
        <aside className="pmc-payment-form-guide">
            <div className="pmc-payment-guide-icon" aria-hidden="true">
                <i className="bi bi-receipt-cutoff" />
            </div>
            <span>{t('payments.payment_record')}</span>
            <h2>{t('payments.form_checklist')}</h2>
            <p>{t('payments.form_checklist_help')}</p>
            <div className="pmc-payment-guide-progress">
                <div>
                    <strong>
                        {t('payments.required_complete', undefined, {
                            complete: complete.length,
                            total: required.length,
                        })}
                    </strong>
                    <span>
                        {required.length > 0
                            ? Math.round(
                                  (complete.length / required.length) * 100,
                              )
                            : 100}
                        %
                    </span>
                </div>
                <progress value={complete.length} max={required.length || 1} />
            </div>
            <div className="pmc-payment-guide-next">
                <i
                    className={`bi ${next ? 'bi-arrow-right-circle' : 'bi-check2-circle'}`}
                    aria-hidden="true"
                />
                <span>
                    {next
                        ? t('payments.next_required', undefined, {
                              field: text(next.label),
                          })
                        : t('payments.ready_to_save')}
                </span>
            </div>
            {values.status !== undefined ? (
                <div
                    className={`pmc-payment-posting-note ${pending ? 'is-pending' : ''}`}
                >
                    <i
                        className={`bi ${pending ? 'bi-hourglass-split' : 'bi-check2-circle'}`}
                    />
                    <span>
                        {pending
                            ? t('payments.workflow_pending_description')
                            : t('payments.workflow_posted_description')}
                    </span>
                </div>
            ) : null}
        </aside>
    );
}
