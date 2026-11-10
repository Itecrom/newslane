<details class="weather-widget" data-weather-refresh-url="{{ route('weather.widget') }}">
    <summary class="weather-trigger" aria-label="Open Africa weather forecast">
        <span class="weather-trigger-icon" aria-hidden="true">{{ $weather['regions']['Africa'][0]['symbol'] ?? '☁' }}</span>
        <span class="weather-trigger-label">Weather</span>
        @if ($weather['available'] && isset($weather['regions']['Africa'][0]))
            <strong>{{ $weather['regions']['Africa'][0]['name'] }} {{ $weather['regions']['Africa'][0]['temperature'] }}°C</strong>
        @else
            <strong>Africa forecast</strong>
        @endif
        <span class="weather-trigger-chevron" aria-hidden="true">⌄</span>
    </summary>
    <div class="weather-panel">
        <div class="weather-panel-heading">
            <div>
                <strong>Africa forecast</strong>
                <span>{{ $weather['is_stale'] ? 'Showing last available update' : 'Current conditions & 3-day forecast' }}</span>
            </div>
            <span class="weather-live-indicator {{ $weather['is_stale'] ? 'is-stale' : ($weather['available'] ? '' : 'is-offline') }}">{{ $weather['is_stale'] ? 'STALE' : ($weather['available'] ? 'LIVE' : 'OFFLINE') }}</span>
        </div>
        @if ($weather['available'])
            @foreach (['Africa' => 'Across Africa', 'Malawi' => 'Malawi'] as $region => $heading)
                @if (count($weather['regions'][$region] ?? []))
                    <section class="weather-region" aria-label="{{ $heading }} weather">
                        <h2>{{ $heading }}</h2>
                        <div class="weather-city-grid">
                            @foreach ($weather['regions'][$region] as $city)
                                <article class="weather-city">
                                    <div class="weather-city-heading">
                                        <span class="weather-city-symbol" aria-hidden="true">{{ $city['symbol'] }}</span>
                                        <div><h3>{{ $city['name'] }}</h3><span>{{ $city['country'] }}</span></div>
                                        <strong>{{ $city['temperature'] }}°</strong>
                                    </div>
                                    <p class="weather-condition">{{ $city['condition'] }}</p>
                                    <div class="weather-city-details">
                                        @if ($city['humidity'] !== null)<span>Humidity {{ $city['humidity'] }}%</span>@endif
                                        @if ($city['wind_speed'] !== null)<span>Wind {{ $city['wind_speed'] }} km/h</span>@endif
                                    </div>
                                    <div class="weather-days">
                                        @foreach ($city['forecast'] as $day)
                                            <div><span>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('D') }}</span><span>{{ $day['high'] }}° / {{ $day['low'] }}°</span></div>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
            <p class="weather-updated">Updated {{ \Illuminate\Support\Carbon::parse($weather['updated_at'])->timezone('Africa/Blantyre')->format('H:i') }} CAT · Refreshes every 10 minutes</p>
        @else
            <p class="weather-unavailable">Live weather is temporarily unavailable. Please try again later.</p>
        @endif
        <a class="weather-attribution" href="https://open-meteo.com/" target="_blank" rel="noopener noreferrer">Weather data by Open-Meteo · CC BY 4.0</a>
    </div>
</details>
