import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { OpeningDataIssues } from './opening-data-issues';
import { openingDataSheetOrder, sheetLabel } from './opening-data-labels';
import { OpeningDataSamples } from './opening-data-samples';
import type { OpeningDataPreview as Preview } from './types';

export function OpeningDataPreview({ preview }: { preview: Preview }) {
    const { locale, t } = useTranslator();
    const commit = useForm({
        preview_token: preview.token,
        confirmed: false,
    });
    const discard = useForm({
        preview_token: preview.token,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        commit.post('/opening-data/import', { preserveScroll: true });
    };

    return (
        <section className="pmc-opening-preview">
            <header className={preview.ready ? 'is-ready' : 'is-blocked'}>
                <span>
                    <i
                        className={`bi ${
                            preview.ready
                                ? 'bi-check2-circle'
                                : 'bi-exclamation-triangle'
                        }`}
                        aria-hidden="true"
                    />
                </span>
                <div>
                    <small>{t('opening_data.preview')}</small>
                    <h2>{t('opening_data.preview_title')}</h2>
                    <p>
                        {preview.ready
                            ? t('opening_data.ready_description')
                            : t('opening_data.blocked_description', undefined, {
                                  count: preview.issue_count,
                              })}
                    </p>
                </div>
                <strong>
                    {preview.ready
                        ? t('opening_data.ready')
                        : t('opening_data.blocked')}
                </strong>
            </header>

            <div className="pmc-opening-target">
                <div>
                    <span>{t('opening_data.portfolio')}</span>
                    <strong>
                        {locale === 'ar'
                            ? preview.portfolio.name_ar
                            : preview.portfolio.name_en}
                    </strong>
                    <small>{preview.portfolio.code}</small>
                </div>
                <small>
                    {t('opening_data.expires', undefined, {
                        time: new Intl.DateTimeFormat(locale, {
                            dateStyle: 'medium',
                            timeStyle: 'short',
                        }).format(new Date(preview.expires_at)),
                    })}
                </small>
            </div>

            <div className="pmc-opening-counts" role="list">
                {openingDataSheetOrder.map((sheet) => (
                    <article key={sheet} role="listitem">
                        <span>{sheetLabel(sheet, t)}</span>
                        <strong>
                            {(preview.counts[sheet] ?? 0).toLocaleString(
                                locale,
                            )}
                        </strong>
                    </article>
                ))}
            </div>

            {preview.issues.length > 0 ? (
                <OpeningDataIssues preview={preview} />
            ) : null}

            <OpeningDataSamples preview={preview} />

            <div className="pmc-opening-commit">
                {Object.keys(commit.errors).length > 0 ? (
                    <div className="pmc-opening-errors" role="alert">
                        <i
                            className="bi bi-exclamation-circle"
                            aria-hidden="true"
                        />
                        <ul>
                            {Object.values(commit.errors).map((error) => (
                                <li key={String(error)}>{String(error)}</li>
                            ))}
                        </ul>
                    </div>
                ) : null}

                {preview.ready ? (
                    <form onSubmit={submit}>
                        <label className="pmc-opening-confirm">
                            <input
                                type="checkbox"
                                checked={commit.data.confirmed}
                                onChange={(event) =>
                                    commit.setData(
                                        'confirmed',
                                        event.target.checked,
                                    )
                                }
                            />
                            <span>{t('opening_data.confirm_label')}</span>
                        </label>
                        <p>
                            <i
                                className="bi bi-exclamation-circle"
                                aria-hidden="true"
                            />
                            {t('opening_data.commit_warning')}
                        </p>
                        <button
                            type="submit"
                            className="btn btn-primary"
                            disabled={
                                commit.processing || !commit.data.confirmed
                            }
                        >
                            <i
                                className="bi bi-database-check"
                                aria-hidden="true"
                            />
                            {t('opening_data.import')}
                        </button>
                    </form>
                ) : null}

                <button
                    type="button"
                    className="btn btn-light"
                    disabled={discard.processing || commit.processing}
                    onClick={() =>
                        discard.delete('/opening-data/preview', {
                            preserveScroll: true,
                        })
                    }
                >
                    {t('opening_data.discard')}
                </button>
            </div>
        </section>
    );
}
