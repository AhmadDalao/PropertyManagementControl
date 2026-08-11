import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/media-detail.css';

import { StatusBadge } from '@/components/operations';
import { DetailCard } from '@/components/resource-cycle/detail-card';
import { HistoryTimeline } from '@/components/resource-cycle/history-timeline';
import { ResourceHeader } from '@/components/resource-cycle/resource-header';
import type { ResourceDetailShellProps } from '@/components/resource-cycle/types';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

type MediaDetailPageProps = SharedProps & {
    detailPage: ResourceDetailShellProps;
};

export default function MediaDetailPage() {
    const { props } = usePage<MediaDetailPageProps>();
    const { detailPage } = props;
    const { t } = useTranslator();
    const spotlight = detailPage.spotlight;

    return (
        <AdminLayout>
            <Head title={detailPage.header.title} />
            <ResourceHeader {...detailPage.header} />
            <div className="pmc-media-detail-layout">
                <div className="pmc-media-detail-main">
                    <section className="pmc-media-detail-preview">
                        <header>
                            <div>
                                <span>{spotlight?.eyebrow}</span>
                                <h2>{spotlight?.title}</h2>
                            </div>
                            {spotlight?.status ? (
                                <StatusBadge value={spotlight.status} />
                            ) : null}
                        </header>
                        {spotlight?.image ? (
                            <a
                                href={spotlight.image.src}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <img
                                    src={spotlight.image.src}
                                    alt={spotlight.image.alt}
                                />
                            </a>
                        ) : null}
                        <footer>
                            <p>{spotlight?.description}</p>
                            <a
                                href={spotlight?.image?.src}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <i className="bi bi-box-arrow-up-right" />
                                {t('media.open_image')}
                            </a>
                        </footer>
                    </section>
                    <HistoryTimeline timeline={detailPage.timeline ?? []} />
                </div>
                <aside className="pmc-media-detail-side">
                    {detailPage.stats?.length ? (
                        <dl className="pmc-media-detail-stats">
                            {detailPage.stats.map((stat) => (
                                <div key={stat.label}>
                                    <dt>{stat.label}</dt>
                                    <dd>{stat.value ?? '-'}</dd>
                                </div>
                            ))}
                        </dl>
                    ) : null}
                    {detailPage.sections?.map((section) => (
                        <DetailCard key={section.title} section={section} />
                    ))}
                </aside>
            </div>
        </AdminLayout>
    );
}
