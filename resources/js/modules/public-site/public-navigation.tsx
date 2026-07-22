import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { RefObject } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { NavigationItemRecord } from '@/types';

export function PublicNavigation({
    items,
    locale,
    menuOpen,
    navigationRef,
    onNavigate,
}: {
    items: NavigationItemRecord[];
    locale: 'en' | 'ar';
    menuOpen: boolean;
    navigationRef: RefObject<HTMLElement | null>;
    onNavigate: () => void;
}) {
    const { t } = useTranslator();

    return (
        <nav
            ref={navigationRef}
            id="public-navigation"
            className={`pmc-site-links ${menuOpen ? 'is-open' : ''}`}
            aria-label={t('public.navigation')}
        >
            {items.map((item) => (
                <PublicNavItem
                    key={`${item.id}-${item.url}`}
                    item={item}
                    locale={locale}
                    onNavigate={onNavigate}
                />
            ))}
        </nav>
    );
}

export function defaultPublicNavigation(
    t: ReturnType<typeof useTranslator>['t'],
): NavigationItemRecord[] {
    return [
        [t('public.features'), '#features'],
        [t('public.workflow'), '#workflow'],
        [t('public.faq'), '#faq'],
    ].map(([title, href], index) => ({
        id: -index - 1,
        title_en: title,
        title_ar: title,
        url: href,
        target: '_self',
        location: 'header',
        sort_order: index + 1,
    }));
}

function PublicNavItem({
    item,
    locale,
    onNavigate,
}: {
    item: NavigationItemRecord;
    locale: 'en' | 'ar';
    onNavigate: () => void;
}) {
    const { t } = useTranslator();
    const [expanded, setExpanded] = useState(false);
    const children = item.children ?? [];

    if (children.length === 0) {
        return (
            <PublicNavLink
                item={item}
                locale={locale}
                onNavigate={onNavigate}
            />
        );
    }

    return (
        <div
            className={`pmc-site-link-group ${expanded ? 'is-open' : ''}`}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) {
                    setExpanded(false);
                }
            }}
        >
            <span>
                <PublicNavLink
                    item={item}
                    locale={locale}
                    onNavigate={onNavigate}
                />
                <button
                    type="button"
                    aria-label={t('public.open_child_links')}
                    aria-expanded={expanded}
                    onClick={() => setExpanded((current) => !current)}
                >
                    <i className="bi bi-chevron-down" />
                </button>
            </span>
            <div className="pmc-site-submenu" hidden={!expanded}>
                {children.map((child) => (
                    <PublicNavLink
                        key={child.id}
                        item={child}
                        locale={locale}
                        onNavigate={() => {
                            setExpanded(false);
                            onNavigate();
                        }}
                    />
                ))}
            </div>
        </div>
    );
}

function PublicNavLink({
    item,
    locale,
    onNavigate,
}: {
    item: NavigationItemRecord;
    locale: 'en' | 'ar';
    onNavigate: () => void;
}) {
    const href = item.page
        ? item.page.is_homepage
            ? '/'
            : `/pages/${item.page.slug}`
        : item.url || '/';
    const label =
        locale === 'ar'
            ? item.title_ar || item.title_en
            : item.title_en || item.title_ar;
    const target = item.target || '_self';

    if (
        target === '_blank' ||
        href.startsWith('#') ||
        href.startsWith('http') ||
        href.startsWith('mailto:') ||
        href.startsWith('tel:')
    ) {
        return (
            <a
                href={href.startsWith('#') ? `/${href}` : href}
                target={target}
                rel={target === '_blank' ? 'noreferrer' : undefined}
                onClick={onNavigate}
            >
                {label}
            </a>
        );
    }

    return (
        <Link href={href} onClick={onNavigate}>
            {label}
        </Link>
    );
}
