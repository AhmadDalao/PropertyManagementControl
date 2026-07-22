import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { TenantRecord } from './types';

export function TenantProfileCompleteness({
    tenant,
}: {
    tenant: TenantRecord;
}) {
    const { t } = useTranslator();
    const labels = {
        emergency_contact: t('tenants.emergency_contact'),
        address: t('tenants.address'),
        company_name: t('tenants.company_name'),
    };
    const missing = tenant.missing_profile_fields.map((field) => labels[field]);

    return missing.length === 0 ? (
        <StatusBadge value="complete" tone="success" />
    ) : (
        <StatusBadge
            value="incomplete"
            label={t('tenants.missing_fields', undefined, {
                fields: missing.join(', '),
            })}
            tone="warning"
        />
    );
}
