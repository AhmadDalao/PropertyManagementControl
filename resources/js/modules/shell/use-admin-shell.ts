import { useEffect, useRef, useState } from 'react';

const DRAWER_QUERY = '(max-width: 1199.98px)';
const COLLAPSE_STORAGE_KEY = 'property-sidebar-collapsed';
const FOCUSABLE_SELECTOR =
    'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])';

export function useAdminShell(locale: string, direction: 'ltr' | 'rtl') {
    const [navOpen, setNavOpen] = useState(false);
    const [drawerViewport, setDrawerViewport] = useState(matchesDrawerQuery);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(
        readCollapsedPreference,
    );
    const sidebarRef = useRef<HTMLElement>(null);
    const menuTriggerRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        document.documentElement.lang = locale;
        document.documentElement.dir = direction;
    }, [direction, locale]);

    useEffect(() => {
        const media = window.matchMedia(DRAWER_QUERY);
        const updateViewport = () => {
            setDrawerViewport(media.matches);

            if (!media.matches) {
                setNavOpen(false);
            }
        };

        updateViewport();
        media.addEventListener('change', updateViewport);

        return () => media.removeEventListener('change', updateViewport);
    }, []);

    useEffect(() => {
        if (!drawerViewport || !navOpen) {
            document.body.classList.remove('pmc-drawer-open');

            return;
        }

        document.body.classList.add('pmc-drawer-open');
        focusFirstSidebarControl(sidebarRef.current);

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setNavOpen(false);
                menuTriggerRef.current?.focus();

                return;
            }

            if (event.key === 'Tab') {
                trapSidebarFocus(event, sidebarRef.current);
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            document.body.classList.remove('pmc-drawer-open');
        };
    }, [drawerViewport, navOpen]);

    function toggleNavigation() {
        if (drawerViewport) {
            setNavOpen((open) => !open);

            return;
        }

        setSidebarCollapsed((collapsed) => {
            const next = !collapsed;
            writeCollapsedPreference(next);

            return next;
        });
    }

    function closeDrawer() {
        setNavOpen(false);

        if (drawerViewport) {
            window.requestAnimationFrame(() => menuTriggerRef.current?.focus());
        }
    }

    return {
        navOpen,
        drawerViewport,
        sidebarCollapsed,
        sidebarRef,
        menuTriggerRef,
        toggleNavigation,
        closeDrawer,
    };
}

function matchesDrawerQuery(): boolean {
    return (
        typeof window !== 'undefined' && window.matchMedia(DRAWER_QUERY).matches
    );
}

function readCollapsedPreference(): boolean {
    try {
        return (
            typeof window !== 'undefined' &&
            window.localStorage.getItem(COLLAPSE_STORAGE_KEY) === '1'
        );
    } catch {
        return false;
    }
}

function writeCollapsedPreference(collapsed: boolean): void {
    try {
        window.localStorage.setItem(
            COLLAPSE_STORAGE_KEY,
            collapsed ? '1' : '0',
        );
    } catch {
        // The shell still works when browser storage is unavailable.
    }
}

function focusFirstSidebarControl(sidebar: HTMLElement | null): void {
    sidebar?.querySelector<HTMLElement>(FOCUSABLE_SELECTOR)?.focus();
}

function trapSidebarFocus(
    event: KeyboardEvent,
    sidebar: HTMLElement | null,
): void {
    if (!sidebar) {
        return;
    }

    const focusable = Array.from(
        sidebar.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR),
    ).filter((element) => !element.hasAttribute('disabled'));

    if (focusable.length === 0) {
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}
