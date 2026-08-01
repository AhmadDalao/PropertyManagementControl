import { Head, Link, usePage } from '@inertiajs/react';

import '../../../../css/styles/property-explorer.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';

import { ExplorerBreadcrumbs } from './explorer-breadcrumbs';
import { ExplorerControls } from './explorer-controls';
import { ExplorerFocusPanel } from './explorer-focus-panel';
import { ExplorerRecords } from './explorer-records';
import { ExplorerSummary } from './explorer-summary';
import type { PropertyExplorerPageProps } from './types';

export default function PropertyExplorerPage() {
    const { props } = usePage<PropertyExplorerPageProps>();
    const { t } = useTranslator();
    const explorer = props.explorer;
    const canCreate = canCreateOperationalRecord(props.auth.user);

    return (
        <AdminLayout>
            <Head title={t('assets.explorer.title')} />
            <div className="pmc-explorer-page">
                <WorkspaceHeader
                    eyebrow={t('assets.explorer.eyebrow')}
                    title={t('assets.explorer.title')}
                    description={t('assets.explorer.description')}
                    actions={[
                        {
                            label: t('assets.explorer.asset_directory'),
                            href: '/assets',
                            icon: 'bi-list',
                        },
                        {
                            label: t('assets.property_map'),
                            href: '/property-map',
                            icon: 'bi-map',
                            tone: 'quiet',
                        },
                        ...(canCreate
                            ? [
                                  {
                                      label: t('assets.builder.setup_building'),
                                      href: '/assets/building-setup',
                                      icon: 'bi-plus-lg',
                                      tone: 'primary' as const,
                                  },
                              ]
                            : []),
                    ]}
                />

                {explorer.selected ? (
                    <>
                        <ExplorerControls
                            key={Object.values(explorer.filters).join(':')}
                            explorer={explorer}
                            propertyContext={props.propertyContext}
                        />
                        <ExplorerBreadcrumbs
                            breadcrumbs={explorer.breadcrumbs}
                        />
                        <ExplorerSummary explorer={explorer} />
                        <ExplorerFocusPanel
                            explorer={explorer}
                            canCreate={canCreate}
                        />
                        <ExplorerRecords
                            explorer={explorer}
                            canCreate={canCreate}
                        />
                    </>
                ) : (
                    <section className="pmc-explorer-empty is-root">
                        <i className="bi bi-buildings" aria-hidden="true" />
                        <strong>{t('assets.explorer.no_properties')}</strong>
                        <p>{t('assets.explorer.no_properties_help')}</p>
                        {canCreate ? (
                            <Link
                                href="/assets/building-setup"
                                className="btn btn-primary"
                            >
                                {t('assets.builder.setup_building')}
                            </Link>
                        ) : null}
                    </section>
                )}
            </div>
        </AdminLayout>
    );
}
