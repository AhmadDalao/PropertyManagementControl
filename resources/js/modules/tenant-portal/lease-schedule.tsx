import { TablePagination } from '@/components/data-table/table-pagination';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { TenantInstallment, TenantLeasePageProps } from './types';

export function LeaseSchedule({ props }: { props: TenantLeasePageProps }) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-portal-panel">
            <header>
                <div>
                    <span>{t('tenant_portal.financial')}</span>
                    <h2>{t('tenant_portal.installment_schedule')}</h2>
                </div>
                <small>
                    {t('tenant_portal.installment_count', undefined, {
                        count: props.lease?.installment_count ?? 0,
                    })}
                </small>
            </header>
            <div className="pmc-portal-table pmc-portal-table-desktop">
                <table>
                    <thead>
                        <tr>
                            <th>{t('tenant_portal.installment')}</th>
                            <th>{t('tenant_portal.due_date')}</th>
                            <th>{t('tenant_portal.amount')}</th>
                            <th>{t('tenant_portal.paid')}</th>
                            <th>{t('tenant_portal.remaining')}</th>
                            <th>{t('tenant_portal.status')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {props.schedule.data.map((item) => (
                            <ScheduleCells
                                item={item}
                                locale={locale}
                                currencyCode={props.lease?.currency ?? 'SAR'}
                                key={item.id}
                            />
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="pmc-portal-records">
                {props.schedule.data.map((item) => (
                    <article key={item.id}>
                        <header>
                            <strong>{item.label}</strong>
                            <StatusBadge value={item.status} />
                        </header>
                        <dl>
                            <div>
                                <dt>{t('tenant_portal.due_date')}</dt>
                                <dd>{humanDate(item.due_date, locale)}</dd>
                            </div>
                            <div>
                                <dt>{t('tenant_portal.amount')}</dt>
                                <dd>
                                    {currency(
                                        item.amount_due,
                                        locale,
                                        props.lease?.currency,
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt>{t('tenant_portal.remaining')}</dt>
                                <dd>
                                    {currency(
                                        item.remaining,
                                        locale,
                                        props.lease?.currency,
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </article>
                ))}
            </div>
            <TablePagination data={props.schedule} />
        </section>
    );
}

function ScheduleCells({
    item,
    locale,
    currencyCode,
}: {
    item: TenantInstallment;
    locale: string;
    currencyCode: string;
}) {
    return (
        <tr>
            <td>
                <strong>{item.label}</strong>
            </td>
            <td>{humanDate(item.due_date, locale)}</td>
            <td>{currency(item.amount_due, locale, currencyCode)}</td>
            <td>{currency(item.amount_paid, locale, currencyCode)}</td>
            <td>{currency(item.remaining, locale, currencyCode)}</td>
            <td>
                <StatusBadge value={item.status} />
            </td>
        </tr>
    );
}
