import { Head, Link, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations/workspace';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

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

type PageProps = SharedProps & {
    audience: string;
    guide: Guide;
    relatedGuides: Guide[];
};

export default function DocumentationGuidePage() {
    const { props } = usePage<PageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={props.guide.title} />
            <WorkspaceHeader
                eyebrow={t('docs.guide_suffix', ':audience guide', {
                    audience: props.guide.audience,
                })}
                title={props.guide.title}
                description={props.guide.summary}
                actions={[
                    {
                        label: t('docs.open_workspace', 'Open workspace'),
                        href: props.guide.route,
                        icon: 'bi-arrow-up-right',
                        tone: 'primary',
                    },
                    {
                        label: t('docs.back_to_guides', 'Back to guides'),
                        href: '/documentation',
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                ]}
            />

            <div className="pmc-doc-detail-layout">
                <aside>
                    <div className="pmc-doc-guide-icon">
                        <i className={`bi ${props.guide.icon}`} />
                    </div>
                    <span>{t('docs.guide_contents', 'Guide contents')}</span>
                    <a href="#features">{t('docs.features', 'Features')}</a>
                    <a href="#steps">{t('docs.how_to', 'How to use it')}</a>
                    <a href="#rules">{t('docs.rules', 'Rules that matter')}</a>
                </aside>

                <main>
                    <section id="features">
                        <div className="pmc-kicker">
                            {t(
                                'docs.feature_kicker',
                                'What this page controls',
                            )}
                        </div>
                        <h2>{t('docs.features', 'Features')}</h2>
                        <div className="pmc-doc-feature-grid">
                            {props.guide.features.map((feature, index) => (
                                <article key={feature}>
                                    <span>
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                    <p>{feature}</p>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section id="steps">
                        <div className="pmc-kicker">
                            {t('docs.steps_kicker', 'Complete the workflow')}
                        </div>
                        <h2>{t('docs.how_to', 'How to use it')}</h2>
                        <ol className="pmc-doc-step-list">
                            {props.guide.steps.map((step) => (
                                <li key={step}>{step}</li>
                            ))}
                        </ol>
                    </section>

                    <section id="rules">
                        <div className="pmc-kicker">
                            {t('docs.rules_kicker', 'Do not skip these')}
                        </div>
                        <h2>{t('docs.rules', 'Rules that matter')}</h2>
                        <ul className="pmc-doc-rule-list">
                            {props.guide.rules.map((rule) => (
                                <li key={rule}>
                                    <i className="bi bi-check2-circle" />
                                    <span>{rule}</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                </main>
            </div>

            {props.relatedGuides.length > 0 ? (
                <section className="pmc-doc-related">
                    <div>
                        <span>{t('docs.keep_learning', 'Keep learning')}</span>
                        <h2>{t('docs.related_guides', 'Related guides')}</h2>
                    </div>
                    <div>
                        {props.relatedGuides.map((guide) => (
                            <Link
                                key={guide.slug}
                                href={`/documentation/${guide.slug}`}
                            >
                                <i className={`bi ${guide.icon}`} />
                                <strong>{guide.title}</strong>
                                <i className="bi bi-arrow-up-right" />
                            </Link>
                        ))}
                    </div>
                </section>
            ) : null}
        </AdminLayout>
    );
}
