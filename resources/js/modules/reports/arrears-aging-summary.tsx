import { useTranslator } from '@/lib/i18n';
import { currency, localizedNumber } from '@/lib/utils';

import type { ArrearsCurrencyPosition } from './arrears-aging-types';

export function ArrearsAgingSummary({
    positions,
}: {
    positions: ArrearsCurrencyPosition[];
}) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-aging-summary">
            <header>
                <div>
                    <span>{t('reports.financial_position')}</span>
                    <h2>{t('reports.aging_currency_title')}</h2>
                    <p>{t('reports.aging_currency_help')}</p>
                </div>
            </header>
            {positions.length > 0 ? (
                <div className="pmc-aging-currency-grid">
                    {positions.map((position) => (
                        <article key={position.currency}>
                            <header>
                                <span>{position.currency}</span>
                                <strong>
                                    {currency(
                                        position.total,
                                        locale,
                                        position.currency,
                                    )}
                                </strong>
                                <small>
                                    {t(
                                        'reports.aging_currency_counts',
                                        undefined,
                                        {
                                            installments: localizedNumber(
                                                position.installment_count,
                                                locale,
                                            ),
                                            leases: localizedNumber(
                                                position.lease_count,
                                                locale,
                                            ),
                                        },
                                    )}
                                </small>
                            </header>
                            <dl>
                                {bucketKeys.map((bucket) => (
                                    <div key={bucket}>
                                        <dt>
                                            {t(
                                                `reports.aging_bucket_${bucket}`,
                                            )}
                                        </dt>
                                        <dd>
                                            {currency(
                                                position[bucket],
                                                locale,
                                                position.currency,
                                            )}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </article>
                    ))}
                </div>
            ) : (
                <div className="pmc-aging-empty">
                    <i className="bi bi-check2-circle" aria-hidden="true" />
                    <span>{t('reports.aging_no_positions')}</span>
                </div>
            )}
        </section>
    );
}

const bucketKeys = [
    'days_1_30',
    'days_31_60',
    'days_61_90',
    'over_90',
] as const;
