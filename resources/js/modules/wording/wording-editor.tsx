import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import type { FormEvent, KeyboardEvent as ReactKeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { WordingEntry } from './types';

export function WordingEditor({
    entry,
    groupLabel,
    onClose,
}: {
    entry: WordingEntry;
    groupLabel: string;
    onClose: () => void;
}) {
    const { t } = useTranslator();
    const dialogRef = useRef<HTMLElement | null>(null);
    const form = useForm({
        group: entry.group,
        key: entry.key,
        english: entry.english,
        arabic: entry.arabic,
    });
    const tokens = Array.from(
        new Set(
            `${entry.default_english} ${entry.default_arabic}`.match(
                /:[A-Za-z_]+/g,
            ) ?? [],
        ),
    );

    useEffect(() => {
        const previousFocus = document.activeElement as HTMLElement | null;
        const previousOverflow = document.body.style.overflow;
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', closeOnEscape);
        window.requestAnimationFrame(() =>
            dialogRef.current
                ?.querySelector<HTMLTextAreaElement>('textarea')
                ?.focus(),
        );

        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener('keydown', closeOnEscape);
            previousFocus?.focus();
        };
    }, [onClose]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/wording', {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const reset = () => {
        if (!window.confirm(t('wording.reset_confirm'))) {
            return;
        }

        router.delete('/wording', {
            data: { group: entry.group, key: entry.key },
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const trapFocus = (event: ReactKeyboardEvent<HTMLElement>) => {
        if (event.key !== 'Tab') {
            return;
        }

        const focusable = Array.from(
            event.currentTarget.querySelectorAll<HTMLElement>(
                'button:not([disabled]), textarea:not([disabled])',
            ),
        );
        const first = focusable[0];
        const last = focusable.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    };

    return (
        <div className="pmc-wording-editor-layer" role="presentation">
            <button
                type="button"
                className="pmc-wording-editor-backdrop"
                aria-label={t('actions.close')}
                onClick={onClose}
            />
            <aside
                ref={dialogRef}
                className="pmc-wording-editor"
                role="dialog"
                aria-modal="true"
                aria-labelledby="wording-editor-title"
                onKeyDown={trapFocus}
            >
                <header>
                    <div>
                        <span>{groupLabel}</span>
                        <h2 id="wording-editor-title">
                            {t('wording.edit_wording')}
                        </h2>
                        <code>
                            {entry.group}.{entry.key}
                        </code>
                    </div>
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary"
                        onClick={onClose}
                        aria-label={t('actions.close')}
                    >
                        <i className="bi bi-x-lg" />
                    </button>
                </header>
                <form onSubmit={submit}>
                    <p>{t('wording.edit_description')}</p>
                    {tokens.length > 0 ? (
                        <div className="pmc-wording-tokens">
                            {t('wording.required_tokens', undefined, {
                                tokens: tokens.join(', '),
                            })}
                        </div>
                    ) : null}
                    <label>
                        <span>{t('wording.english')}</span>
                        <textarea
                            dir="ltr"
                            rows={5}
                            value={form.data.english}
                            onChange={(event) =>
                                form.setData(
                                    'english',
                                    event.currentTarget.value,
                                )
                            }
                        />
                        {form.errors.english ? (
                            <small className="text-danger">
                                {form.errors.english}
                            </small>
                        ) : null}
                    </label>
                    <label>
                        <span>{t('wording.arabic')}</span>
                        <textarea
                            dir="rtl"
                            rows={5}
                            value={form.data.arabic}
                            onChange={(event) =>
                                form.setData(
                                    'arabic',
                                    event.currentTarget.value,
                                )
                            }
                        />
                        {form.errors.arabic ? (
                            <small className="text-danger">
                                {form.errors.arabic}
                            </small>
                        ) : null}
                    </label>
                    <footer>
                        {entry.customized ? (
                            <button
                                type="button"
                                className="btn btn-outline-secondary"
                                onClick={reset}
                            >
                                {t('wording.reset')}
                            </button>
                        ) : (
                            <span />
                        )}
                        <button
                            type="submit"
                            className="btn btn-primary"
                            disabled={form.processing}
                        >
                            {form.processing
                                ? t('wording.saving')
                                : t('wording.save')}
                        </button>
                    </footer>
                </form>
            </aside>
        </div>
    );
}
