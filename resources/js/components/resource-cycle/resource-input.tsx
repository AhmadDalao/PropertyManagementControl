import { useTranslator } from '@/lib/i18n';

import type { ResourceField, ResourceFormValue } from './types';

type ResourceInputProps = {
    field: ResourceField;
    value: ResourceFormValue;
    error?: string;
    onChange: (value: ResourceFormValue) => void;
};

export function ResourceInput({
    field,
    value,
    error,
    onChange,
}: ResourceInputProps) {
    const id = `pmc-field-${field.name.replaceAll(/[^a-zA-Z0-9_-]/g, '-')}`;
    const helpId = field.help ? `${id}-help` : undefined;
    const errorId = error ? `${id}-error` : undefined;
    const describedBy =
        [helpId, errorId].filter(Boolean).join(' ') || undefined;
    const { text } = useTranslator();

    if (field.type === 'hidden') {
        return (
            <input
                type="hidden"
                name={field.name}
                value={String(value ?? '')}
            />
        );
    }

    if (field.type === 'checkbox') {
        return (
            <label className="pmc-resource-check" htmlFor={id}>
                <input
                    id={id}
                    type="checkbox"
                    checked={Boolean(value)}
                    onChange={(event) => onChange(event.currentTarget.checked)}
                />
                <span>
                    <strong>{text(field.label)}</strong>
                    {field.help ? (
                        <small id={helpId}>{text(field.help)}</small>
                    ) : null}
                </span>
                {error ? <em id={errorId}>{error}</em> : null}
            </label>
        );
    }

    return (
        <label className="pmc-resource-field" htmlFor={id}>
            <span>
                {text(field.label)}
                {field.required ? <strong>*</strong> : null}
            </span>
            {field.type === 'textarea' ? (
                <textarea
                    id={id}
                    name={field.name}
                    className="form-control"
                    rows={field.rows ?? 4}
                    value={String(value ?? '')}
                    placeholder={
                        field.placeholder ? text(field.placeholder) : undefined
                    }
                    onChange={(event) => onChange(event.currentTarget.value)}
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                />
            ) : field.type === 'select' ? (
                <select
                    id={id}
                    name={field.name}
                    className="form-select"
                    value={String(value ?? '')}
                    onChange={(event) => onChange(event.currentTarget.value)}
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                >
                    {(field.options ?? []).map((option) => (
                        <option
                            key={String(option.value)}
                            value={String(option.value)}
                        >
                            {text(option.label)}
                        </option>
                    ))}
                </select>
            ) : field.type === 'file' ? (
                <input
                    id={id}
                    name={field.name}
                    className="form-control"
                    type="file"
                    accept={field.accept}
                    onChange={(event) =>
                        onChange(event.currentTarget.files?.[0] ?? null)
                    }
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                />
            ) : (
                <input
                    id={id}
                    name={field.name}
                    className="form-control"
                    type={field.type ?? 'text'}
                    value={String(value ?? '')}
                    placeholder={
                        field.placeholder ? text(field.placeholder) : undefined
                    }
                    step={field.step}
                    min={field.min}
                    max={field.max}
                    onChange={(event) =>
                        onChange(
                            field.type === 'number'
                                ? event.currentTarget.value === ''
                                    ? ''
                                    : Number(event.currentTarget.value)
                                : event.currentTarget.value,
                        )
                    }
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                />
            )}
            {field.help ? <small id={helpId}>{text(field.help)}</small> : null}
            {error ? <em id={errorId}>{error}</em> : null}
        </label>
    );
}
