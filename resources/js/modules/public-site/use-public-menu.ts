import { useEffect, useRef, useState } from 'react';

export function usePublicMenu() {
    const [menuOpen, setMenuOpen] = useState(false);
    const triggerRef = useRef<HTMLButtonElement>(null);
    const navigationRef = useRef<HTMLElement>(null);

    useEffect(() => {
        document.body.classList.toggle('pmc-site-menu-open', menuOpen);

        return () => document.body.classList.remove('pmc-site-menu-open');
    }, [menuOpen]);

    useEffect(() => {
        if (!menuOpen) {
            return;
        }

        navigationRef.current?.querySelector<HTMLElement>('a')?.focus();

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') {
                return;
            }

            setMenuOpen(false);
            requestAnimationFrame(() => triggerRef.current?.focus());
        };

        document.addEventListener('keydown', closeOnEscape);

        return () => document.removeEventListener('keydown', closeOnEscape);
    }, [menuOpen]);

    return {
        closeMenu: () => setMenuOpen(false),
        menuOpen,
        navigationRef,
        setMenuOpen,
        triggerRef,
    };
}
