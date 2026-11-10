<?php

return [
    'open_meteo' => [
        'url' => env('OPEN_METEO_API_URL', 'https://customer-api.open-meteo.com/v1/forecast'),
        'api_key' => env('OPEN_METEO_API_KEY'),
        'cache_minutes' => 10,
        'cache_key' => 'weather.africa.forecasts',
        'last_success_cache_key' => 'weather.africa.last_success',
    ],

    'locations' => [
        ['name' => 'Lilongwe', 'country' => 'Malawi', 'region' => 'Malawi', 'latitude' => -13.9626, 'longitude' => 33.7741],
        ['name' => 'Blantyre', 'country' => 'Malawi', 'region' => 'Malawi', 'latitude' => -15.7861, 'longitude' => 35.0058],
        ['name' => 'Mzuzu', 'country' => 'Malawi', 'region' => 'Malawi', 'latitude' => -11.4656, 'longitude' => 34.0207],
        ['name' => 'Zomba', 'country' => 'Malawi', 'region' => 'Malawi', 'latitude' => -15.3859, 'longitude' => 35.3188],
        ['name' => 'Mangochi', 'country' => 'Malawi', 'region' => 'Malawi', 'latitude' => -14.4782, 'longitude' => 35.2645],
        ['name' => 'Lusaka', 'country' => 'Zambia', 'region' => 'Africa', 'latitude' => -15.4167, 'longitude' => 28.2833],
        ['name' => 'Harare', 'country' => 'Zimbabwe', 'region' => 'Africa', 'latitude' => -17.8252, 'longitude' => 31.0335],
        ['name' => 'Dar es Salaam', 'country' => 'Tanzania', 'region' => 'Africa', 'latitude' => -6.7924, 'longitude' => 39.2083],
        ['name' => 'Nairobi', 'country' => 'Kenya', 'region' => 'Africa', 'latitude' => -1.2921, 'longitude' => 36.8219],
        ['name' => 'Johannesburg', 'country' => 'South Africa', 'region' => 'Africa', 'latitude' => -26.2041, 'longitude' => 28.0473],
        ['name' => 'Accra', 'country' => 'Ghana', 'region' => 'Africa', 'latitude' => 5.6037, 'longitude' => -0.1869],
        ['name' => 'Cairo', 'country' => 'Egypt', 'region' => 'Africa', 'latitude' => 30.0444, 'longitude' => 31.2357],
    ],
];
