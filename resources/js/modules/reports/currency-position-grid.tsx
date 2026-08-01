import { useTranslator } from '@/lib/i18n';
import { currency, percent } from '@/lib/utils';

import type { CurrencyPosition } from './types';

export function CurrencyPositionGrid({
    positions,
}: {
    positions: CurrencyPosition[];
}) {
    const { locale, t } = useTranslator();

    return (
        <section
            className="pmc-report-currency-section"
            aria-label={t('reports.currency_positions')}
        >
            <header>
                <div>
                    <span>{t('reports.financial_position')}</span>
                    <h2>{t('reports.currency_positions')}</h2>
                </div>
                <p>{t('reports.currency_positions_help')}</p>
            </header>

            <div className="pmc-report-currency-grid">
                {positions.map((position) => (
                    <article
                        key={position.currency}
                        className={
                            position.net < 0 ? 'is-negative' : 'is-positive'
                        }
                    >
                        <header>
                            <span>{t('reports.currency')}</span>
                            <strong>{position.currency}</strong>
                            <em>{percent(position.collectionRate, locale)}</em>
                        </header>
                        <dl>
                            <CurrencyValue
                                label={t('reports.collected')}
                                value={currency(
                                    position.revenue,
                                    locale,
                                    position.currency,
                                )}
                            />
                            <CurrencyValue
                                label={t('reports.expenses')}
                                value={currency(
                                    position.expenses,
                                    locale,
                                    position.currency,
                                )}
                            />
                            <CurrencyValue
                                label={t('reports.net_position')}
                                value={currency(
                                    position.net,
                                    locale,
                                    position.currency,
                                )}
                            />
                            <CurrencyValue
                                label={t('reports.arrears')}
                                value={currency(
                                    position.arrears,
                                    locale,
                                    position.currency,
                                )}
                            />
                        </dl>
                        <footer>
                            <span>
                                {t('reports.scheduled_paid', undefined, {
                                    paid: currency(
                                        position.scheduledPaid,
                                        locale,
                                        position.currency,
                                    ),
                                    due: currency(
                                        position.scheduledDue,
                                        locale,
                                        position.currency,
                                    ),
                                })}
                            </span>
                            <strong>
                                {t('reports.contract_balance', undefined, {
                                    amount: currency(
                                        position.contractBalance,
                                        locale,
                                        position.currency,
                                    ),
                                })}
                            </strong>
                        </footer>
                    </article>
                ))}
            </div>
        </section>
    );
}

function CurrencyValue({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt>{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}
