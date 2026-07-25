import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { AssignmentLocationSections } from './assignment-location-sections';
import { BuildingSetupPreview } from './building-setup-preview';
import { IdentityStructureSections } from './identity-structure-sections';
import { structureTotals } from './structure-plan';
import type {
    BuildingSetupErrors,
    BuildingSetupPayload,
    BuildingSetupValues,
    SetBuildingSetupValue,
} from './types';

export function BuildingSetupForm({
    payload,
}: {
    payload: BuildingSetupPayload;
}) {
    const { t } = useTranslator();
    const form = useForm<BuildingSetupValues>(payload.initialValues);
    const errors = form.errors as BuildingSetupErrors;
    const totals = structureTotals(form.data);
    const exceedsLimit = totals.total > payload.limits.records;
    const errorMessages = Object.values(form.errors).filter(Boolean);

    const setValue: SetBuildingSetupValue = (field, value) => {
        form.setData({
            ...form.data,
            [field]: value,
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!exceedsLimit) {
            form.post(payload.action, { preserveScroll: true });
        }
    };

    const changePortfolio = (portfolioId: string) => {
        router.get(
            window.location.pathname,
            { portfolio_id: portfolioId },
            {
                preserveScroll: true,
                preserveState: false,
                replace: true,
            },
        );
    };

    return (
        <div className="pmc-building-setup-layout">
            <form className="pmc-building-setup-form" onSubmit={submit}>
                {errorMessages.length > 0 ? (
                    <div
                        className="pmc-form-error-summary"
                        role="alert"
                        aria-live="assertive"
                    >
                        <i className="bi bi-exclamation-circle" />
                        <div>
                            <strong>{t('resource.validation_title')}</strong>
                            <ul>
                                {errorMessages.map((error) => (
                                    <li key={String(error)}>{String(error)}</li>
                                ))}
                            </ul>
                        </div>
                    </div>
                ) : null}

                <IdentityStructureSections
                    values={form.data}
                    errors={errors}
                    options={payload.options}
                    limits={payload.limits}
                    setValue={setValue}
                    onPortfolioChange={changePortfolio}
                />
                <AssignmentLocationSections
                    values={form.data}
                    errors={errors}
                    owners={payload.options.owners}
                    managers={payload.options.managers}
                    setValue={setValue}
                />

                <div className="pmc-building-setup-actions">
                    <Link href={payload.backHref} className="btn btn-light">
                        {t('actions.cancel')}
                    </Link>
                    <button
                        className="btn btn-primary"
                        disabled={form.processing || exceedsLimit}
                    >
                        <i className="bi bi-buildings" />
                        <span>{payload.submitLabel}</span>
                    </button>
                </div>
            </form>

            <BuildingSetupPreview
                values={form.data}
                recordLimit={payload.limits.records}
            />
        </div>
    );
}
