import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { CmsIndexPageProps, CmsPageRecord } from './types';

type CmsPagesTableProps = Pick<
    CmsIndexPageProps,
    'pages' | 'filters' | 'counts'
>;

export function CmsPagesTable({ pages, filters, counts }: CmsPagesTableProps) {
    const { locale, t } = useTranslator();
    const pageName = (page: CmsPageRecord) =>
        locale === 'ar'
            ? page.title_ar || page.title_en
            : page.title_en || page.title_ar;
    const titleCell = (page: CmsPageRecord) => (
        <div className="pmc-primary-cell">
            <strong>{pageName(page)}</strong>
            <span>{page.title_ar || `/${page.slug}`}</span>
            <div className="pmc-inline-badges">
                {page.is_homepage ? (
                    <StatusBadge
                        value="homepage"
                        label={t('cms.homepage')}
                        tone="blue"
                    />
                ) : null}
                {!page.is_visible ? (
                    <StatusBadge
                        value="hidden"
                        label={t('cms.hidden')}
                        tone="neutral"
                    />
                ) : null}
            </div>
        </div>
    );
    const pathCell = (page: CmsPageRecord) => (
        <div className="pmc-stacked-cell">
            <strong>{page.is_homepage ? '/' : `/pages/${page.slug}`}</strong>
            <span>
                {(locale === 'ar'
                    ? page.excerpt_ar || page.excerpt_en
                    : page.excerpt_en || page.excerpt_ar) ||
                    t('cms.no_excerpt')}
            </span>
        </div>
    );
    const sectionCell = (page: CmsPageRecord) => (
        <div className="pmc-stacked-cell">
            <strong>{page.page_sections_count ?? 0}</strong>
            <span>{t('cms.attached_blocks')}</span>
        </div>
    );
    const actions = (page: CmsPageRecord) => (
        <RecordActions
            showHref={`/cms/pages/${page.id}`}
            editHref={`/cms/pages/${page.id}/edit`}
        >
            {page.status !== 'archived' ? (
                <ArchiveAction
                    href={`/cms/pages/${page.id}`}
                    confirmMessage={t('cms.archive_page_confirm', undefined, {
                        title: pageName(page) || '',
                    })}
                />
            ) : null}
        </RecordActions>
    );

    return (
        <DataTable
            title={t('cms.public_pages')}
            description={t('cms.public_pages_description')}
            data={pages}
            filters={filters}
            counts={counts}
            basePath="/cms?view=pages"
            createHref="/cms/pages/create"
            createLabel={t('cms.create_page')}
            rowHref={(page) => `/cms/pages/${page.id}`}
            exportHref={exportUrl('/exports/cms-pages', filters)}
            filterFields={[
                {
                    name: 'status',
                    label: t('cms.status'),
                    options: [
                        { label: t('cms.all_pages'), value: 'all' },
                        { label: t('status.draft'), value: 'draft' },
                        { label: t('status.published'), value: 'published' },
                        { label: t('status.archived'), value: 'archived' },
                    ],
                },
            ]}
            emptyText={t('cms.no_pages')}
            mobileCard={{
                title: titleCell,
                subtitle: (page) => <StatusBadge value={page.status} />,
                status: (page) =>
                    page.is_homepage ? t('cms.homepage') : `/${page.slug}`,
                meta: [
                    { label: t('cms.public_path'), value: pathCell },
                    { label: t('cms.sections'), value: sectionCell },
                ],
                actions,
            }}
            columns={[
                { key: 'title', label: t('cms.page'), render: titleCell },
                {
                    key: 'slug',
                    label: t('cms.public_path'),
                    render: pathCell,
                },
                {
                    key: 'sections',
                    label: t('cms.sections'),
                    render: sectionCell,
                },
                {
                    key: 'status',
                    label: t('cms.status'),
                    render: (page) => <StatusBadge value={page.status} />,
                },
                {
                    key: 'actions',
                    label: t('cms.actions'),
                    className: 'text-end',
                    render: actions,
                },
            ]}
        />
    );
}
