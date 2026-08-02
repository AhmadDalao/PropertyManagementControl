import { useTranslator } from '@/lib/i18n';

import { sheetLabel } from './opening-data-labels';
import type { OpeningDataPayload } from './types';

export function OpeningDataGuidance({
    payload,
}: {
    payload: OpeningDataPayload;
}) {
    const { locale, t } = useTranslator();

    return (
        <aside className="pmc-opening-guidance">
            <article>
                <i className="bi bi-lock" aria-hidden="true" />
                <div>
                    <strong>{t('opening_data.secure_title')}</strong>
                    <p>{t('opening_data.secure_description')}</p>
                </div>
            </article>
            <article>
                <i className="bi bi-person-lock" aria-hidden="true" />
                <div>
                    <strong>{t('opening_data.portal_title')}</strong>
                    <p>{t('opening_data.portal_description')}</p>
                </div>
            </article>
            <article>
                <i className="bi bi-table" aria-hidden="true" />
                <div>
                    <strong>{t('opening_data.limits_title')}</strong>
                    <p>{t('opening_data.limits_description')}</p>
                    <ul>
                        {Object.entries(payload.limits).map(
                            ([sheet, count]) => (
                                <li key={sheet}>
                                    {t(
                                        'opening_data.row_limit_label',
                                        undefined,
                                        {
                                            sheet: sheetLabel(sheet, t),
                                            count: count.toLocaleString(locale),
                                        },
                                    )}
                                </li>
                            ),
                        )}
                    </ul>
                </div>
            </article>
        </aside>
    );
}
