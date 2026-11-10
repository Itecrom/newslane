<?php

namespace App\Http\Controllers;

use App\Services\WeatherForecastService;
use Illuminate\Http\Response;

class WeatherWidgetController extends Controller
{
    public function __invoke(WeatherForecastService $weather): Response
    {
        return response()
            ->view('partials.weather-widget', ['weather' => $weather->forTopbar()])
            ->header('Cache-Control', 'no-store');
    }
}
