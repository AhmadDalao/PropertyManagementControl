<?php

return [
    'tile_url' => env('PROPERTY_MAP_TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'attribution' => env(
        'PROPERTY_MAP_ATTRIBUTION',
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    ),
    'default_center' => [
        (float) env('PROPERTY_MAP_DEFAULT_LATITUDE', 23.8859),
        (float) env('PROPERTY_MAP_DEFAULT_LONGITUDE', 45.0792),
    ],
    'default_zoom' => (int) env('PROPERTY_MAP_DEFAULT_ZOOM', 5),
];
