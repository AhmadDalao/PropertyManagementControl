import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import type { PlatformActivity } from '../types';

const icons: Record<string, string> = {
    portfolio: 'bi-briefcase',
    user: 'bi-person',
    asset: 'bi-building',
    tenant_profile: 'bi-people',
    lease: 'bi-file-earmark-text',
    payment: 'bi-cash-stack',
    maintenance_request: 'bi-tools',
    maintenance_vendor: 'bi-person-workspace',
    maintenance_work_order: 'bi-clipboard-check',
    expense_entry: 'bi-receipt',
    document: 'bi-file-earmark-pdf',
    cms_page: 'bi-layout-text-window',
    cms_section: 'bi-grid',
    navigation_item: 'bi-list',
};

export function PlatformActivityPanel({
    activities,
}: {
    activities: PlatformActivity[];
}) {
    const { locale, t } = useTranslator();

    return (
        <WorkspacePanel
            className="pmc-platform-activity"
            eyebrow={t('dashboard.platform_activity_eyebrow')}
            title={t('dashboard.platform_activity_title')}
            description={t('dashboard.platform_activity_description')}
            action={{
                label: t('dashboard.view_audit_history'),
                href: '/audit-logs',
            }}
        >
            {activities.length === 0 ? (
                <div className="pmc-command-empty">
                    {t('dashboard.no_platform_activity')}
                </div>
            ) : (
                <div className="pmc-platform-activity-grid">
                    {activities.map((activity) => (
                        <Link
                            key={activity.id}
                            href={activity.subject_url}
                            data-platform-activity
                        >
                            <span className="pmc-platform-activity-icon">
                                <i
                                    className={`bi ${icons[activity.subject_type ?? ''] ?? 'bi-clock-history'}`}
                                    aria-hidden="true"
                                />
                            </span>
                            <span className="pmc-platform-activity-copy">
                                <span>
                                    <strong>{activity.subject_label}</strong>
                                    <em>{activity.event_label}</em>
                                </span>
                                <small>
                                    {activity.portfolio?.name ??
                                        t('dashboard.platform_scope')}
                                    {' · '}
                                    {activity.causer_label}
                                    {' · '}
                                    {dateTime(activity.created_at, locale)}
                                </small>
                            </span>
                            <span className="pmc-platform-activity-type">
                                {activity.subject_type_label}
                            </span>
                        </Link>
                    ))}
                </div>
            )}
        </WorkspacePanel>
    );
}
