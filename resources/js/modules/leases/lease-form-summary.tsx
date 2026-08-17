import type { ResourceField } from '@/components/resource-cycle';
import type { ResourceFormValues } from '@/components/resource-cycle/types';
import { useTranslator } from '@/lib/i18n';

export function LeaseFormSummary({
    fields,
    values,
}: {
    fields: ResourceField[];
    values: ResourceFormValues;
}) {
    const { locale, t, text } = useTranslator();
    const rows = [
        summaryChoice('tenant_profile_id', 'leases.summary_tenant'),
        summaryChoice('asset_id', 'leases.summary_asset'),
        summaryPeriod(),
        summaryRent(),
        summaryChoice('status', 'leases.summary_status'),
        summaryValue('renewal_notice_days', 'leases.summary_notice'),
    ].filter((row): row is SummaryRow => row !== null);

    return (
        <dl className="pmc-lease-form-summary">
            {rows.map((row) => (
                <div key={row.label}>
                    <dt>{t(row.label)}</dt>
                    <dd>{text(row.value)}</dd>
                </div>
            ))}
        </dl>
    );

    function summaryChoice(
        name: string,
        label: SummaryRow['label'],
    ): SummaryRow | null {
        const field = fields.find((candidate) => candidate.name === name);

        if (!field) {
            return null;
        }

        const option = field.options?.find(
            (candidate) =>
                String(candidate.value) === String(values[name] ?? ''),
        );

        return {
            label,
            value:
                option && String(option.value) !== ''
                    ? option.label
                    : t('leases.not_selected'),
        };
    }

    function summaryPeriod(): SummaryRow | null {
        if (!fields.some((field) => field.name === 'started_at')) {
            return null;
        }

        const start = String(values.started_at ?? '');
        const end = String(values.ends_at ?? '');

        return {
            label: 'leases.summary_period',
            value:
                start && end
                    ? t('leases.summary_period_value', undefined, {
                          start: formatDate(start),
                          end: formatDate(end),
                      })
                    : t('leases.not_selected'),
        };
    }

    function summaryRent(): SummaryRow | null {
        if (!fields.some((field) => field.name === 'rent_amount')) {
            return null;
        }

        const amount = values.rent_amount;
        const numeric = amount === '' ? Number.NaN : Number(amount);

        return {
            label: 'leases.summary_rent',
            value: Number.isFinite(numeric)
                ? `${new Intl.NumberFormat(locale).format(numeric)} ${String(values.currency ?? '')}`.trim()
                : t('leases.not_selected'),
        };
    }

    function summaryValue(
        name: string,
        label: SummaryRow['label'],
    ): SummaryRow | null {
        if (!fields.some((field) => field.name === name)) {
            return null;
        }

        const value = values[name];

        return {
            label,
            value:
                value === '' || value === null || value === undefined
                    ? t('leases.not_selected')
                    : t('leases.summary_notice_value', undefined, {
                          count: String(value),
                      }),
        };
    }

    function formatDate(value: string): string {
        const date = new Date(`${value}T00:00:00`);

        return Number.isNaN(date.getTime())
            ? value
            : new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(
                  date,
              );
    }
}

type SummaryRow = {
    label:
        | 'leases.summary_tenant'
        | 'leases.summary_asset'
        | 'leases.summary_period'
        | 'leases.summary_rent'
        | 'leases.summary_status'
        | 'leases.summary_notice';
    value: string;
};
