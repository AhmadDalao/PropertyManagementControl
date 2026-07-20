import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';
import type { SharedProps } from '@/types';

import '../../../css/styles/showcase-data.css';

type ShowcaseDataset = {
    id: number;
    key: string;
    name: string;
    status: string;
    target_properties: number;
    generated_properties: number;
    progress_percent: number;
    counts: Record<string, number>;
    failure_details?: string | null;
    initiated_by?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    purged_at?: string | null;
};

type PageProps = SharedProps & {
    datasets: ShowcaseDataset[];
    targets: Record<string, number>;
};

export default function ShowcaseDataIndexPage() {
    const { props } = usePage<PageProps>();
    const { locale, t } = useTranslator();
    const [purgeDataset, setPurgeDataset] = useState<ShowcaseDataset | null>(
        null,
    );
    const active = props.datasets.some((dataset) =>
        ['queued', 'generating', 'purging'].includes(dataset.status),
    );

    useEffect(() => {
        if (!active) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({
                only: ['datasets'],
            });
        }, 5000);

        return () => window.clearInterval(timer);
    }, [active]);

    return (
        <AdminLayout>
            <Head title={t('showcase.title')} />
            <WorkspaceHeader
                eyebrow={t('showcase.system_control')}
                title={t('showcase.title')}
                description={t('showcase.description')}
                actions={[
                    {
                        label: t('showcase.refresh'),
                        href: '/system/showcase-data',
                        icon: 'bi-arrow-clockwise',
                    },
                ]}
            />

            <section className="pmc-showcase-warning">
                <span>
                    <i className="bi bi-exclamation-triangle" />
                </span>
                <div>
                    <strong>{t('showcase.warning_title')}</strong>
                    <p>{t('showcase.warning_description')}</p>
                </div>
                <button
                    type="button"
                    className="btn btn-primary"
                    disabled={active}
                    onClick={() => router.post('/system/showcase-data')}
                >
                    <i className="bi bi-database-add" />
                    {t('showcase.generate')}
                </button>
            </section>

            <div className="pmc-showcase-targets">
                {Object.entries(props.targets).map(([key, value]) => (
                    <article key={key}>
                        <span>{targetLabel(key, t)}</span>
                        <strong>{value.toLocaleString(locale)}</strong>
                    </article>
                ))}
            </div>

            {props.datasets.length > 0 ? (
                <div className="pmc-showcase-datasets">
                    {props.datasets.map((dataset) => (
                        <article key={dataset.id}>
                            <header>
                                <div>
                                    <span>{dataset.key}</span>
                                    <h2>{dataset.name}</h2>
                                    <p>
                                        {dataset.initiated_by
                                            ? t(
                                                  'showcase.initiated_by',
                                                  undefined,
                                                  {
                                                      name: dataset.initiated_by,
                                                  },
                                              )
                                            : t('showcase.system_control')}
                                        {dataset.started_at
                                            ? ` · ${dateTime(dataset.started_at, locale)}`
                                            : ''}
                                    </p>
                                </div>
                                <span
                                    className={`pmc-showcase-status is-${dataset.status}`}
                                >
                                    {t(
                                        `status.${dataset.status}`,
                                        dataset.status,
                                    )}
                                </span>
                            </header>

                            <div className="pmc-showcase-progress">
                                <div>
                                    <strong>
                                        {t('showcase.progress', undefined, {
                                            generated:
                                                dataset.generated_properties,
                                            target: dataset.target_properties,
                                        })}
                                    </strong>
                                    <span>{dataset.progress_percent}%</span>
                                </div>
                                <div
                                    className="progress"
                                    role="progressbar"
                                    aria-label={t(
                                        'showcase.progress',
                                        undefined,
                                        {
                                            generated:
                                                dataset.generated_properties,
                                            target: dataset.target_properties,
                                        },
                                    )}
                                    aria-valuenow={dataset.progress_percent}
                                    aria-valuemin={0}
                                    aria-valuemax={100}
                                >
                                    <div
                                        className="progress-bar"
                                        style={{
                                            width: `${dataset.progress_percent}%`,
                                        }}
                                    />
                                </div>
                            </div>

                            <div className="pmc-showcase-counts">
                                {Object.entries(dataset.counts).map(
                                    ([key, value]) => (
                                        <div key={key}>
                                            <span>{targetLabel(key, t)}</span>
                                            <strong>
                                                {value.toLocaleString(locale)}
                                            </strong>
                                        </div>
                                    ),
                                )}
                            </div>

                            {dataset.failure_details ? (
                                <pre className="pmc-showcase-failure">
                                    {dataset.failure_details}
                                </pre>
                            ) : null}

                            {dataset.status !== 'purged' ? (
                                <footer>
                                    {dataset.status === 'failed' ||
                                    (dataset.status === 'complete' &&
                                        dataset.generated_properties <
                                            dataset.target_properties) ? (
                                        <button
                                            type="button"
                                            className="btn btn-outline-secondary"
                                            onClick={() =>
                                                router.post(
                                                    `/system/showcase-data/${dataset.id}/retry`,
                                                )
                                            }
                                        >
                                            {t('showcase.retry')}
                                        </button>
                                    ) : (
                                        <span />
                                    )}
                                    <button
                                        type="button"
                                        className="btn btn-outline-danger"
                                        onClick={() => setPurgeDataset(dataset)}
                                    >
                                        {t('showcase.purge')}
                                    </button>
                                </footer>
                            ) : null}
                        </article>
                    ))}
                </div>
            ) : (
                <section className="pmc-showcase-empty">
                    <i className="bi bi-database" />
                    <strong>{t('showcase.no_datasets')}</strong>
                    <p>{t('showcase.no_datasets_description')}</p>
                </section>
            )}

            {purgeDataset ? (
                <PurgeDialog
                    dataset={purgeDataset}
                    onClose={() => setPurgeDataset(null)}
                />
            ) : null}
        </AdminLayout>
    );
}

function PurgeDialog({
    dataset,
    onClose,
}: {
    dataset: ShowcaseDataset;
    onClose: () => void;
}) {
    const { t } = useTranslator();
    const form = useForm({ confirmation: '' });
    const required = t('showcase.confirmation');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.delete(`/system/showcase-data/${dataset.id}`, {
            onSuccess: onClose,
        });
    };

    return (
        <div className="pmc-showcase-dialog-layer">
            <button
                type="button"
                className="pmc-showcase-dialog-backdrop"
                onClick={onClose}
                aria-label={t('actions.close')}
            />
            <form
                className="pmc-showcase-dialog"
                role="dialog"
                aria-modal="true"
                onSubmit={submit}
            >
                <header>
                    <div>
                        <span>{dataset.key}</span>
                        <h2>{t('showcase.purge_title')}</h2>
                    </div>
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary"
                        onClick={onClose}
                    >
                        <i className="bi bi-x-lg" />
                    </button>
                </header>
                <p>
                    {t('showcase.purge_description', undefined, {
                        confirmation: required,
                    })}
                </p>
                <label>
                    <span>{t('showcase.confirmation_label')}</span>
                    <input
                        type="text"
                        className="form-control"
                        value={form.data.confirmation}
                        onChange={(event) =>
                            form.setData(
                                'confirmation',
                                event.currentTarget.value,
                            )
                        }
                        autoComplete="off"
                    />
                </label>
                {form.errors.confirmation ? (
                    <small className="text-danger">
                        {form.errors.confirmation}
                    </small>
                ) : null}
                <footer>
                    <button
                        type="button"
                        className="btn btn-outline-secondary"
                        onClick={onClose}
                    >
                        {t('actions.cancel')}
                    </button>
                    <button
                        type="submit"
                        className="btn btn-danger"
                        disabled={
                            form.processing ||
                            form.data.confirmation.trim() !== required
                        }
                    >
                        {t('showcase.purge')}
                    </button>
                </footer>
            </form>
        </div>
    );
}

function targetLabel(
    key: string,
    t: ReturnType<typeof useTranslator>['t'],
): string {
    return t(`showcase.${key}`, key.replaceAll('_', ' '));
}
