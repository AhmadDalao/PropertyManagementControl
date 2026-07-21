import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { FlashBanner } from '@/components/flash-banner';
import { AdminSidebar } from '@/modules/shell/admin-sidebar';
import { AdminTopbar } from '@/modules/shell/admin-topbar';
import { TemporaryPasswordNotice } from '@/modules/shell/temporary-password-notice';
import { useAdminShell } from '@/modules/shell/use-admin-shell';
import type { SharedProps } from '@/types';

export function AdminLayout({ children }: { children: ReactNode }) {
    const { props, url } = usePage<SharedProps>();
    const user = props.auth.user;
    const shell = useAdminShell(props.app.locale, props.app.direction);

    return (
        <div
            className={`pmc-console-shell ${shell.sidebarCollapsed ? 'is-collapsed' : ''} ${shell.navOpen ? 'is-open' : ''}`}
        >
            <AdminSidebar
                currentUrl={url}
                user={user}
                direction={props.app.direction}
                {...shell}
            />

            <main className="pmc-console-main">
                <AdminTopbar user={user} {...shell} />

                <section className="pmc-console-content">
                    <FlashBanner />
                    <TemporaryPasswordNotice user={user} currentUrl={url} />
                    {children}
                </section>
            </main>
        </div>
    );
}
