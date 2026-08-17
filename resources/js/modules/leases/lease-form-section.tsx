import type { ResourceField } from '@/components/resource-cycle';
import { fieldError } from '@/components/resource-cycle/resource-form-helpers';
import { ResourceInput } from '@/components/resource-cycle/resource-input';
import type { ResourceFormValue } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

import { LeaseChoiceField } from './lease-choice-field';

type LeaseFormSectionProps = {
    title: string;
    description?: string;
    fields: ResourceField[];
    values: Record<string, ResourceFormValue>;
    errors: Partial<Record<string, string>>;
    onChange: (field: ResourceField, value: ResourceFormValue) => void;
};

export function LeaseFormSection(props: LeaseFormSectionProps) {
    const { t, text } = useTranslator();
    const optional = props.fields.every(
        (field) => !field.required && field.type !== 'hidden',
    );
    const hasValue = props.fields.some((field) => {
        const value = props.values[field.name];

        return value !== null && value !== undefined && value !== '';
    });
    const fields = <LeaseFields {...props} />;

    if (optional) {
        return (
            <details className="pmc-lease-form-optional" open={hasValue}>
                <summary>
                    <span>
                        <strong>{text(props.title)}</strong>
                        {props.description ? (
                            <small>{text(props.description)}</small>
                        ) : null}
                    </span>
                    <em>{t('leases.optional_section')}</em>
                    <i className="bi bi-chevron-down" aria-hidden="true" />
                </summary>
                {fields}
            </details>
        );
    }

    return (
        <fieldset className="pmc-lease-form-section">
            <legend className="visually-hidden">{text(props.title)}</legend>
            <header>
                <h2>{text(props.title)}</h2>
                {props.description ? <p>{text(props.description)}</p> : null}
            </header>
            {fields}
        </fieldset>
    );
}

function LeaseFields({
    fields,
    values,
    errors,
    onChange,
}: LeaseFormSectionProps) {
    return (
        <div className="pmc-lease-field-grid">
            {fields.map((field) =>
                ['tenant_profile_id', 'asset_id'].includes(field.name) ? (
                    <LeaseChoiceField
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
