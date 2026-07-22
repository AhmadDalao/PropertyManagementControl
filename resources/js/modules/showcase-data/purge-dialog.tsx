import { useForm } from '@inertiajs/react';
import { useEffect, useEffectEvent, useRef } from 'react';
import type { FormEvent, KeyboardEvent as ReactKeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { ShowcaseDataset } from './types';

export function PurgeDialog({
    dataset,
    onClose,
}: {
    dataset: ShowcaseDataset;
    onClose: () => void;
}) {
    const { t } = useTranslator();
    const form = useForm({ confirmation: '' });
    const inputRef = useRef<HTMLInputElement>(null);
    const dialogRef = useRef<HTMLFormElement>(null);
    const closeDialog = useEffectEvent(onClose);
    const required = t('showcase.confirmation');
    const titleId = `showcase-purge-title-${dataset.id}`;

    useEffect(() => {
        const previousFocus = document.activeElement as HTMLElement | null;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        inputRef.current?.focus();

        const escape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeDialog();
            }
        };

        document.addEventListener('keydown', escape);

        return () => {
            document.removeEventListener('keydown', escape);
            document.body.style.overflow = previousOverflow;
            previousFocus?.focus();
        };
    }, []);

    const trapFocus = (event: ReactKeyboardEvent<HTMLFormElement>) => {
        if (event.key !== 'Tab') {
            return;
        }

        const focusable = Array.from(
            dialogRef.current?.querySelectorAll<HTMLElement>(
                'button:not([disabled]), input:not([disabled])',
            ) ?? [],
        );
        const first = focusable.at(0);
        const last = focusable.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.delete(`/system/showcase-data/${dataset.id}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div className="pmc-showcase-dialog-layer">
            <div
                className="pmc-showcase-dialog-backdrop"
                aria-hidden="true"
                onClick={onClose}
            />
            <form
                ref={dialogRef}
                className="pmc-showcase-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                onKeyDown={trapFocus}
                onSubmit={submit}
            >
                <header>
                    <div>
                        <span>{dataset.key}</span>
                        <h2 id={titleId}>{t('showcase.purge_title')}</h2>
                    </div>
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary"
                        aria-label={t('actions.close')}
                        onClick={onClose}
                    >
                        <i className="bi bi-x-lg" aria-hidden="true" />
                    </button>
                </header>
                <p>
                    {t('showcase.purge_description', undefined, {
                        confirmation: required,
                    })}
                </p>
                <label htmlFor={`showcase-confirmation-${dataset.id}`}>
                    <span>{t('showcase.confirmation_label')}</span>
                    <input
                        ref={inputRef}
                        id={`showcase-confirmation-${dataset.id}`}
                        type="text"
                        className="form-control"
                        value={form.data.confirmation}
                        autoComplete="off"
                        onChange={(event) =>
                            form.setData(
                                'confirmation',
                                event.currentTarget.value,
                            )
                        }
                    />
                </label>
                {form.errors.confirmation ? (
                    <small className="text-danger" role="alert">
                        {form.errors.confirmation}
                    </small>
                ) : null}
                <footer>
                    <button
                        type="button"
                        className="btn btn-outline-secondary"
                        onClick={onClose}
                    >
                        {t('actions.cancel')}
                    </button>
                    <button
                        type="submit"
                        className="btn btn-danger"
                        disabled={
                            form.processing ||
                            form.data.confirmation.trim() !== required
                        }
                    >
                        {t('showcase.purge')}
                    </button>
                </footer>
            </form>
        </div>
    );
}
