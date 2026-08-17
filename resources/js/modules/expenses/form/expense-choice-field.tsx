import { useDeferredValue, useState } from 'react';

import type { ResourceField } from '@/components/resource-cycle';
import type { ResourceFormValue } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

const RESULT_LIMIT = 24;

type ExpenseChoiceFieldProps = {
    field: ResourceField;
    value: ResourceFormValue;
    error: string;
    onChange: (value: ResourceFormValue) => void;
};

export function ExpenseChoiceField({
    field,
    value,
    error,
    onChange,
}: ExpenseChoiceFieldProps) {
    const { t, text } = useTranslator();
    const [query, setQuery] = useState('');
    const deferredQuery = useDeferredValue(query.trim().toLocaleLowerCase());
    const request = field.name === 'maintenance_request_id';
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
    const visible = matches.slice(0, RESULT_LIMIT);

    if (
        selected &&
        !visible.some((option) => option.value === selected.value)
    ) {
        visible.unshift(selected);
    }

    const id = `expense-${field.name}`;
    const searchId = `${id}-search`;
    const helpId = field.help ? `${id}-help` : undefined;
    const errorId = error ? `${id}-error` : undefined;
    const describedBy =
        [helpId, errorId].filter(Boolean).join(' ') || undefined;

    return (
        <div className="pmc-expense-choice-field">
            <label className="pmc-expense-choice-label" htmlFor={id}>
                {text(field.label)}
            </label>
            {choices.length > 0 ? (
                <label className="pmc-expense-choice-search" htmlFor={searchId}>
                    <span className="visually-hidden">
                        {t(
                            request
                                ? 'expenses.search_request'
                                : 'expenses.search_asset',
                        )}
                    </span>
                    <i className="bi bi-search" aria-hidden="true" />
                    <input
                        id={searchId}
                        type="search"
                        value={query}
                        placeholder={t(
                            request
                                ? 'expenses.search_request_placeholder'
                                : 'expenses.search_asset_placeholder',
                        )}
                        onChange={(event) =>
                            setQuery(event.currentTarget.value)
                        }
                    />
                </label>
            ) : null}
            <select
                id={id}
                name={field.name}
                className={`form-select${error ? 'is-invalid' : ''}`}
                value={String(value ?? '')}
                aria-describedby={describedBy}
                aria-invalid={error ? 'true' : undefined}
                onChange={(event) => onChange(event.currentTarget.value)}
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
            <small className="pmc-expense-choice-result" aria-live="polite">
                {selected
                    ? t('expenses.choice_selected', undefined, {
                          label: text(selected.label),
                      })
                    : deferredQuery && matches.length === 0
                      ? t('expenses.choice_no_match')
                      : t('expenses.choice_matches', undefined, {
                            shown: Math.min(matches.length, RESULT_LIMIT),
                            total: matches.length,
                        })}
            </small>
            {field.help ? <small id={helpId}>{text(field.help)}</small> : null}
            {error ? (
                <div id={errorId} className="invalid-feedback d-block">
                    {error}
                </div>
            ) : null}
        </div>
    );
}
