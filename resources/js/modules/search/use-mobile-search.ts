import { useEffect, useRef, useState } from 'react';

export function useMobileSearch() {
    const [open, setOpen] = useState(false);
    const triggerRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        document.body.classList.add('pmc-search-open');

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                close();
            }
        };

        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('keydown', closeOnEscape);
            document.body.classList.remove('pmc-search-open');
        };
    }, [open]);

    function close() {
        setOpen(false);
        window.requestAnimationFrame(() => triggerRef.current?.focus());
    }

    return { open, setOpen, close, triggerRef };
}
