import type { ResourceField } from '@/components/resource-cycle';
import { isRequiredFieldComplete } from '@/components/resource-cycle/resource-form-helpers';
import type { ResourceFormValues } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

import { LeaseFormSummary } from './lease-form-summary';

export function LeaseFormGuide({
    fields,
    values,
}: {
    fields: ResourceField[];
    values: ResourceFormValues;
}) {
    const { t, text } = useTranslator();
    const required = fields.filter((field) => field.required);
    const complete = required.filter((field) =>
        leaseFieldComplete(field, values),
    );
    const next = required.find((field) => !leaseFieldComplete(field, values));
    const draft = values.status === 'draft';

    return (
        <aside className="pmc-lease-form-guide">
            <div className="pmc-lease-guide-heading">
                <i className="bi bi-file-earmark-check" aria-hidden="true" />
                <div>
                    <span>{t('leases.form_eyebrow')}</span>
                    <h2>{t('leases.form_checklist')}</h2>
                </div>
            </div>
            <p>{t('leases.form_checklist_help')}</p>
            <div className="pmc-lease-guide-progress" aria-live="polite">
                <div>
                    <strong>
                        {t('leases.required_complete', undefined, {
                            complete: complete.length,
                            total: required.length,
                        })}
                    </strong>
                    <span>
                        {required.length
                            ? Math.round(
                                  (complete.length / required.length) * 100,
                              )
                            : 100}
                        %
                    </span>
                </div>
                <progress value={complete.length} max={required.length || 1} />
            </div>
            <div className="pmc-lease-guide-next">
                <i
                    className={`bi ${next ? 'bi-arrow-right-circle' : 'bi-check2-circle'}`}
                    aria-hidden="true"
                />
                <span>
                    {next
                        ? t('leases.next_required', undefined, {
                              field: text(next.label),
                          })
                        : t('leases.ready_to_save')}
                </span>
            </div>
            <div className={`pmc-lease-status-note${draft ? 'is-draft' : ''}`}>
                <i
                    className={`bi ${draft ? 'bi-pencil-square' : 'bi-check2-circle'}`}
                    aria-hidden="true"
                />
                <span>
                    {t(
                        draft
                            ? 'leases.draft_description'
                            : 'leases.active_description',
                    )}
                </span>
            </div>
            <div className="pmc-lease-summary-heading">
                <span>{t('leases.summary_title')}</span>
            </div>
            <LeaseFormSummary fields={fields} values={values} />
        </aside>
    );
}

function leaseFieldComplete(
    field: ResourceField,
    values: ResourceFormValues,
): boolean {
    if (!isRequiredFieldComplete(field, values[field.name])) {
        return false;
    }

    if (field.name !== 'ends_at') {
        return true;
    }

    const start = Date.parse(String(values.started_at ?? ''));
    const end = Date.parse(String(values.ends_at ?? ''));

    return Number.isFinite(start) && Number.isFinite(end) && end > start;
}
