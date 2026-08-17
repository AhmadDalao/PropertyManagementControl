import { useDeferredValue, useState } from 'react';

import type { ResourceField } from '@/components/resource-cycle';
import type { ResourceFormValue } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

type ChoiceFieldProps = {
    field: ResourceField;
    value: ResourceFormValue;
    error: string;
    onChange: (value: ResourceFormValue) => void;
};

export function LeaseChoiceField({
    field,
    value,
    error,
    onChange,
}: ChoiceFieldProps) {
    const { t, text } = useTranslator();
    const [query, setQuery] = useState('');
    const deferredQuery = useDeferredValue(query.trim().toLocaleLowerCase());
    const options = field.options ?? [];
    const placeholder = options.find((option) => String(option.value) === '');
    const choices = options.filter((option) => String(option.value) !== '');
    const selected = choices.find(
        (option) => String(option.value) === String(value ?? ''),
    );
    const matches = deferredQuery
        ? choices.filter((option) =>
              text(option.label).toLocaleLowerCase().includes(deferredQuery),
          )
        : choices;
    const visible = matches.slice(0, 24);

    if (selected && !visible.includes(selected)) {
        visible.unshift(selected);
    }

    const tenant = field.name === 'tenant_profile_id';
    const searchLabel = t(
        tenant ? 'leases.search_tenant' : 'leases.search_asset',
    );
    const inputId = `lease-${field.name}`;
    const searchId = `${inputId}-search`;
    const errorId = `${inputId}-error`;

    return (
        <div className="pmc-lease-choice-field">
            <label className="pmc-lease-choice-label" htmlFor={inputId}>
                {text(field.label)}
                {field.required ? <strong aria-hidden="true">*</strong> : null}
            </label>
            {choices.length > 0 ? (
                <label className="pmc-lease-choice-search" htmlFor={searchId}>
                    <span className="visually-hidden">{searchLabel}</span>
                    <i className="bi bi-search" aria-hidden="true" />
                    <input
                        id={searchId}
                        type="search"
                        value={query}
                        placeholder={t(
                            tenant
                                ? 'leases.search_tenant_placeholder'
                                : 'leases.search_asset_placeholder',
                        )}
                        onChange={(event) => setQuery(event.target.value)}
                    />
                </label>
            ) : null}
            <select
                id={inputId}
                name={field.name}
                className={`form-select${error ? 'is-invalid' : ''}`}
                value={String(value ?? '')}
                required={field.required}
                aria-invalid={error ? 'true' : undefined}
                aria-describedby={error ? errorId : undefined}
                onChange={(event) => onChange(event.target.value)}
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
            <small className="pmc-lease-choice-result" aria-live="polite">
                {selected
                    ? t('leases.choice_selected', undefined, {
                          label: text(selected.label),
                      })
                    : matches.length > 0
                      ? t('leases.choice_matches', undefined, {
                            count: matches.length,
                        })
                      : t('leases.choice_no_match')}
            </small>
            {error ? (
                <div id={errorId} className="invalid-feedback d-block">
                    {error}
                </div>
            ) : null}
        </div>
    );
}
