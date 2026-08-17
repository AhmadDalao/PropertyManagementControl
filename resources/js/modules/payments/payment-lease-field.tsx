import { useDeferredValue, useState } from 'react';

import type { ResourceField } from '@/components/resource-cycle';
import type { ResourceFormValue } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

const RESULT_LIMIT = 24;

export function PaymentLeaseField({
    field,
    value,
    error,
    onChange,
}: {
    field: ResourceField;
    value: ResourceFormValue;
    error?: string;
    onChange: (value: ResourceFormValue) => void;
}) {
    const { t, text } = useTranslator();
    const [query, setQuery] = useState('');
    const deferredQuery = useDeferredValue(query.trim().toLocaleLowerCase());
    const id = 'pmc-field-lease_id';
    const helpId = field.help ? `${id}-help` : undefined;
    const errorId = error ? `${id}-error` : undefined;
    const describedBy =
        [helpId, errorId].filter(Boolean).join(' ') || undefined;
    const options = field.options ?? [];
    const placeholder = options.find((option) => String(option.value) === '');
    const selectable = options.filter((option) => String(option.value) !== '');
    const selected = selectable.find(
        (option) => String(option.value) === String(value ?? ''),
    );
    const matches = selectable.filter((option) =>
        text(option.label).toLocaleLowerCase().includes(deferredQuery),
    );
    const visible = matches.slice(0, RESULT_LIMIT);

    if (
        selected &&
        !visible.some((option) => option.value === selected.value)
    ) {
        visible.unshift(selected);
    }

    return (
        <div className="pmc-resource-field pmc-payment-lease-field">
            <label className="pmc-payment-lease-label" htmlFor={id}>
                {text(field.label)}
                {field.required ? <strong>*</strong> : null}
            </label>
            {selectable.length > 0 ? (
                <label className="pmc-payment-lease-search">
                    <span className="visually-hidden">
                        {t('payments.search_lease')}
                    </span>
                    <i className="bi bi-search" aria-hidden="true" />
                    <input
                        type="search"
                        value={query}
                        placeholder={t('payments.search_lease_placeholder')}
                        onChange={(event) =>
                            setQuery(event.currentTarget.value)
                        }
                    />
                </label>
            ) : null}
            <select
                id={id}
                name={field.name}
                className="form-select"
                value={String(value ?? '')}
                required={field.required}
                onChange={(event) => onChange(event.currentTarget.value)}
                aria-describedby={describedBy}
                aria-invalid={Boolean(error)}
            >
                {placeholder ? (
                    <option value="">{text(placeholder.label)}</option>
                ) : null}
                {visible.map((option) => (
                    <option
                        key={String(option.value)}
                        value={String(option.value)}
                    >
                        {text(option.label)}
                    </option>
                ))}
            </select>
            <small className="pmc-payment-lease-result" aria-live="polite">
                {selected
                    ? t('payments.selected_lease', undefined, {
                          lease: text(selected.label),
                      })
                    : deferredQuery && matches.length === 0
                      ? t('payments.no_lease_matches')
                      : t('payments.lease_matches', undefined, {
                            shown: Math.min(matches.length, RESULT_LIMIT),
                            total: matches.length,
                        })}
            </small>
            {field.help ? <small id={helpId}>{text(field.help)}</small> : null}
            {error ? <em id={errorId}>{error}</em> : null}
        </div>
    );
}
