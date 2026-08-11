import { useTranslator } from '@/lib/i18n';

import type {
    ResourceField,
    ResourceFormValue,
    ResourceFormValues,
} from './types';

export function ResourceFormSummary({
    action,
    description,
    fields,
    title,
    values,
}: {
    action: string;
    description: string;
    fields: ResourceField[];
    title: string;
    values: ResourceFormValues;
}) {
    const { t, text } = useTranslator();
    const requiredFields = fields.filter((field) => field.required);
    const completedRequired = requiredFields.filter((field) =>
        hasValue(values[field.name]),
    ).length;
    const summaryFields = fields
        .filter((field) => isSummaryField(field, values[field.name]))
        .slice(0, 5);

    return (
        <aside className="pmc-resource-form-summary">
            <div className="pmc-resource-form-summary-icon">
                <i className={`bi ${resourceIcon(action)}`} />
            </div>
            <span>{t('resource.live_summary', 'Live summary')}</span>
            <h2>{title}</h2>
            <p>{description}</p>
            {requiredFields.length > 0 ? (
                <div className="pmc-resource-form-progress">
                    <div>
                        <strong>
                            {t('resource.required_progress', 'Required fields')}
                        </strong>
                        <span>
                            {completedRequired}/{requiredFields.length}
                        </span>
                    </div>
                    <progress
                        value={completedRequired}
                        max={requiredFields.length}
                    />
                </div>
            ) : null}
            {summaryFields.length > 0 ? (
                <dl>
                    {summaryFields.map((field) => (
                        <div key={field.name}>
                            <dt>{text(field.label)}</dt>
                            <dd>
                                {summaryValue(field, values[field.name], text)}
                            </dd>
                        </div>
                    ))}
                </dl>
            ) : (
                <div className="pmc-resource-form-summary-empty">
                    <i className="bi bi-pencil-square" />
                    <span>
                        {t(
                            'resource.summary_empty',
                            'Your record summary will appear as you complete the form.',
                        )}
                    </span>
                </div>
            )}
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

function isSummaryField(
    field: ResourceField,
    value: ResourceFormValue,
): boolean {
    return (
        hasValue(value) &&
        !['hidden', 'password', 'file', 'textarea', 'checkbox'].includes(
            field.type ?? 'text',
        ) &&
        !field.name.endsWith('_id') &&
        !['currency', 'status'].includes(field.name)
    );
}

function summaryValue(
    field: ResourceField,
    value: ResourceFormValue,
    translate: (value: string) => string,
): string {
    if (field.type === 'select') {
        const selected = field.options?.find(
            (option) => String(option.value) === String(value),
        );

        return selected ? translate(selected.label) : String(value ?? '-');
    }

    return String(value ?? '-');
}

function resourceIcon(action: string): string {
    const icons: Array<[string, string]> = [
        ['tenant', 'bi-person-badge'],
        ['lease', 'bi-file-earmark-text'],
        ['payment', 'bi-cash-stack'],
        ['expense', 'bi-receipt'],
        ['maintenance', 'bi-tools'],
        ['asset', 'bi-buildings'],
    ];

    return (
        icons.find(([resource]) => action.includes(resource))?.[1] ??
        'bi-pencil-square'
    );
}
