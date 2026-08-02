import { useTranslator } from '@/lib/i18n';
import { currency, localizedNumber } from '@/lib/utils';

import type { RentRollCurrencyPosition } from './rent-roll-types';

export function RentRollFinancials({
    positions,
}: {
    positions: RentRollCurrencyPosition[];
}) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-rent-roll-financials">
            <header>
                <div>
                    <span>{t('reports.financial_position')}</span>
                    <h2>{t('reports.rent_roll_currency_title')}</h2>
                    <p>{t('reports.rent_roll_currency_help')}</p>
                </div>
            </header>

            {positions.length > 0 ? (
                <div className="pmc-rent-roll-currency-grid">
                    {positions.map((position) => (
                        <article key={position.currency}>
                            <header>
                                <span>{position.currency}</span>
                                <strong>
                                    {t(
                                        'reports.rent_roll_active_leases',
                                        undefined,
                                        {
                                            count: localizedNumber(
                                                position.active_leases,
                                                locale,
                                            ),
                                        },
                                    )}
                                </strong>
                            </header>
                            <dl>
                                <Position
                                    label={t('reports.rent_roll_contracted')}
                                    value={currency(
                                        position.contracted,
                                        locale,
                                        position.currency,
                                    )}
                                />
                                <Position
                                    label={t('reports.rent_roll_paid')}
                                    value={currency(
                                        position.paid,
                                        locale,
                                        position.currency,
                                    )}
                                    tone="good"
                                />
                                <Position
                                    label={t('reports.rent_roll_outstanding')}
                                    value={currency(
                                        position.outstanding,
                                        locale,
                                        position.currency,
                                    )}
                                />
                                <Position
                                    label={t('reports.rent_roll_overdue')}
                                    value={currency(
                                        position.overdue,
                                        locale,
                                        position.currency,
                                    )}
                                    tone={
                                        position.overdue > 0 ? 'risk' : 'good'
                                    }
                                />
                                <Position
                                    label={t('reports.rent_roll_deposits')}
                                    value={currency(
                                        position.deposits,
                                        locale,
                                        position.currency,
                                    )}
                                />
                            </dl>
                        </article>
                    ))}
                </div>
            ) : (
                <div className="pmc-rent-roll-financial-empty">
                    <i className="bi bi-wallet2" aria-hidden="true" />
                    <span>{t('reports.rent_roll_no_currency_positions')}</span>
                </div>
            )}
        </section>
    );
}

function Position({
    label,
    value,
    tone,
}: {
    label: string;
    value: string;
    tone?: 'good' | 'risk';
}) {
    return (
        <div className={tone ? `is-${tone}` : undefined}>
            <dt>{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}
