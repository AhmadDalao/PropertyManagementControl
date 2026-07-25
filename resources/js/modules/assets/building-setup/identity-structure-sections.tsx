import { useTranslator } from '@/lib/i18n';

import { SetupField } from './setup-field';
import type {
    BuildingSetupErrors,
    BuildingSetupOption,
    BuildingSetupValues,
    SetBuildingSetupValue,
} from './types';

export function IdentityStructureSections({
    values,
    errors,
    options,
    limits,
    setValue,
    onPortfolioChange,
}: {
    values: BuildingSetupValues;
    errors: BuildingSetupErrors;
    options: {
        portfolios: BuildingSetupOption[];
        usages: BuildingSetupOption[];
        unitTypes: BuildingSetupOption[];
    };
    limits: {
        floors: number;
        unitsPerFloor: number;
        records: number;
    };
    setValue: SetBuildingSetupValue;
    onPortfolioChange: (value: string) => void;
}) {
    const { t } = useTranslator();

    return (
        <>
            <fieldset className="pmc-building-setup-section">
                <legend>{t('assets.builder.identity_title')}</legend>
                <p>{t('assets.builder.identity_help')}</p>
                <div className="pmc-building-setup-fields">
                    {options.portfolios.length > 1 ? (
                        <SetupField
                            name="portfolio_id"
                            label={t('assets.portfolio')}
                            value={values.portfolio_id}
                            error={errors.portfolio_id}
                            type="select"
                            required
                            options={options.portfolios}
                            onChange={(value) =>
                                onPortfolioChange(String(value))
                            }
                        />
                    ) : null}
                    <SetupField
                        name="title_en"
                        label={t('assets.builder.building_name_en')}
                        value={values.title_en}
                        error={errors.title_en}
                        required
                        onChange={(value) =>
                            setValue('title_en', String(value))
                        }
                    />
                    <SetupField
                        name="title_ar"
                        label={t('assets.builder.building_name_ar')}
                        value={values.title_ar}
                        error={errors.title_ar}
                        required
                        onChange={(value) =>
                            setValue('title_ar', String(value))
                        }
                    />
                    <SetupField
                        name="code_prefix"
                        label={t('assets.builder.code_prefix')}
                        value={values.code_prefix}
                        error={errors.code_prefix}
                        required
                        help={t('assets.builder.code_help')}
                        onChange={(value) =>
                            setValue('code_prefix', String(value).toUpperCase())
                        }
                    />
                    <SetupField
                        name="usage_type"
                        label={t('assets.usage_type')}
                        value={values.usage_type}
                        error={errors.usage_type}
                        type="select"
                        required
                        options={options.usages}
                        onChange={(value) =>
                            setValue('usage_type', String(value))
                        }
                    />
                </div>
            </fieldset>

            <fieldset className="pmc-building-setup-section">
                <legend>{t('assets.builder.structure_title')}</legend>
                <p>{t('assets.builder.structure_help')}</p>
                <div className="pmc-building-setup-fields">
                    <SetupField
                        name="floor_count"
                        label={t('assets.builder.floor_count')}
                        value={values.floor_count}
                        error={errors.floor_count}
                        type="number"
                        required
                        min={1}
                        max={limits.floors}
                        onChange={(value) => setValue('floor_count', value)}
                    />
                    <SetupField
                        name="units_per_floor"
                        label={t('assets.builder.units_per_floor')}
                        value={values.units_per_floor}
                        error={errors.units_per_floor}
                        type="number"
                        required
                        min={1}
                        max={limits.unitsPerFloor}
                        help={t('assets.builder.record_limit', undefined, {
                            limit: limits.records,
                        })}
                        onChange={(value) => setValue('units_per_floor', value)}
                    />
                    <SetupField
                        name="floor_start"
                        label={t('assets.builder.floor_start')}
                        value={values.floor_start}
                        error={errors.floor_start}
                        type="select"
                        required
                        options={[
                            {
                                value: '0',
                                label: t('assets.builder.ground_floor'),
                            },
                            {
                                value: '1',
                                label: t('assets.builder.first_floor'),
                            },
                        ]}
                        onChange={(value) =>
                            setValue('floor_start', Number(value))
                        }
                    />
                    <SetupField
                        name="unit_type"
                        label={t('assets.builder.unit_type')}
                        value={values.unit_type}
                        error={errors.unit_type}
                        type="select"
                        required
                        options={options.unitTypes}
                        onChange={(value) =>
                            setValue('unit_type', String(value))
                        }
                    />
                    <SetupField
                        name="unit_area"
                        label={t('assets.builder.unit_area')}
                        value={values.unit_area}
                        error={errors.unit_area}
                        type="number"
                        min={0}
                        step="0.01"
                        onChange={(value) => setValue('unit_area', value)}
                    />
                </div>
            </fieldset>
        </>
    );
}
