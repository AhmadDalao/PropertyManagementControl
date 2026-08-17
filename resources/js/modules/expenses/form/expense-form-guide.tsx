import type { ResourceField } from '@/components/resource-cycle';
import { isRequiredFieldComplete } from '@/components/resource-cycle/resource-form-helpers';
import type {
    ResourceFormValue,
    ResourceFormValues,
} from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { ExpenseFormPage } from '../types';

type ExpenseFormGuideProps = {
    mode: ExpenseFormPage['mode'];
    context: ExpenseFormPage['context'];
    fields: ResourceField[];
    values: ResourceFormValues;
};

export function ExpenseFormGuide({
    mode,
    context: formContext,
    fields,
    values,
}: ExpenseFormGuideProps) {
    const { locale, t, text } = useTranslator();
    const required = fields.filter(
        (field) => field.required && field.type !== 'hidden',
    );
    const checks = required.map((field) => ({
        complete: isRequiredFieldComplete(field, values[field.name]),
        label: text(field.label),
    }));
    const completed = checks.filter((check) => check.complete).length;
    const next = checks.find((check) => !check.complete);
    const status = String(values.status ?? 'pending');
    const amount = Number(values.amount ?? 0);
    const currencyCode = String(values.currency || 'SAR');
    const amountLabel =
        Number.isFinite(amount) && amount > 0
            ? currency(amount, locale, currencyCode)
            : t('expenses.amount_not_entered');
    const contextItems = [
        formContext.portfolio
            ? { label: t('expenses.portfolio'), value: formContext.portfolio }
            : summaryItem(fields, values, 'portfolio_id', text),
        summaryItem(fields, values, 'asset_id', text),
        summaryItem(fields, values, 'maintenance_request_id', text),
        summaryItem(fields, values, 'category', text),
    ].filter((item): item is { label: string; value: string } => item !== null);
    const workOrder = String(
        formContext.workOrderId ?? values.maintenance_work_order_id ?? '',
    );

    return (
        <aside
            className="pmc-expense-form-guide"
            aria-label={t('expenses.form_review')}
        >
            <section className="pmc-expense-posting-preview">
                <span className="pmc-expense-guide-eyebrow">
                    {t('expenses.posting_preview')}
                </span>
                <strong aria-live="polite">{amountLabel}</strong>
                <div className={`pmc-expense-posting-state is-${status}`}>
                    <i
                        className={
                            status === 'posted'
                                ? 'bi bi-graph-down-arrow'
                                : 'bi bi-shield-check'
                        }
                        aria-hidden="true"
                    />
                    <div>
                        <b>
                            {t(
                                status === 'posted'
                                    ? 'expenses.posting_posted'
                                    : 'expenses.posting_pending',
                            )}
                        </b>
                        <p>
                            {t(
                                status === 'posted'
                                    ? 'expenses.posting_posted_help'
                                    : 'expenses.posting_pending_help',
                            )}
                        </p>
                    </div>
                </div>
            </section>

            <section className="pmc-expense-guide-section pmc-expense-context-summary">
                <header>
                    <span>{t('expenses.scope_summary')}</span>
                    <small>
                        {mode === 'edit'
                            ? t('expenses.editing_record')
                            : t('expenses.new_record')}
                    </small>
                </header>
                <dl>
                    {contextItems.map((item) => (
                        <div key={item.label}>
                            <dt>{item.label}</dt>
                            <dd>{item.value}</dd>
                        </div>
                    ))}
                    {workOrder ? (
                        <div>
                            <dt>{t('expenses.maintenance_work_order')}</dt>
                            <dd>
                                {t('expenses.work_order_value', undefined, {
                                    id: workOrder,
                                })}
                            </dd>
                        </div>
                    ) : null}
                </dl>
            </section>

            <section className="pmc-expense-guide-section pmc-expense-checklist">
                <header>
                    <span>{t('expenses.form_checklist')}</span>
                    <small>
                        {t('expenses.checklist_progress', undefined, {
                            complete: completed,
                            total: checks.length,
                        })}
                    </small>
                </header>
                <ul>
                    {checks.map((check) => (
                        <li
                            className={check.complete ? 'is-complete' : ''}
                            key={check.label}
                        >
                            <i
                                className={
                                    check.complete
                                        ? 'bi bi-check-circle-fill'
                                        : 'bi bi-circle'
                                }
                                aria-hidden="true"
                            />
                            {check.label}
                        </li>
                    ))}
                </ul>
                <p aria-live="polite">
                    {next
                        ? t('expenses.next_required', undefined, {
                              field: next.label,
                          })
                        : t('expenses.required_complete')}
                </p>
            </section>

            <section className="pmc-expense-evidence-note">
                <i className="bi bi-file-earmark-pdf" aria-hidden="true" />
                <div>
                    <strong>{t('expenses.evidence_after_save')}</strong>
                    <p>{t('expenses.evidence_after_save_help')}</p>
                </div>
                <span>PDF</span>
            </section>
        </aside>
    );
}

function summaryItem(
    fields: ResourceField[],
    values: ResourceFormValues,
    name: string,
    text: (value: string) => string,
): { label: string; value: string } | null {
    const field = fields.find((candidate) => candidate.name === name);
    const value = normalizeValue(values[name]);

    if (!field || !value) {
        return null;
    }

    const option = field.options?.find(
        (candidate) => String(candidate.value) === value,
    );

    return option
        ? { label: text(field.label), value: text(option.label) }
        : null;
}

function normalizeValue(value: ResourceFormValue): string {
    return typeof value === 'string' || typeof value === 'number'
        ? String(value)
        : '';
}
