import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations/workspace';
import { useTranslator } from '@/lib/i18n';

import type { Guide, PageShortcut } from './types';

export function DocumentationLibrary({
    guides,
    shortcuts,
}: {
    guides: Guide[];
    shortcuts: PageShortcut[];
}) {
    const { t } = useTranslator();

    if (guides.length === 0 && shortcuts.length === 0) {
        return null;
    }

    return (
        <div className="pmc-doc-index-grid">
            {guides.length > 0 ? (
                <WorkspacePanel
                    eyebrow={t('docs.guides')}
                    title={t('docs.all_guides')}
                    description={t('docs.guides_available', undefined, {
                        count: guides.length,
                    })}
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
            ) : null}

            {shortcuts.length > 0 ? (
                <WorkspacePanel
                    eyebrow={t('docs.pages')}
                    title={t('docs.open_workspace_title')}
                    description={t('docs.open_workspace_description')}
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
            ) : null}
        </div>
    );
}
