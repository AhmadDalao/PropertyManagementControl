import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { VendorWorkOrder } from './types';

export function VendorWorkOrderCard({ order }: { order: VendorWorkOrder }) {
    const { t } = useTranslator();

    return (
        <article className="pmc-vendor-job-card">
            <header>
                <div>
                    <small>{order.request}</small>
                    <h3>
                        <Link href={order.href}>{order.reference}</Link>
                    </h3>
                </div>
                <span className={`is-${order.statusTone}`}>{order.status}</span>
            </header>

            <div className="pmc-vendor-job-property">
                <span aria-hidden="true">
                    <i className="bi bi-buildings" />
                </span>
                <div>
                    <strong>{order.property}</strong>
                    <small>
                        {[order.propertyCode, order.tenant]
                            .filter(Boolean)
                            .join(' · ')}
                    </small>
                </div>
            </div>

            <dl>
                <div>
                    <dt>{t('maintenance_vendors.schedule')}</dt>
                    <dd className={`is-${order.scheduleTone}`}>
                        {order.schedule}
                        {order.scheduledAt ? (
                            <small>{order.scheduledAt}</small>
                        ) : null}
                    </dd>
                </div>
                <div>
                    <dt>{t('maintenance_vendors.internal_owner')}</dt>
                    <dd>{order.assignedTo}</dd>
                </div>
                <div>
                    <dt>{t('maintenance_vendors.quoted')}</dt>
                    <dd>{order.estimated ?? '-'}</dd>
                </div>
                <div>
                    <dt>{t('maintenance_vendors.final')}</dt>
                    <dd>{order.final ?? '-'}</dd>
                </div>
            </dl>

            {order.scope ? <p>{order.scope}</p> : null}

            <footer>
                <Link href={order.href} className="btn btn-outline-secondary">
                    {t('maintenance_vendors.open_job')}
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                </Link>
            </footer>
        </article>
    );
}
