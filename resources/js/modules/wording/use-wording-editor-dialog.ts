import { useEffect, useRef } from 'react';
import type { KeyboardEvent as ReactKeyboardEvent } from 'react';

const FOCUSABLE_SELECTOR = 'button:not([disabled]), textarea:not([disabled])';

export function useWordingEditorDialog(onClose: () => void) {
    const dialogRef = useRef<HTMLElement | null>(null);

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

    const trapFocus = (event: ReactKeyboardEvent<HTMLElement>) => {
        if (event.key !== 'Tab') {
            return;
        }

        const focusable = Array.from(
            event.currentTarget.querySelectorAll<HTMLElement>(
                FOCUSABLE_SELECTOR,
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

    return { dialogRef, trapFocus };
}
