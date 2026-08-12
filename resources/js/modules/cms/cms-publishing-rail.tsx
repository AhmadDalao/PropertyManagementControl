import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { CmsWorkspaceStats } from './types';

export function CmsPublishingRail({ stats }: { stats: CmsWorkspaceStats }) {
    const { t } = useTranslator();

    return (
        <aside className="pmc-cms-publishing-rail">
            <h2>{t('cms.publishing_translation')}</h2>
            <dl>
                <RailItem
                    label={t('cms.published')}
                    value={stats.published}
                    tone="success"
                />
                <RailItem
                    label={t('status.draft')}
                    value={stats.drafts}
                    tone="warning"
                />
                <RailItem
                    label={t('cms.missing_arabic')}
                    value={stats.missing_arabic}
                    tone={stats.missing_arabic > 0 ? 'danger' : 'success'}
                />
                <RailItem
                    label={t('cms.navigation')}
                    value={stats.visible_navigation}
                    tone="info"
                />
            </dl>
            <Link href="/wording">
                {t('cms.page_wording')}
                <i className="bi bi-arrow-up-right" />
            </Link>
        </aside>
    );
}

function RailItem({
    label,
    value,
    tone,
}: {
    label: string;
    value: number;
    tone: 'success' | 'warning' | 'danger' | 'info';
}) {
    return (
        <div>
            <dt>{label}</dt>
            <dd className={`is-${tone}`}>{value}</dd>
        </div>
    );
}
