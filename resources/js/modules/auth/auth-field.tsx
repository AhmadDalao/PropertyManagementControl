export function AuthField({
    id,
    name,
    label,
    type,
    autoComplete,
    value,
    error,
    onChange,
}: {
    id: string;
    name: string;
    label: string;
    type: 'email' | 'password';
    autoComplete: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
}) {
    const errorId = `${id}-error`;

    return (
        <div>
            <label className="form-label pmc-form-label" htmlFor={id}>
                {label}
            </label>
            <input
                id={id}
                name={name}
                type={type}
                autoComplete={autoComplete}
                className="form-control form-control-lg"
                required
                aria-invalid={Boolean(error)}
                aria-describedby={error ? errorId : undefined}
                value={value}
                onChange={(event) => onChange(event.currentTarget.value)}
            />
            {error ? (
                <div
                    id={errorId}
                    className="text-danger small mt-1"
                    role="alert"
                >
                    {error}
                </div>
            ) : null}
        </div>
    );
}
