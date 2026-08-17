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
    const { text } = useTranslator();

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
            <div className="pmc-expense-field-grid">
                {fields.map((field) =>
                    ['asset_id', 'maintenance_request_id'].includes(
                        field.name,
                    ) ? (
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
        </fieldset>
    );
}
