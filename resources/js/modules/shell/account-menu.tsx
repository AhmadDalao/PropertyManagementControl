import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import type { AppUser } from '@/types/auth';

export function AccountMenu({ user }: { user: AppUser }) {
    const [open, setOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const triggerRef = useRef<HTMLButtonElement>(null);
    const { t } = useTranslator();
    const role = user.roles[0] ?? '';
    const roleLabel = role
        ? t(`roles.${role}` as UiTranslationKey, role)
        : user.email;

    useEffect(() => {
        if (!open) {
            return;
        }

        const closeOutside = (event: PointerEvent) => {
            if (!menuRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                triggerRef.current?.focus();
            }
        };

        document.addEventListener('pointerdown', closeOutside);
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('pointerdown', closeOutside);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, [open]);

    function closeMenu() {
        setOpen(false);
    }

    return (
        <div ref={menuRef} className="pmc-account-menu">
            <button
                ref={triggerRef}
                type="button"
                className="pmc-account-trigger"
                aria-label={t('shell.account_menu', undefined, {
                    name: user.name,
                })}
                aria-haspopup="menu"
                aria-expanded={open}
                onClick={() => setOpen((current) => !current)}
            >
                <span>{user.name.slice(0, 1).toUpperCase()}</span>
                <div>
                    <strong>{user.name}</strong>
                    <small>{roleLabel}</small>
                </div>
                <i className="bi bi-chevron-down" aria-hidden="true" />
            </button>

            {open ? (
                <div className="pmc-account-panel" role="menu">
                    <div className="pmc-account-panel-head">
                        <strong>{user.name}</strong>
                        <small>{user.email}</small>
                    </div>
                    <Link href="/profile" role="menuitem" onClick={closeMenu}>
                        {t('nav.profile')}
                    </Link>
                    <Link href="/dashboard" role="menuitem" onClick={closeMenu}>
                        {t('nav.dashboard')}
                    </Link>
                    <Link
                        href="/documentation"
                        role="menuitem"
                        onClick={closeMenu}
                    >
                        {t('nav.documentation')}
                    </Link>
                    <button
                        type="button"
                        role="menuitem"
                        onClick={() => {
                            setOpen(false);
                            router.post('/logout');
                        }}
                    >
                        {t('nav.logout')}
                    </button>
                </div>
            ) : null}
        </div>
    );
}
