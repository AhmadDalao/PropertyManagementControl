import { Link } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { StatusBadge, WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { NavigationRecord } from './types';

export function CmsNavigationPanel({
    items,
    limitReached,
}: {
    items: NavigationRecord[];
    limitReached: boolean;
}) {
    const { t } = useTranslator();

    return (
        <WorkspacePanel
            eyebrow={t('cms.public_menus')}
            title={t('cms.navigation')}
            description={t('cms.navigation_description')}
            action={{
                label: t('cms.create_navigation'),
                href: '/cms/navigation/create',
            }}
        >
            {limitReached ? (
                <div className="alert alert-warning" role="status">
                    {t('cms.navigation_limit_notice')}
                </div>
            ) : null}

            <div className="pmc-cms-navigation-list">
                {items.length > 0 ? (
                    items.map((item) => (
                        <NavigationCard key={item.id} item={item} />
                    ))
                ) : (
                    <div className="pmc-inline-empty">
                        {t('cms.no_navigation')}
                    </div>
                )}
            </div>
        </WorkspacePanel>
    );
}

function NavigationCard({ item }: { item: NavigationRecord }) {
    const { locale, t } = useTranslator();
    const title =
        locale === 'ar'
            ? item.title_ar || item.title_en
            : item.title_en || item.title_ar;
    const destination = item.page
        ? item.page.is_homepage
            ? '/'
            : `/pages/${item.page.slug}`
        : item.url || '/';

    return (
        <article className="pmc-cms-navigation-card">
            <div>
                <span>{t(`cms.location_${item.location}`)}</span>
                <strong>{title}</strong>
                <small>{item.title_ar || destination}</small>
            </div>
            <div className="pmc-cms-navigation-meta">
                <StatusBadge
                    value={item.is_visible ? 'visible' : 'hidden'}
                    label={item.is_visible ? t('cms.visible') : t('cms.hidden')}
                    tone={item.is_visible ? 'success' : 'neutral'}
                />
                <span>{destination}</span>
                {item.children?.length ? (
                    <span>
                        {t('cms.child_links', undefined, {
                            count: item.children.length,
                        })}
                    </span>
                ) : null}
            </div>
            <div className="pmc-cms-card-actions">
                <Link
                    className="btn btn-outline-secondary btn-sm"
                    href={`/cms/navigation/${item.id}/edit`}
                >
                    <i className="bi bi-pencil" />
                    {t('actions.edit')}
                </Link>
                <ArchiveAction
                    href={`/navigation-items/${item.id}`}
                    label={t('actions.delete')}
                    confirmMessage={t(
                        'cms.delete_navigation_confirm',
                        undefined,
                        { title: title || '' },
                    )}
                />
            </div>

            {item.children?.length ? (
                <div className="pmc-cms-navigation-children">
                    {item.children.map((child) => (
                        <NavigationChild key={child.id} item={child} />
                    ))}
                </div>
            ) : null}
        </article>
    );
}

function NavigationChild({ item }: { item: NavigationRecord }) {
    const { locale, t } = useTranslator();
    const title =
        locale === 'ar'
            ? item.title_ar || item.title_en
            : item.title_en || item.title_ar;

    return (
        <div>
            <span>
                <i className="bi bi-arrow-return-right" />
                {title}
            </span>
            <Link href={`/cms/navigation/${item.id}/edit`}>
                {t('actions.edit')}
            </Link>
        </div>
    );
}
