import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { OpeningDataPayload } from './types';

export function OpeningDataUpload({
    payload,
}: {
    payload: OpeningDataPayload;
}) {
    const { t } = useTranslator();
    const form = useForm<{
        portfolio_id: string;
        file: File | null;
    }>({
        portfolio_id:
            payload.portfolios.length === 1
                ? String(payload.portfolios[0].id)
                : '',
        file: null,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/opening-data/preview', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <section className="pmc-opening-card pmc-opening-upload">
            <header>
                <span className="pmc-opening-icon">
                    <i className="bi bi-cloud-arrow-up" aria-hidden="true" />
                </span>
                <div>
                    <h2>{t('opening_data.upload_title')}</h2>
                    <p>{t('opening_data.upload_description')}</p>
                </div>
            </header>

            {Object.keys(form.errors).length > 0 ? (
                <div className="pmc-opening-errors" role="alert">
                    <i
                        className="bi bi-exclamation-circle"
                        aria-hidden="true"
                    />
                    <ul>
                        {Object.values(form.errors).map((error) => (
                            <li key={String(error)}>{String(error)}</li>
                        ))}
                    </ul>
                </div>
            ) : null}

            <form onSubmit={submit}>
                <div className="pmc-opening-field">
                    <label htmlFor="opening-portfolio">
                        {t('opening_data.portfolio')}
                    </label>
                    <select
                        id="opening-portfolio"
                        className="form-select"
                        value={form.data.portfolio_id}
                        onChange={(event) =>
                            form.setData('portfolio_id', event.target.value)
                        }
                    >
                        <option value="">
                            {t('opening_data.select_portfolio')}
                        </option>
                        {payload.portfolios.map((portfolio) => (
                            <option key={portfolio.id} value={portfolio.id}>
                                {portfolio.name} · {portfolio.code}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="pmc-opening-field">
                    <label htmlFor="opening-file">
                        {t('opening_data.workbook')}
                    </label>
                    <input
                        id="opening-file"
                        className="visually-hidden"
                        type="file"
                        accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        onChange={(event) =>
                            form.setData(
                                'file',
                                event.target.files?.[0] ?? null,
                            )
                        }
                    />
                    <label
                        className="pmc-opening-file-picker"
                        htmlFor="opening-file"
                    >
                        <span>
                            <i
                                className="bi bi-file-earmark-excel"
                                aria-hidden="true"
                            />
                            {t('opening_data.choose_file')}
                        </span>
                        <strong>
                            {form.data.file?.name ??
                                t('opening_data.no_file_selected')}
                        </strong>
                    </label>
                    <small>
                        {t('opening_data.workbook_hint', undefined, {
                            size: payload.maxFileMegabytes,
                        })}
                    </small>
                </div>
                <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={
                        form.processing ||
                        !form.data.portfolio_id ||
                        !form.data.file
                    }
                >
                    <i className="bi bi-shield-check" aria-hidden="true" />
                    {t('opening_data.validate')}
                </button>
            </form>
        </section>
    );
}
