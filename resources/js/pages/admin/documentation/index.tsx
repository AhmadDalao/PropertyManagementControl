import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

type QuickStart = {
    title: string;
    summary: string;
    route: string;
    icon: string;
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
    title: string;
    audience: string;
    summary: string;
    route: string;
    icon: string;
    features: string[];
    steps: string[];
    rules: string[];
};

type PageProps = SharedProps & {
    audience: string;
    guides: Guide[];
    quickStarts: QuickStart[];
    roleGuides: RoleGuide[];
};

export default function DocumentationPage() {
    const { props } = usePage<PageProps>();
    const [query, setQuery] = useState('');

    const normalizedQuery = query.trim().toLowerCase();
    const filteredGuides = useMemo(() => {
        if (!normalizedQuery) {
            return props.guides;
        }

        return props.guides.filter((guide) =>
            [
                guide.title,
                guide.audience,
                guide.summary,
                guide.route,
                ...guide.features,
                ...guide.steps,
                ...guide.rules,
            ]
                .join(' ')
                .toLowerCase()
                .includes(normalizedQuery),
        );
    }, [normalizedQuery, props.guides]);

    return (
        <AdminLayout>
            <Head title="Documentation" />
            <PageHeader
                title="Documentation"
                description="Searchable operating manual for owners, managers, tenants, and superadmins."
            />

            <section className="pmc-doc-hero">
                <div>
                    <div className="pmc-kicker mb-2">System guide</div>
                    <h2>Use the exact workflow before touching live data.</h2>
                    <p>
                        This module explains where to start, who can do what,
                        and which page controls each property operation.
                    </p>
                    <div className="pmc-doc-stats">
                        <span>{props.quickStarts.length} quick starts</span>
                        <span>{props.roleGuides.length} role guides</span>
                        <span>{props.guides.length} feature guides</span>
                    </div>
                </div>
                <label className="pmc-doc-search">
                    <span>Search documentation</span>
                    <input
                        type="search"
                        className="form-control form-control-lg"
                        placeholder="Search assets, tenants, leases, payments, CMS..."
                        value={query}
                        onChange={(event) =>
                            setQuery(event.currentTarget.value)
                        }
                    />
                </label>
            </section>

            <div className="pmc-doc-layout">
                <aside className="pmc-doc-sidebar">
                    <div className="pmc-table-heading">
                        <span className="pmc-table-icon">
                            <i className="bi bi-search" />
                        </span>
                        <strong>Guide Index</strong>
                        <span className="pmc-table-count">
                            {filteredGuides.length}
                        </span>
                    </div>
                    <p>
                        Signed in as <strong>{props.audience}</strong>. Open a
                        page from any guide when your role has access.
                    </p>
                    <nav className="pmc-doc-nav" aria-label="Documentation">
                        {props.guides.map((guide) => (
                            <a key={guide.title} href={`#${slug(guide.title)}`}>
                                <i className={`bi ${guide.icon}`} />
                                <span>{guide.title}</span>
                            </a>
                        ))}
                    </nav>
                </aside>

                <section className="pmc-doc-stack">
                    <section className="pmc-doc-group">
                        <div className="pmc-doc-group-head">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Start here
                                </div>
                                <h2>Quick workflows</h2>
                            </div>
                            <span>
                                Fast paths for the pages people actually use.
                            </span>
                        </div>
                        <div className="pmc-doc-card-grid">
                            {props.quickStarts.map((item) => (
                                <Link
                                    key={item.title}
                                    href={item.route}
                                    className="pmc-doc-mini-card"
                                >
                                    <i className={`bi ${item.icon}`} />
                                    <strong>{item.title}</strong>
                                    <span>{item.summary}</span>
                                </Link>
                            ))}
                        </div>
                    </section>

                    <section className="pmc-doc-group">
                        <div className="pmc-doc-group-head">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Role guide
                                </div>
                                <h2>Who does what</h2>
                            </div>
                            <span>
                                Permission boundaries without guesswork.
                            </span>
                        </div>
                        <div className="pmc-doc-role-grid">
                            {props.roleGuides.map((guide) => (
                                <article
                                    key={guide.role}
                                    className="pmc-doc-role-card"
                                >
                                    <header>
                                        <i className={`bi ${guide.icon}`} />
                                        <div>
                                            <div className="pmc-kicker mb-1">
                                                {guide.role}
                                            </div>
                                            <h3>{guide.title}</h3>
                                        </div>
                                    </header>
                                    <p>{guide.summary}</p>
                                    <ul>
                                        {guide.responsibilities.map((item) => (
                                            <li key={item}>{item}</li>
                                        ))}
                                    </ul>
                                    <div className="pmc-doc-route-pills">
                                        {guide.routes.map((route) => (
                                            <span key={route}>{route}</span>
                                        ))}
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="pmc-doc-group">
                        <div className="pmc-doc-group-head">
                            <div>
                                <div className="pmc-kicker mb-2">
                                    Full manual
                                </div>
                                <h2>Feature guides</h2>
                            </div>
                            <span>
                                Search, open, and follow the workflow in order.
                            </span>
                        </div>

                        {filteredGuides.length > 0 ? (
                            filteredGuides.map((guide) => (
                                <article
                                    key={guide.title}
                                    id={slug(guide.title)}
                                    className="pmc-doc-guide-card"
                                >
                                    <header>
                                        <div>
                                            <div className="pmc-kicker mb-2">
                                                {guide.audience}
                                            </div>
                                            <h3>
                                                <i
                                                    className={`bi ${guide.icon}`}
                                                />
                                                <span>{guide.title}</span>
                                            </h3>
                                        </div>
                                        <Link
                                            href={guide.route}
                                            className="btn btn-outline-secondary btn-sm"
                                        >
                                            Open page
                                        </Link>
                                    </header>
                                    <p>{guide.summary}</p>
                                    <ScreenMock guide={guide} />
                                    <div className="pmc-doc-columns">
                                        <GuideBlock
                                            title="Features"
                                            items={guide.features}
                                        />
                                        <GuideBlock
                                            title="How to use it"
                                            items={guide.steps}
                                            ordered
                                        />
                                    </div>
                                    <GuideBlock
                                        title="Rules that matter"
                                        items={guide.rules}
                                    />
                                </article>
                            ))
                        ) : (
                            <div className="pmc-empty-state pmc-doc-empty">
                                <i className="bi bi-search" />
                                <strong>No guide matches this search</strong>
                                <span>
                                    Try assets, lease, payment, CMS, tenant, or
                                    maintenance.
                                </span>
                            </div>
                        )}
                    </section>
                </section>
            </div>
        </AdminLayout>
    );
}

function GuideBlock({
    title,
    items,
    ordered = false,
}: {
    title: string;
    items: string[];
    ordered?: boolean;
}) {
    const List = ordered ? 'ol' : 'ul';

    return (
        <div className="pmc-doc-block">
            <h4>{title}</h4>
            <List>
                {items.map((item) => (
                    <li key={item}>{item}</li>
                ))}
            </List>
        </div>
    );
}

function ScreenMock({ guide }: { guide: Guide }) {
    return (
        <figure className="pmc-doc-screen">
            <div className="pmc-doc-screen-bar">
                <span />
                <span />
                <span />
                <em>{guide.route}</em>
            </div>
            <div className="pmc-doc-screen-body">
                <aside>
                    <i className={`bi ${guide.icon}`} />
                    <strong>{guide.title}</strong>
                    <small>{guide.audience}</small>
                </aside>
                <main>
                    <div>
                        <strong>{guide.title}</strong>
                        <span>Workflow preview</span>
                    </div>
                    <div className="pmc-doc-screen-steps">
                        {guide.steps.slice(0, 3).map((step, index) => (
                            <span key={step}>
                                <em>{index + 1}</em>
                                {step}
                            </span>
                        ))}
                    </div>
                    <div className="pmc-doc-screen-lines">
                        <span />
                        <span />
                        <span />
                    </div>
                </main>
            </div>
            <figcaption>
                <strong>Screen guide</strong>
                <span>Recognize the page before following the steps.</span>
            </figcaption>
        </figure>
    );
}

function slug(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}
