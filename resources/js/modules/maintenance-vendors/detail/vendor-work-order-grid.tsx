import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { VendorWorkOrder } from './types';
import { VendorWorkOrderCard } from './vendor-work-order-card';

export function VendorWorkOrderGrid({
    title,
    description,
    orders,
    allHref,
}: {
    title: string;
    description: string;
    orders: VendorWorkOrder[];
    allHref: string;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-vendor-job-section">
            <header>
                <div>
                    <h2>{title}</h2>
                    <p>{description}</p>
                </div>
                <Link href={allHref} className="btn btn-light">
                    {t('maintenance_vendors.view_all_work_orders')}
                </Link>
            </header>
            {orders.length > 0 ? (
                <div className="pmc-vendor-job-grid">
                    {orders.map((order) => (
                        <VendorWorkOrderCard order={order} key={order.id} />
                    ))}
                </div>
            ) : (
                <p className="pmc-vendor-job-empty">
                    {t('maintenance_vendors.no_work_orders')}
                </p>
            )}
        </section>
    );
}
