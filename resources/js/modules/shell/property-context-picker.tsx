import { useEffect, useEffectEvent, useRef } from 'react';
import type { ChangeEvent } from 'react';
import { createPortal } from 'react-dom';

import '../../../css/styles/shell/property-context-picker.css';
import '../../../css/styles/shell/property-context-results.css';

import { useTranslator } from '@/lib/i18n';
import type { PropertyContext } from '@/types';

import { groupPropertyOptions } from './property-context-options';
import { PropertyContextResults } from './property-context-results';

type PropertyContextPickerProps = {
    context: PropertyContext;
    open: boolean;
    search: string;
    updating: boolean;
    allowAll?: boolean;
    onSearch: (value: string) => void;
    onSelect: (propertyId: string) => void;
    onClose: () => void;
};

export function PropertyContextPicker({
    context,
    open,
    search,
    updating,
    allowAll = true,
    onSearch,
    onSelect,
    onClose,
}: PropertyContextPickerProps) {
    const { locale, t } = useTranslator();
    const dialogRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    const closePicker = useEffectEvent(onClose);
    const groups = groupPropertyOptions(context.options, locale, search);
    const resultCount = groups.reduce(
        (total, group) => total + group.options.length,
        0,
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        document.body.classList.add('pmc-property-picker-open');
        searchRef.current?.focus();

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                closePicker();

                return;
            }

            if (event.key !== 'Tab' || !dialogRef.current) {
                return;
            }

            const focusable = [
                ...dialogRef.current.querySelectorAll<HTMLElement>(
                    'button:not([disabled]), input:not([disabled]), [href], [tabindex]:not([tabindex="-1"])',
                ),
            ].filter((element) => element.offsetParent !== null);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown, true);

        return () => {
            document.body.classList.remove('pmc-property-picker-open');
            document.removeEventListener('keydown', handleKeyDown, true);
        };
    }, [open]);

    if (!open || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <>
            <button
                type="button"
                className="pmc-property-picker-backdrop"
                aria-label={t('shell.close_property_picker')}
                onClick={onClose}
            />
            <div
                ref={dialogRef}
                className="pmc-property-picker"
                role="dialog"
                aria-modal="true"
                aria-labelledby="property-picker-title"
                data-property-scope-dialog
            >
                <header>
                    <div>
                        <span>{t('shell.property_scope')}</span>
                        <strong id="property-picker-title">
                            {t('shell.choose_property')}
                        </strong>
                    </div>
                    <button
                        type="button"
                        aria-label={t('shell.close_property_picker')}
                        onClick={onClose}
                    >
                        <i className="bi bi-x-lg" aria-hidden="true" />
                    </button>
                </header>

                <label className="pmc-property-picker-search">
                    <span className="visually-hidden">
                        {t('shell.search_properties')}
                    </span>
                    <i className="bi bi-search" aria-hidden="true" />
                    <input
                        ref={searchRef}
                        type="search"
                        value={search}
                        placeholder={t('shell.search_properties')}
                        data-property-scope-search
                        onChange={(event: ChangeEvent<HTMLInputElement>) =>
                            onSearch(event.currentTarget.value)
                        }
                    />
                </label>

                <div className="pmc-property-picker-summary">
                    <span>
                        {t('shell.available_properties', undefined, {
                            count: resultCount,
                        })}
                    </span>
                    {search ? (
                        <button type="button" onClick={() => onSearch('')}>
                            {t('actions.clear')}
                        </button>
                    ) : null}
                </div>

                <PropertyContextResults
                    context={context}
                    groups={groups}
                    resultCount={resultCount}
                    updating={updating}
                    allowAll={allowAll}
                    onSelect={onSelect}
                />
            </div>
        </>,
        document.body,
    );
}
