import { useTranslator } from '@/lib/i18n';

import { SetupField } from './setup-field';
import type {
    BuildingSetupErrors,
    BuildingSetupOption,
    BuildingSetupValues,
    SetBuildingSetupValue,
} from './types';

export function AssignmentLocationSections({
    values,
    errors,
    owners,
    managers,
    setValue,
}: {
    values: BuildingSetupValues;
    errors: BuildingSetupErrors;
    owners: BuildingSetupOption[];
    managers: BuildingSetupOption[];
    setValue: SetBuildingSetupValue;
}) {
    const { t } = useTranslator();

    return (
        <>
            <fieldset className="pmc-building-setup-section">
                <legend>{t('assets.builder.responsibility_title')}</legend>
                <p>{t('assets.builder.responsibility_help')}</p>
                <div className="pmc-building-setup-fields">
                    <SetupField
                        name="primary_owner_user_id"
                        label={t('assets.primary_owner')}
                        value={values.primary_owner_user_id}
                        error={errors.primary_owner_user_id}
                        type="select"
                        required
                        options={owners}
                        onChange={(value) =>
                            setValue('primary_owner_user_id', String(value))
                        }
                    />
                    <SetupField
                        name="primary_manager_user_id"
                        label={t('assets.primary_manager')}
                        value={values.primary_manager_user_id}
                        error={errors.primary_manager_user_id}
                        type="select"
                        required
                        options={managers}
                        onChange={(value) =>
                            setValue('primary_manager_user_id', String(value))
                        }
                    />
                </div>
            </fieldset>

            <fieldset className="pmc-building-setup-section">
                <legend>{t('assets.builder.location_title')}</legend>
                <p>{t('assets.builder.location_help')}</p>
                <div className="pmc-building-setup-fields">
                    <SetupField
                        name="valuation_amount"
                        label={t('assets.valuation')}
                        value={values.valuation_amount}
                        error={errors.valuation_amount}
                        type="number"
                        min={0}
                        step="0.01"
                        onChange={(value) =>
                            setValue('valuation_amount', value)
                        }
                    />
                    <SetupField
                        name="currency"
                        label={t('assets.currency')}
                        value={values.currency}
                        error={errors.currency}
                        required
                        onChange={(value) =>
                            setValue('currency', String(value).toUpperCase())
                        }
                    />
                    <SetupField
                        name="area"
                        label={t('assets.area')}
                        value={values.area}
                        error={errors.area}
                        type="number"
                        min={0}
                        step="0.01"
                        onChange={(value) => setValue('area', value)}
                    />
                    <SetupField
                        name="land_number"
                        label={t('assets.land_number')}
                        value={values.land_number}
                        error={errors.land_number}
                        onChange={(value) =>
                            setValue('land_number', String(value))
                        }
                    />
                    <SetupField
                        name="address"
                        label={t('fields.address_en')}
                        value={values.address}
                        error={errors.address}
                        type="textarea"
                        onChange={(value) => setValue('address', String(value))}
                    />
                    <SetupField
                        name="address_ar"
                        label={t('fields.address_ar')}
                        value={values.address_ar}
                        error={errors.address_ar}
                        type="textarea"
                        onChange={(value) =>
                            setValue('address_ar', String(value))
                        }
                    />
                    <SetupField
                        name="map_zone_en"
                        label={t('fields.zone_en')}
                        value={values.map_zone_en}
                        error={errors.map_zone_en}
                        onChange={(value) =>
                            setValue('map_zone_en', String(value))
                        }
                    />
                    <SetupField
                        name="map_zone_ar"
                        label={t('fields.zone_ar')}
                        value={values.map_zone_ar}
                        error={errors.map_zone_ar}
                        onChange={(value) =>
                            setValue('map_zone_ar', String(value))
                        }
                    />
                    <SetupField
                        name="latitude"
                        label={t('assets.latitude')}
                        value={values.latitude}
                        error={errors.latitude}
                        type="number"
                        min={-90}
                        max={90}
                        step="0.000001"
                        onChange={(value) => setValue('latitude', value)}
                    />
                    <SetupField
                        name="longitude"
                        label={t('assets.longitude')}
                        value={values.longitude}
                        error={errors.longitude}
                        type="number"
                        min={-180}
                        max={180}
                        step="0.000001"
                        onChange={(value) => setValue('longitude', value)}
                    />
                </div>
            </fieldset>
        </>
    );
}
