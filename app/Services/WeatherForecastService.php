<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class WeatherForecastService
{
    public function forTopbar(): array
    {
        $settings = config('weather.open_meteo');
        $lastSuccessKey = $settings['last_success_cache_key'];

        if (blank($settings['api_key'])) {
            return $this->lastAvailableForecast($lastSuccessKey);
        }

        try {
            return Cache::remember(
                $settings['cache_key'],
                now()->addMinutes($settings['cache_minutes']),
                function () use ($settings, $lastSuccessKey): array {
                    $forecast = $this->fetch($settings['url'], $settings['api_key']);
                    Cache::put($lastSuccessKey, $forecast, now()->addHours(6));

                    return $forecast;
                },
            );
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            Log::warning('The live weather forecast could not be refreshed.', [
                'exception_type' => $exception::class,
            ]);

            return $this->lastAvailableForecast($lastSuccessKey);
        }
    }

    private function fetch(string $url, string $apiKey): array
    {
        $locations = config('weather.locations');

        $response = Http::acceptJson()
            ->connectTimeout(2)
            ->timeout(6)
            ->get($url, [
                'latitude' => implode(',', array_column($locations, 'latitude')),
                'longitude' => implode(',', array_column($locations, 'longitude')),
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                'forecast_days' => 3,
                'timezone' => 'auto',
                'apikey' => $apiKey,
            ])
            ->throw();

        $payload = $response->json();

        if (! is_array($payload) || count($payload) !== count($locations)) {
            throw new UnexpectedValueException('The weather provider returned an invalid location list.');
        }

        $regions = ['Malawi' => [], 'Africa' => []];

        foreach ($locations as $index => $location) {
            $data = $payload[$index] ?? null;

            if (! is_array($data)
                || ! is_array($data['current'] ?? null)
                || ! is_array($data['daily'] ?? null)
                || ! is_numeric($data['current']['temperature_2m'] ?? null)
                || ! is_numeric($data['current']['weather_code'] ?? null)
                || ! is_array($data['daily']['time'] ?? null)
                || count($data['daily']['time']) < 3
                || ! is_array($data['daily']['temperature_2m_max'] ?? null)
                || count($data['daily']['temperature_2m_max']) < 3
                || ! is_array($data['daily']['temperature_2m_min'] ?? null)
                || count($data['daily']['temperature_2m_min']) < 3
                || ! is_array($data['daily']['weather_code'] ?? null)
                || count($data['daily']['weather_code']) < 3) {
                throw new UnexpectedValueException('The weather provider returned incomplete forecast data.');
            }

            foreach (range(0, 2) as $day) {
                if (! is_string($data['daily']['time'][$day] ?? null)
                    || ! is_numeric($data['daily']['temperature_2m_max'][$day] ?? null)
                    || ! is_numeric($data['daily']['temperature_2m_min'][$day] ?? null)
                    || ! is_numeric($data['daily']['weather_code'][$day] ?? null)) {
                    throw new UnexpectedValueException('The weather provider returned invalid daily forecast data.');
                }
            }

            $regions[$location['region']][] = [
                'name' => $location['name'],
                'country' => $location['country'],
                'temperature' => (int) round($data['current']['temperature_2m']),
                'condition' => $this->condition((int) $data['current']['weather_code']),
                'symbol' => $this->symbol((int) $data['current']['weather_code']),
                'humidity' => is_numeric($data['current']['relative_humidity_2m'] ?? null)
                    ? (int) round($data['current']['relative_humidity_2m'])
                    : null,
                'wind_speed' => is_numeric($data['current']['wind_speed_10m'] ?? null)
                    ? (int) round($data['current']['wind_speed_10m'])
                    : null,
                'forecast' => array_map(
                    fn (int $day): array => [
                        'date' => $data['daily']['time'][$day],
                        'condition' => $this->condition((int) $data['daily']['weather_code'][$day]),
                        'high' => (int) round($data['daily']['temperature_2m_max'][$day]),
                        'low' => (int) round($data['daily']['temperature_2m_min'][$day]),
                    ],
                    range(0, 2),
                ),
            ];
        }

        return [
            'available' => true,
            'is_stale' => false,
            'updated_at' => now()->toIso8601String(),
            'regions' => $regions,
        ];
    }

    private function condition(int $code): string
    {
        return match ($code) {
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45, 48 => 'Fog',
            51, 53, 55 => 'Drizzle',
            56, 57 => 'Freezing drizzle',
            61, 63, 65 => 'Rain',
            66, 67 => 'Freezing rain',
            71, 73, 75, 77 => 'Snow',
            80, 81, 82 => 'Rain showers',
            85, 86 => 'Snow showers',
            95, 96, 99 => 'Thunderstorm',
            default => 'Conditions unavailable',
        };
    }

    private function symbol(int $code): string
    {
        return match ($code) {
            0, 1 => '☀',
            2 => '⛅',
            3, 45, 48 => '☁',
            51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82 => '☂',
            71, 73, 75, 77, 85, 86 => '❄',
            95, 96, 99 => '⚡',
            default => '•',
        };
    }

    private function unavailable(): array
    {
        return [
            'available' => false,
            'is_stale' => false,
            'updated_at' => null,
            'regions' => [],
        ];
    }

    private function lastAvailableForecast(string $cacheKey): array
    {
        $forecast = Cache::get($cacheKey);

        if (! is_array($forecast)) {
            return $this->unavailable();
        }

        $forecast['is_stale'] = true;

        return $forecast;
    }
}
