import { useTranslator } from '@/lib/i18n';

import { DatasetCard } from './dataset-card';
import { DatasetPagination } from './dataset-pagination';
import type { ShowcaseDataset, ShowcaseDataPageProps } from './types';

export function DatasetHistory({
    datasets,
    busyAction,
    onRetry,
    onPurge,
}: {
    datasets: ShowcaseDataPageProps['datasets'];
    busyAction: string | null;
    onRetry: (dataset: ShowcaseDataset) => void;
    onPurge: (dataset: ShowcaseDataset) => void;
}) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-showcase-history">
            <header>
                <div>
                    <span>{t('showcase.datasets')}</span>
                    <h2>{t('showcase.dataset_history')}</h2>
                    <p>{t('showcase.dataset_history_description')}</p>
                </div>
                <strong>{datasets.total.toLocaleString(locale)}</strong>
            </header>

            {datasets.data.length > 0 ? (
                <>
                    <div className="pmc-showcase-datasets">
                        {datasets.data.map((dataset) => (
                            <DatasetCard
                                key={dataset.id}
                                dataset={dataset}
                                busy={busyAction !== null}
                                onRetry={onRetry}
                                onPurge={onPurge}
                            />
                        ))}
                    </div>
                    <DatasetPagination datasets={datasets} />
                </>
            ) : (
                <div className="pmc-showcase-empty">
                    <i className="bi bi-database" aria-hidden="true" />
                    <strong>{t('showcase.no_datasets')}</strong>
                    <p>{t('showcase.no_datasets_description')}</p>
                </div>
            )}
        </section>
    );
}
