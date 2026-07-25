import { MODULE_NAV_GROUPS } from '@/modules/registry';
import type { ModuleNavGroup, ModuleNavItem } from '@/modules/registry';
import type { AppUser } from '@/types/auth';

export function visibleNavigationGroups(
    user: AppUser | null,
): ModuleNavGroup[] {
    const roles = user?.roles ?? [];

    return MODULE_NAV_GROUPS.map((group) => ({
        ...group,
        items: group.items.filter(
            (item) => hasRoleAccess(item, roles) && hasModuleAccess(item, user),
        ),
    })).filter((group) => group.items.length > 0);
}

export function isActivePath(currentUrl: string, href: string): boolean {
    const path = currentUrl.split('?')[0];

    return path === href || path.startsWith(`${href}/`);
}

export function navigationHref(
    item: ModuleNavItem,
    propertyId?: number | null,
): string {
    if (!item.propertyScoped || !propertyId) {
        return item.href;
    }

    const separator = item.href.includes('?') ? '&' : '?';

    return `${item.href}${separator}property_id=${propertyId}`;
}

function hasRoleAccess(item: ModuleNavItem, roles: string[]): boolean {
    return !item.roles || item.roles.some((role) => roles.includes(role));
}

function hasModuleAccess(item: ModuleNavItem, user: AppUser | null): boolean {
    if (!item.module || user?.roles.includes('superadmin')) {
        return true;
    }

    if (!user?.portfolio) {
        return false;
    }

    return user.portfolio.module_settings[item.module] !== false;
}
