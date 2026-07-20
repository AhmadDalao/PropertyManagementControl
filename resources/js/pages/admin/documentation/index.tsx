import { Head, Link, usePage } from '@inertiajs/react';
import { useDeferredValue, useMemo, useState } from 'react';

import {
    WorkspaceHeader,
    WorkspacePanel,
} from '@/components/operations/workspace';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

type QuickStart = {
    title: string;
    audience: string;
    summary: string;
    route: string;
    icon: string;
    steps?: string[];
};

type RoleGuide = {
    role: string;
    title: string;
    summary: string;
    responsibilities: string[];
    routes: string[];
    icon: string;
};

type Guide = {
    slug: string;
    title: string;
    audience: string;
    summary: string;
    route: string;
    icon: string;
    features: string[];
    steps: string[];
    rules: string[];
};

type WorkflowTrack = {
    key: string;
    title: string;
    audience: string;
    summary: string;
    outcome: string;
    route: string;
    icon: string;
    steps: Array<{ label: string; route: string; module?: string | null }>;
};

type PageShortcut = {
    label: string;
    category: string;
    route: string;
    description: string;
    action: string;
    icon: string;
};

type ControlCheck = {
    title: string;
    summary: string;
    route: string;
    icon: string;
    checks: string[];
};

type ModuleStatus = {
    key: string;
    label: string;
    description: string;
    enabled: boolean;
};

type PageProps = SharedProps & {
    audience: string;
    guides: Guide[];
    quickStarts: QuickStart[];
    roleGuides: RoleGuide[];
    workflowTracks: WorkflowTrack[];
    pageShortcuts: PageShortcut[];
    controlChecks: ControlCheck[];
    moduleStatus: ModuleStatus[];
};

export default function DocumentationPage() {
    const { props } = usePage<PageProps>();
    const { t } = useTranslator();
    const [query, setQuery] = useState('');
    const deferredQuery = useDeferredValue(query.trim().toLowerCase());
    const currentRole = props.roleGuides.find(
        (guide) => guide.role === props.audience,
    );
    const enabledModules = props.moduleStatus.filter(
        (module) => module.enabled,
    );

    const guides = useMemo(
        () =>
            props.guides.filter((guide) =>
                matches(
                    [
                        guide.title,
                        guide.audience,
                        guide.summary,
                        ...guide.features,
                    ],
                    deferredQuery,
                ),
            ),
        [deferredQuery, props.guides],
    );
    const workflows = useMemo(
        () =>
            props.workflowTracks.filter((workflow) =>
                matches(
                    [
                        workflow.title,
                        workflow.summary,
                        workflow.outcome,
                        ...workflow.steps.map((step) => step.label),
                    ],
                    deferredQuery,
                ),
            ),
        [deferredQuery, props.workflowTracks],
    );
    const shortcuts = useMemo(
        () =>
            props.pageShortcuts
                .filter((shortcut) =>
                    matches(
                        [
                            shortcut.label,
                            shortcut.category,
                            shortcut.description,
                        ],
                        deferredQuery,
                    ),
                )
                .slice(0, 8),
        [deferredQuery, props.pageShortcuts],
    );

    return (
        <AdminLayout>
            <Head title={t('docs.title', 'Documentation')} />
            <WorkspaceHeader
                eyebrow={t('docs.system_guide', 'System guide')}
                title={t('docs.title', 'Documentation')}
                description={t(
                    'docs.description',
                    'Short, role-aware guides for running each property workflow.',
                )}
            />

            <section className="pmc-doc-command">
                <div>
                    <span>{t('docs.your_access', 'Your access')}</span>
                    <strong>{currentRole?.title ?? props.audience}</strong>
                    <small>
                        {t(
                            'docs.active_modules',
                            ':count active modules for this account',
                            { count: enabledModules.length },
                        )}
                    </small>
                </div>
                <label htmlFor="documentation-search">
                    <span>{t('docs.search', 'Search guides')}</span>
                    <i className="bi bi-search" />
                    <input
                        id="documentation-search"
                        type="search"
                        className="form-control"
                        placeholder={t(
                            'docs.search_placeholder',
                            'Assets, leases, payments, maintenance...',
                        )}
                        value={query}
                        onChange={(event) =>
                            setQuery(event.currentTarget.value)
                        }
                    />
                </label>
            </section>

            <WorkspacePanel
                eyebrow={t('docs.operating_cycles', 'Operating cycles')}
                title={t(
                    'docs.run_in_order',
                    'Run the system in the right order',
                )}
                description={t(
                    'docs.choose_workflow',
                    'Choose the workflow that matches the job you need to finish.',
                )}
            >
                <div className="pmc-doc-workflow-grid">
                    {workflows.map((workflow) => (
                        <article
                            key={workflow.key}
                            className="pmc-doc-workflow-card"
                        >
                            <header>
                                <i className={`bi ${workflow.icon}`} />
                                <div>
                                    <span>{workflow.audience}</span>
                                    <strong>{workflow.title}</strong>
                                    <small>{workflow.summary}</small>
                                </div>
                            </header>
                            <ol>
                                {workflow.steps.slice(0, 6).map((step) => (
                                    <li key={step.label}>{step.label}</li>
                                ))}
                            </ol>
                            <footer>
                                <span>{workflow.outcome}</span>
                                <Link
                                    href={workflow.route}
                                    className="btn btn-primary btn-sm"
                                >
                                    {t('docs.start', 'Start')}
                                </Link>
                            </footer>
                        </article>
                    ))}
                </div>
            </WorkspacePanel>

            <div className="pmc-doc-index-grid">
                <WorkspacePanel
                    eyebrow={t('docs.guides', 'Guides')}
                    title={t('docs.all_guides', 'All guides')}
                    description={t(
                        'docs.guides_available',
                        ':count guides available to your role.',
                        { count: guides.length },
                    )}
                >
                    <div className="pmc-doc-guide-grid">
                        {guides.map((guide) => (
                            <Link
                                key={guide.slug}
                                href={`/documentation/${guide.slug}`}
                                className="pmc-doc-guide-link"
                            >
                                <i className={`bi ${guide.icon}`} />
                                <div>
                                    <span>{guide.audience}</span>
                                    <strong>{guide.title}</strong>
                                    <small>{guide.summary}</small>
                                </div>
                                <i className="bi bi-arrow-up-right" />
                            </Link>
                        ))}
                    </div>
                </WorkspacePanel>

                <WorkspacePanel
                    eyebrow={t('docs.pages', 'Pages')}
                    title={t(
                        'docs.open_workspace_title',
                        'Open the right workspace',
                    )}
                    description={t(
                        'docs.open_workspace_description',
                        'Direct shortcuts, already filtered by your permissions.',
                    )}
                >
                    <div className="pmc-doc-shortcut-list">
                        {shortcuts.map((shortcut) => (
                            <Link key={shortcut.route} href={shortcut.route}>
                                <i className={`bi ${shortcut.icon}`} />
                                <div>
                                    <span>{shortcut.category}</span>
                                    <strong>{shortcut.label}</strong>
                                </div>
                                <em>{shortcut.action}</em>
                            </Link>
                        ))}
                    </div>
                </WorkspacePanel>
            </div>

            <details className="pmc-doc-controls">
                <summary>
                    <div>
                        <i className="bi bi-shield-check" />
                        <span>
                            {t(
                                'docs.regulation_checks',
                                'Regression and regulation checks',
                            )}
                        </span>
                        <strong>{props.controlChecks.length}</strong>
                    </div>
                    <i className="bi bi-chevron-down" />
                </summary>
                <div>
                    {props.controlChecks.map((check) => (
                        <article key={check.title}>
                            <i className={`bi ${check.icon}`} />
                            <div>
                                <strong>{check.title}</strong>
                                <span>{check.summary}</span>
                                <ul>
                                    {check.checks.map((item) => (
                                        <li key={item}>{item}</li>
                                    ))}
                                </ul>
                            </div>
                            <Link
                                href={check.route}
                                className="btn btn-outline-secondary btn-sm"
                            >
                                {t('actions.open', 'Open')}
                            </Link>
                        </article>
                    ))}
                </div>
            </details>
        </AdminLayout>
    );
}

function matches(values: string[], query: string): boolean {
    return query === '' || values.join(' ').toLowerCase().includes(query);
}
