import { useForm } from '@inertiajs/react';
import { useEffect, useEffectEvent, useRef } from 'react';
import type { FormEvent, KeyboardEvent as ReactKeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { ReadinessConfirmation } from './types';

export function ConfirmationDialog({
    check,
    onClose,
}: {
    check: ReadinessConfirmation;
    onClose: () => void;
}) {
    const { t } = useTranslator();
    const form = useForm({
        key: check.key,
        confirmed: true,
        evidence: '',
        portfolio_id: check.portfolio_id,
    });
    const inputRef = useRef<HTMLTextAreaElement>(null);
    const dialogRef = useRef<HTMLFormElement>(null);
    const closeDialog = useEffectEvent(onClose);
    const titleId = `readiness-confirm-${check.key}`;

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
                'button:not([disabled]), textarea:not([disabled])',
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
        form.put('/system/readiness/checks', {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div className="pmc-readiness-dialog-layer">
            <div
                className="pmc-readiness-dialog-backdrop"
                aria-hidden="true"
                onClick={onClose}
            />
            <form
                ref={dialogRef}
                className="pmc-readiness-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                onKeyDown={trapFocus}
                onSubmit={submit}
            >
                <header>
                    <div>
                        <span>{t('readiness.evidence_check')}</span>
                        <h2 id={titleId}>{check.label}</h2>
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
                <p>{check.description}</p>
                <label htmlFor={`readiness-evidence-${check.key}`}>
                    <span>{t('readiness.evidence')}</span>
                    <textarea
                        ref={inputRef}
                        id={`readiness-evidence-${check.key}`}
                        className="form-control"
                        rows={5}
                        value={form.data.evidence}
                        placeholder={t('readiness.evidence_placeholder')}
                        onChange={(event) =>
                            form.setData('evidence', event.currentTarget.value)
                        }
                    />
                </label>
                {form.errors.evidence ? (
                    <small className="text-danger" role="alert">
                        {form.errors.evidence}
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
                        className="btn btn-primary"
                        disabled={
                            form.processing ||
                            form.data.evidence.trim().length < 3
                        }
                    >
                        {t('readiness.confirm_check')}
                    </button>
                </footer>
            </form>
        </div>
    );
}
