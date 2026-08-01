import { useEffect, useRef, useState } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { PropertyContext } from '@/types';

import { portfolioTitle, propertyLabel } from './property-context-options';
import { PropertyContextPicker } from './property-context-picker';

import '../../../css/styles/shell/property-selection-field.css';

type PropertySelectionFieldProps = {
    label: string;
    context: PropertyContext;
    value: string;
    updating?: boolean;
    allowAll?: boolean;
    testId?: string;
    onChange: (propertyId: string) => void;
};

export function PropertySelectionField({
    label,
    context,
    value,
    updating = false,
    allowAll = true,
    testId,
    onChange,
}: PropertySelectionFieldProps) {
    const { locale, t } = useTranslator();
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const triggerRef = useRef<HTMLButtonElement>(null);
    const restoreFocus = useRef(false);
    const selected =
        context.options.find((property) => String(property.id) === value) ??
        null;
    const pickerContext = { ...context, selected };
    const selectedLabel = selected
        ? propertyLabel(selected, locale)
        : context.assignment_restricted
          ? t('shell.all_assigned_properties')
          : t('shell.all_properties');
    const selectedPortfolio = selected
        ? portfolioTitle(selected, locale)
        : t('shell.available_properties', undefined, {
              count: context.options.length,
          });

    useEffect(() => {
        if (!open && restoreFocus.current) {
            restoreFocus.current = false;
            triggerRef.current?.focus({ preventScroll: true });
        }
    }, [open]);

    const close = () => {
        restoreFocus.current = true;
        setOpen(false);
    };

    const select = (propertyId: string) => {
        if (!allowAll && propertyId === '') {
            return;
        }

        onChange(propertyId);
        close();
    };

    return (
        <div className="pmc-property-selection-field">
            <span>{label}</span>
            <button
                ref={triggerRef}
                type="button"
                className="pmc-property-selection-trigger"
                aria-haspopup="dialog"
                aria-expanded={open}
                aria-label={`${label}: ${selectedLabel}`}
                disabled={updating || context.options.length === 0}
                data-testid={testId}
                onClick={() => {
                    restoreFocus.current = false;
                    setSearch('');
                    setOpen(true);
                }}
            >
                <i className="bi bi-building" aria-hidden="true" />
                <span>
                    <strong>{selectedLabel}</strong>
                    <small>{selectedPortfolio}</small>
                </span>
                {updating ? (
                    <span
                        className="spinner-border spinner-border-sm"
                        aria-label={t('table.loading')}
                    />
                ) : (
                    <i className="bi bi-chevron-down" aria-hidden="true" />
                )}
            </button>
            <PropertyContextPicker
                context={pickerContext}
                open={open}
                search={search}
                updating={updating}
                allowAll={allowAll}
                onSearch={setSearch}
                onSelect={select}
                onClose={close}
            />
        </div>
    );
}
