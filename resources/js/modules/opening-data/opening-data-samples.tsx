import { useTranslator } from '@/lib/i18n';

import {
    fieldLabel,
    openingDataSheetOrder,
    sheetLabel,
} from './opening-data-labels';
import type { OpeningDataPreview } from './types';

export function OpeningDataSamples({
    preview,
}: {
    preview: OpeningDataPreview;
}) {
    const { t } = useTranslator();

    return (
        <details className="pmc-opening-samples">
            <summary>
                <span>
                    <i className="bi bi-card-list" aria-hidden="true" />
                    <strong>{t('opening_data.samples')}</strong>
                    <small>{t('opening_data.sample_description')}</small>
                </span>
                <i className="bi bi-chevron-down" aria-hidden="true" />
            </summary>
            <div>
                {openingDataSheetOrder.map((sheet) => (
                    <section key={sheet}>
                        <h3>{sheetLabel(sheet, t)}</h3>
                        {(preview.samples[sheet] ?? []).length === 0 ? (
                            <p>{t('opening_data.no_rows')}</p>
                        ) : (
                            <div className="pmc-opening-sample-grid">
                                {(preview.samples[sheet] ?? []).map(
                                    (row, rowIndex) => (
                                        <dl key={`${sheet}-${rowIndex}`}>
                                            {Object.entries(row)
                                                .filter(
                                                    ([field, value]) =>
                                                        field !== '_row' &&
                                                        value !== null &&
                                                        value !== '',
                                                )
                                                .slice(0, 6)
                                                .map(([field, value]) => (
                                                    <div key={field}>
                                                        <dt>
                                                            {fieldLabel(
                                                                field,
                                                                t,
                                                            )}
                                                        </dt>
                                                        <dd>{String(value)}</dd>
                                                    </div>
                                                ))}
                                        </dl>
                                    ),
                                )}
                            </div>
                        )}
                    </section>
                ))}
            </div>
        </details>
    );
}
