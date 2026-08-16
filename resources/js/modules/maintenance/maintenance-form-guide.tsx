import type { ResourceField } from '@/components/resource-cycle';
import type {
    ResourceFormValue,
    ResourceFormValues,
} from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

export function MaintenanceFormGuide({
    fields,
    values,
}: {
    fields: ResourceField[];
    values: ResourceFormValues;
}) {
    const { t, text } = useTranslator();
    const required = fields.filter((field) => field.required);
    const complete = required.filter((field) => hasValue(values[field.name]));
    const next = required.find((field) => !hasValue(values[field.name]));

    return (
        <aside className="pmc-maintenance-form-guide">
            <div className="pmc-maintenance-guide-icon" aria-hidden="true">
                <i className="bi bi-clipboard2-check" />
            </div>
            <span>{t('maintenance.request_form')}</span>
            <h2>{t('maintenance.request_checklist')}</h2>
            <p>{t('maintenance.request_checklist_help')}</p>
            <div className="pmc-maintenance-guide-progress">
                <div>
                    <strong>
                        {t('maintenance.required_complete', undefined, {
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
            <div className="pmc-maintenance-guide-next">
                <i
                    className={`bi ${next ? 'bi-arrow-right-circle' : 'bi-check2-circle'}`}
                    aria-hidden="true"
                />
                <span>
                    {next
                        ? t('maintenance.next_required', undefined, {
                              field: text(next.label),
                          })
                        : t('maintenance.ready_to_submit')}
                </span>
            </div>
        </aside>
    );
}

function hasValue(value: ResourceFormValue): boolean {
    if (Array.isArray(value)) {
        return value.length > 0;
    }

    if (typeof File !== 'undefined' && value instanceof File) {
        return value.size > 0;
    }

    return value !== null && value !== undefined && value !== '';
}
