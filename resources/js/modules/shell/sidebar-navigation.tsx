import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import type { ModuleNavGroup } from '@/modules/registry';

import { isActivePath, navigationHref } from './navigation-access';
import { useSidebarNavigation } from './use-sidebar-navigation';

type SidebarNavigationProps = {
    groups: ModuleNavGroup[];
    currentUrl: string;
    propertyId?: number | null;
    compact: boolean;
    onExpand: () => void;
    onNavigate: () => void;
};

export function SidebarNavigation({
    groups,
    currentUrl,
    propertyId,
    compact,
    onExpand,
    onNavigate,
}: SidebarNavigationProps) {
    const { t } = useTranslator();
    const { activeGroupKey, openGroupKey, toggleGroup } = useSidebarNavigation(
        groups,
        currentUrl,
        compact,
        onExpand,
    );

    return (
        <nav
            className="pmc-console-nav"
            aria-label={t('shell.navigation', 'Main navigation')}
        >
            {groups.map((group) => {
                const groupId = `pmc-nav-${group.labelKey.replaceAll('.', '-')}`;
                const open = openGroupKey === group.labelKey;
                const active = activeGroupKey === group.labelKey;

                return (
                    <section
                        className={`pmc-nav-group ${open ? 'is-open' : ''} ${active ? 'has-active' : ''}`}
                        data-navigation-group={group.labelKey}
                        key={group.labelKey}
                    >
                        <button
                            type="button"
                            className="pmc-nav-group-trigger"
                            title={t(group.labelKey)}
                            aria-expanded={open}
                            aria-controls={groupId}
                            data-navigation-group-trigger={group.labelKey}
                            onClick={() => toggleGroup(group.labelKey)}
                        >
                            <i
                                className={`bi ${group.icon} pmc-nav-group-icon`}
                                aria-hidden="true"
                            />
                            <span>{t(group.labelKey)}</span>
                            <i
                                className="bi bi-chevron-down pmc-nav-group-chevron"
                                aria-hidden="true"
                            />
                        </button>

                        <div
                            id={groupId}
                            className="pmc-nav-group-items"
                            data-navigation-group-items={group.labelKey}
                            hidden={!open}
                        >
                            {group.items.map((item) => {
                                const itemActive = isActivePath(
                                    currentUrl,
                                    item.href,
                                );

                                return (
                                    <Link
                                        key={item.href}
                                        href={navigationHref(item, propertyId)}
                                        onClick={onNavigate}
                                        className={`pmc-nav-link ${itemActive ? 'active' : ''}`}
                                        title={t(item.labelKey)}
                                        aria-current={
                                            itemActive ? 'page' : undefined
                                        }
                                    >
                                        <i
                                            className={`bi ${item.icon}`}
                                            aria-hidden="true"
                                        />
                                        <span>{t(item.labelKey)}</span>
                                    </Link>
                                );
                            })}
                        </div>
                    </section>
                );
            })}
        </nav>
    );
}
