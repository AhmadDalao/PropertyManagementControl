import type { ResourceField } from '@/components/resource-cycle';
import { fieldError } from '@/components/resource-cycle/resource-form-helpers';
import { ResourceInput } from '@/components/resource-cycle/resource-input';
import type { ResourceFormValue } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

export function MaintenanceFormSection({
    title,
    description,
    fields,
    values,
    errors,
    onChange,
}: {
    title: string;
    description?: string;
    fields: ResourceField[];
    values: Record<string, ResourceFormValue>;
    errors: Partial<Record<string, string>>;
    onChange: (field: ResourceField, value: ResourceFormValue) => void;
}) {
    const { text } = useTranslator();

    return (
        <fieldset className="pmc-maintenance-form-section">
            <legend className="visually-hidden">{text(title)}</legend>
            <header>
                <h2>{text(title)}</h2>
                {description ? <p>{text(description)}</p> : null}
            </header>
            <div className="pmc-maintenance-field-grid">
                {fields.map((field) => (
                    <ResourceInput
                        key={field.name}
                        field={field}
                        value={values[field.name]}
                        error={fieldError(errors, field.name)}
                        onChange={(value) => onChange(field, value)}
                    />
                ))}
            </div>
        </fieldset>
    );
}
