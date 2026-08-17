import type { ResourceField } from '@/components/resource-cycle';
import { fieldError } from '@/components/resource-cycle/resource-form-helpers';
import { ResourceInput } from '@/components/resource-cycle/resource-input';
import type { ResourceFormValue } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

import { ExpenseChoiceField } from './expense-choice-field';

type ExpenseFormSectionProps = {
    number: number;
    title: string;
    description?: string;
    fields: ResourceField[];
    values: Record<string, ResourceFormValue>;
    errors: Partial<Record<string, string>>;
    onChange: (field: ResourceField, value: ResourceFormValue) => void;
};

export function ExpenseFormSection({
    number,
    title,
    description,
    fields,
    values,
    errors,
    onChange,
}: ExpenseFormSectionProps) {
    const { t, text } = useTranslator();
    const optional = fields.every(
        (field) => !field.required && field.type !== 'hidden',
    );
    const hasValue = fields.some((field) => {
        const value = values[field.name];

        return value !== null && value !== undefined && value !== '';
    });
    const fieldGrid = (
        <ExpenseFields
            fields={fields}
            values={values}
            errors={errors}
            onChange={onChange}
        />
    );

    if (optional) {
        return (
            <details className="pmc-expense-form-optional" open={hasValue}>
                <summary>
                    <span aria-hidden="true">{number}</span>
                    <div>
                        <strong>{text(title)}</strong>
                        {description ? (
                            <small>{text(description)}</small>
                        ) : null}
                    </div>
                    <em>{t('expenses.optional_section_badge')}</em>
                    <i className="bi bi-chevron-down" aria-hidden="true" />
                </summary>
                {fieldGrid}
            </details>
        );
    }

    return (
        <fieldset className="pmc-expense-form-section">
            <legend className="visually-hidden">{text(title)}</legend>
            <header>
                <span aria-hidden="true">{number}</span>
                <div>
                    <h2>{text(title)}</h2>
                    {description ? <p>{text(description)}</p> : null}
                </div>
            </header>
            {fieldGrid}
        </fieldset>
    );
}

function ExpenseFields({
    fields,
    values,
    errors,
    onChange,
}: Pick<ExpenseFormSectionProps, 'fields' | 'values' | 'errors' | 'onChange'>) {
    return (
        <div className="pmc-expense-field-grid">
            {fields.map((field) =>
                ['asset_id', 'maintenance_request_id'].includes(field.name) ? (
                    <ExpenseChoiceField
                        key={field.name}
                        field={field}
                        value={values[field.name]}
                        error={fieldError(errors, field.name)}
                        onChange={(value) => onChange(field, value)}
                    />
                ) : (
                    <ResourceInput
                        key={field.name}
                        field={field}
                        value={values[field.name]}
                        error={fieldError(errors, field.name)}
                        onChange={(value) => onChange(field, value)}
                    />
                ),
            )}
        </div>
    );
}
