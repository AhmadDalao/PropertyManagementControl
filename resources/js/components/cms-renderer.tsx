type SectionRecord = {
    id: number;
    section?: {
        id?: number;
        section_type: string;
        content_en?: Record<string, unknown> | null;
        content_ar?: Record<string, unknown> | null;
    };
};

type CmsRendererProps = {
    sections: SectionRecord[];
    locale: 'en' | 'ar';
};

export function CmsRenderer({ sections, locale }: CmsRendererProps) {
    return (
        <>
            {sections.map((item) => {
                const section = item.section;
                const content =
                    (locale === 'ar' ? section?.content_ar : section?.content_en) ??
                    {};

                if (!section) {
                    return null;
                }

                if (section.section_type === 'hero') {
                    return (
                        <section key={item.id} className="pmc-hero p-5 position-relative mb-4">
                            <span className="pmc-hero-blob pmc-hero-blob--orange top-0 start-0 translate-middle p-5" />
                            <span className="pmc-hero-blob pmc-hero-blob--teal bottom-0 end-0 translate-middle p-5" />
                            <div className="position-relative">
                                <div className="pmc-kicker mb-3">Property control platform</div>
                                <h1 className="display-4 fw-bold mb-3">
                                    {String(content.headline ?? '')}
                                </h1>
                                <p className="lead text-secondary mb-4">
                                    {String(content.subheadline ?? '')}
                                </p>
                                <div className="d-flex flex-wrap gap-2">
                                    <a href="/login" className="btn btn-primary btn-lg">
                                        {String(content.ctaPrimary ?? 'Access dashboard')}
                                    </a>
                                    <a href="#overview" className="btn btn-outline-secondary btn-lg">
                                        {String(content.ctaSecondary ?? 'Learn more')}
                                    </a>
                                </div>
                            </div>
                        </section>
                    );
                }

                if (section.section_type === 'metrics') {
                    const items = Array.isArray(content.items)
                        ? (content.items as Array<Record<string, unknown>>)
                        : [];

                    return (
                        <section key={item.id} id="overview" className="mb-4">
                            <div className="row g-3">
                                {items.map((metric, index) => (
                                    <div key={`${item.id}-${index}`} className="col-md-4">
                                        <div className="pmc-card p-4 h-100">
                                            <div className="pmc-kicker mb-2">
                                                {String(metric.label ?? '')}
                                            </div>
                                            <div className="pmc-metric">
                                                {String(metric.value ?? '')}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    );
                }

                return (
                    <section key={item.id} className="pmc-card p-4 mb-4">
                        <pre className="mb-0 small text-secondary">
                            {JSON.stringify(content, null, 2)}
                        </pre>
                    </section>
                );
            })}
        </>
    );
}
