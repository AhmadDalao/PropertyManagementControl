import type { BuildingSetupFieldName, BuildingSetupOption } from './types';

export function SetupField({
    name,
    label,
    value,
    error,
    onChange,
    type = 'text',
    required = false,
    help,
    options,
    min,
    max,
    step,
    rows,
}: {
    name: BuildingSetupFieldName;
    label: string;
    value: string | number;
    error?: string;
    onChange: (value: string | number) => void;
    type?: 'text' | 'number' | 'select' | 'textarea';
    required?: boolean;
    help?: string;
    options?: BuildingSetupOption[];
    min?: number;
    max?: number;
    step?: string;
    rows?: number;
}) {
    const id = `building-setup-${name}`;
    const helpId = help ? `${id}-help` : undefined;
    const errorId = error ? `${id}-error` : undefined;
    const describedBy =
        [helpId, errorId].filter(Boolean).join(' ') || undefined;

    return (
        <label className="pmc-building-setup-field" htmlFor={id}>
            <span>
                {label}
                {required ? <strong>*</strong> : null}
            </span>

            {type === 'select' ? (
                <select
                    id={id}
                    className="form-select"
                    value={String(value)}
                    onChange={(event) => onChange(event.currentTarget.value)}
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                >
                    {(options ?? []).map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            ) : type === 'textarea' ? (
                <textarea
                    id={id}
                    className="form-control"
                    rows={rows ?? 3}
                    value={String(value)}
                    onChange={(event) => onChange(event.currentTarget.value)}
                    aria-describedby={describedBy}
                    aria-invalid={Boolean(error)}
                />
            ) : (
                <input
                    id={id}
                    className="form-control"
                    type={type}
                    value={String(value)}
                    min={min}
                    max={max}
                    step={step}
                    onChange={(event) =>
                        onChange(
                            type === 'number'
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

            {help ? <small id={helpId}>{help}</small> : null}
            {error ? <em id={errorId}>{error}</em> : null}
        </label>
    );
}
