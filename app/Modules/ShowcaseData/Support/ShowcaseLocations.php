<?php

namespace App\Modules\ShowcaseData\Support;

class ShowcaseLocations
{
    /**
     * @return list<array{
     *     city_en:string,
     *     city_ar:string,
     *     zone_en:string,
     *     zone_ar:string,
     *     address_en:string,
     *     address_ar:string,
     *     land_prefix:string,
     *     latitude:float,
     *     longitude:float
     * }>
     */
    public function all(): array
    {
        return [
            [
                'city_en' => 'Riyadh',
                'city_ar' => 'الرياض',
                'zone_en' => 'North Riyadh',
                'zone_ar' => 'شمال الرياض',
                'address_en' => 'King Fahd Road, Riyadh',
                'address_ar' => 'طريق الملك فهد، الرياض',
                'land_prefix' => 'RUH',
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ],
            [
                'city_en' => 'Jeddah',
                'city_ar' => 'جدة',
                'zone_en' => 'Jeddah Coast',
                'zone_ar' => 'ساحل جدة',
                'address_en' => 'Prince Sultan Road, Jeddah',
                'address_ar' => 'طريق الأمير سلطان، جدة',
                'land_prefix' => 'JED',
                'latitude' => 21.5433,
                'longitude' => 39.1728,
            ],
            [
                'city_en' => 'Dammam',
                'city_ar' => 'الدمام',
                'zone_en' => 'Dammam Business District',
                'zone_ar' => 'منطقة أعمال الدمام',
                'address_en' => 'King Saud Street, Dammam',
                'address_ar' => 'شارع الملك سعود، الدمام',
                'land_prefix' => 'DMM',
                'latitude' => 26.4207,
                'longitude' => 50.0888,
            ],
            [
                'city_en' => 'Makkah',
                'city_ar' => 'مكة المكرمة',
                'zone_en' => 'Makkah Central',
                'zone_ar' => 'وسط مكة',
                'address_en' => 'Ibrahim Al Khalil Road, Makkah',
                'address_ar' => 'طريق إبراهيم الخليل، مكة المكرمة',
                'land_prefix' => 'MAK',
                'latitude' => 21.3891,
                'longitude' => 39.8579,
            ],
            [
                'city_en' => 'Madinah',
                'city_ar' => 'المدينة المنورة',
                'zone_en' => 'Madinah North',
                'zone_ar' => 'شمال المدينة',
                'address_en' => 'King Abdullah Road, Madinah',
                'address_ar' => 'طريق الملك عبدالله، المدينة المنورة',
                'land_prefix' => 'MED',
                'latitude' => 24.5247,
                'longitude' => 39.5692,
            ],
        ];
    }

    /** @return array<string, string|float> */
    public function forBuilding(int $buildingIndex): array
    {
        return $this->all()[intdiv($buildingIndex, ShowcaseTargets::BUILDINGS_PER_PORTFOLIO)];
    }
}
