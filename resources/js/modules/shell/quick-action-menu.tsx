import { Link } from '@inertiajs/react';

import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import type { AppUser } from '@/types/auth';

type QuickAction = {
    label: UiTranslationKey;
    fallback: string;
    href: string;
    icon: string;
    module?: string;
    roles?: string[];
};

const OPERATIONAL_ACTIONS: QuickAction[] = [
    {
        label: 'quick_actions.property',
        fallback: 'Add property',
        href: '/assets/create',
        icon: 'bi-building-add',
        module: 'assets',
    },
    {
        label: 'quick_actions.tenant',
        fallback: 'Add tenant',
        href: '/tenants/create',
        icon: 'bi-person-plus',
        module: 'tenants',
    },
    {
        label: 'quick_actions.lease',
        fallback: 'Create lease',
        href: '/leases/create',
        icon: 'bi-file-earmark-plus',
        module: 'leases',
    },
    {
        label: 'quick_actions.payment',
        fallback: 'Record payment',
        href: '/payments/create',
        icon: 'bi-cash-stack',
        module: 'payments',
    },
    {
        label: 'quick_actions.expense',
        fallback: 'Record expense',
        href: '/expenses/create',
        icon: 'bi-receipt-cutoff',
        module: 'expenses',
    },
    {
        label: 'quick_actions.maintenance',
        fallback: 'New maintenance request',
        href: '/maintenance-requests/create',
        icon: 'bi-tools',
        module: 'maintenance',
    },
];

const TENANT_ACTIONS: QuickAction[] = [
    {
        label: 'quick_actions.maintenance',
        fallback: 'New maintenance request',
        href: '/maintenance-requests/create',
        icon: 'bi-tools',
        module: 'maintenance',
    },
    {
        label: 'quick_actions.portal',
        fallback: 'Open my portal',
        href: '/dashboard',
        icon: 'bi-house-door',
    },
];

export function QuickActionMenu({ user }: { user: AppUser }) {
    const { t } = useTranslator();
    const tenant = user.roles.includes('tenant');
    const actions = (tenant ? TENANT_ACTIONS : OPERATIONAL_ACTIONS).filter(
        (action) => canUse(action, user),
    );

    if (actions.length === 0) {
        return null;
    }

    return (
        <details className="pmc-quick-action-menu">
            <summary aria-label={t('quick_actions.title', 'Quick action')}>
                <i className="bi bi-plus-lg" aria-hidden="true" />
                <span>{t('quick_actions.title', 'Quick action')}</span>
            </summary>
            <div>
                <header>
                    <strong>{t('quick_actions.title', 'Quick action')}</strong>
                    <small>
                        {t(
                            'quick_actions.description',
                            'Start a common task without leaving your work.',
                        )}
                    </small>
                </header>
                {actions.map((action) => (
                    <Link href={action.href} key={action.href}>
                        <i className={`bi ${action.icon}`} aria-hidden="true" />
                        <span>{t(action.label, action.fallback)}</span>
                        <i
                            className="bi bi-arrow-up-right"
                            aria-hidden="true"
                        />
                    </Link>
                ))}
            </div>
        </details>
    );
}

function canUse(action: QuickAction, user: AppUser): boolean {
    if (
        action.roles &&
        !action.roles.some((role) => user.roles.includes(role))
    ) {
        return false;
    }

    if (!canCreateOperationalRecord(user) && action.href !== '/dashboard') {
        return false;
    }

    if (!action.module || user.roles.includes('superadmin')) {
        return true;
    }

    return user.portfolio?.module_settings[action.module] !== false;
}
