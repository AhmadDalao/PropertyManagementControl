import type { ReactNode } from 'react';

export function ProfileField({
    id,
    label,
    error,
    help,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    help?: string;
    children: ReactNode;
}) {
    return (
        <div className="pmc-profile-field">
            <label htmlFor={id}>{label}</label>
            {children}
            {help ? <small id={`${id}-help`}>{help}</small> : null}
            {error ? (
                <small id={`${id}-error`} className="is-error" role="alert">
                    {error}
                </small>
            ) : null}
        </div>
    );
}

export function describedBy(
    id: string,
    error?: string,
    help?: string,
): string | undefined {
    const descriptions = [
        help ? `${id}-help` : null,
        error ? `${id}-error` : null,
    ].filter(Boolean);

    return descriptions.length > 0 ? descriptions.join(' ') : undefined;
}
