import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/wording.css';

import { ContentTranslationQueue } from './content-translation-queue';
import type { WordingPageProps } from './types';
import { useWordingWorkspace } from './use-wording-workspace';
import { WordingCatalog } from './wording-catalog';
import { WordingEditor } from './wording-editor';
import { WordingMetrics } from './wording-metrics';
import { WordingTabs } from './wording-tabs';

export default function WordingIndexPage() {
    const { props } = usePage<WordingPageProps>();
    const { t } = useTranslator();
    const workspace = useWordingWorkspace(props.filters, t);

    return (
        <AdminLayout>
            <Head title={t('wording.title')} />
            <WorkspaceHeader
                eyebrow={t('wording.eyebrow')}
                title={t('wording.title')}
                description={t('wording.description')}
                actions={[
                    {
                        label: t('wording.open_website_builder'),
                        href: '/cms',
                        icon: 'bi-layout-wtf',
                    },
                ]}
            />
            <WordingMetrics
                totalLabels={props.totalLabels}
                customizedCount={props.customizedCount}
                content={props.contentTranslations}
            />
            <WordingTabs
                active={workspace.tab}
                totalLabels={props.totalLabels}
                contentTotal={props.contentTranslations.total}
                onChange={workspace.setTab}
            />
            {workspace.tab === 'wording' ? (
                <WordingCatalog
                    entries={props.entries}
                    groups={props.groups}
                    filters={props.filters}
                    search={workspace.search}
                    groupLabel={workspace.groupLabel}
                    onSearch={workspace.setSearch}
                    onApply={workspace.applyFilters}
                    onSelect={workspace.selectEntry}
                />
            ) : (
                <ContentTranslationQueue
                    content={props.contentTranslations}
                    selectedModule={props.filters.contentModule}
                    onModule={(contentModule) =>
                        workspace.applyFilters({
                            content_module: contentModule,
                        })
                    }
                />
            )}
            {workspace.selected ? (
                <WordingEditor
                    entry={workspace.selected}
                    groupLabel={workspace.groupLabel(workspace.selected.group)}
                    onClose={workspace.closeEditor}
                />
            ) : null}
        </AdminLayout>
    );
}
