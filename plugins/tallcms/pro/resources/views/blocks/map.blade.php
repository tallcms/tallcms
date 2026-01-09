@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];
    $isPreview = $is_preview ?? false;

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
        '--block-primary-color: ' . ($textPreset['link'] ?? '#2563eb'),
    ])->join('; ') . ';';

    $headingColor = $textPreset['heading'] ?? '#111827';
    $textColor = $textPreset['description'] ?? '#4b5563';
    $primaryColor = $textPreset['link'] ?? '#2563eb';

    $uniqueId = 'map-' . uniqid();
    $lat = floatval($latitude ?? 40.7128);
    $lng = floatval($longitude ?? -74.0060);
    $zoomLevel = intval($zoom ?? 14);

    // Check if coordinates are provided (using isset to allow 0,0)
    $hasCoordinates = isset($latitude) && isset($longitude) && $latitude !== '' && $longitude !== '';

    // Height classes
    $heightPx = match($height ?? 'md') {
        'sm' => '300px',
        'lg' => '500px',
        'xl' => '600px',
        default => '400px',
    };

    $roundedClass = ($rounded ?? true) ? 'rounded-xl' : '';
    $roundedStyle = ($rounded ?? true) ? 'border-radius: 0.75rem;' : '';

    // Get API key from block config or Pro Settings
    $effectiveApiKey = $api_key ?? '';
    if (empty($effectiveApiKey)) {
        // Try to get from Pro Settings (bypass cache for reliability)
        $providerKey = match($provider ?? 'openstreetmap') {
            'google' => 'google_maps_api_key',
            'mapbox' => 'mapbox_access_token',
            default => null,
        };
        if ($providerKey) {
            // First try cached value
            $effectiveApiKey = \Tallcms\Pro\Models\ProSetting::get($providerKey, '');

            // If empty, try direct database query (bypass cache)
            if (empty($effectiveApiKey)) {
                $setting = \Tallcms\Pro\Models\ProSetting::where('key', $providerKey)->first();
                if ($setting) {
                    $effectiveApiKey = $setting->getValue() ?? '';
                }
            }
        }
    }

    $providerLabel = match($provider ?? 'openstreetmap') {
        'google' => 'Google Maps',
        'mapbox' => 'Mapbox',
        default => 'OpenStreetMap',
    };

    // Build popup HTML safely for JS (use json_encode for XSS protection)
    $popupHtml = '';
    if (!empty($marker_title) || !empty($address)) {
        $popupHtml = '<div style="min-width: 150px;">';
        if (!empty($marker_title)) {
            $popupHtml .= '<strong>' . e($marker_title) . '</strong><br>';
        }
        if (!empty($address)) {
            $popupHtml .= '<span>' . e($address) . '</span>';
        }
        $popupHtml .= '</div>';
    }
    $popupHtmlJson = json_encode($popupHtml, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $markerTitleJson = json_encode($marker_title ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
@endphp

@if($isPreview)
{{-- Admin Preview: Show placeholder since maps require JavaScript --}}
<section style="padding: 3rem 0;">
    <div style="max-width: 72rem; margin: 0 auto; padding: 0 1rem;">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div style="text-align: center; margin-bottom: 2rem;">
                @if(!empty($heading))
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: {{ $headingColor }};">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p style="margin-top: 0.75rem; font-size: 1.125rem; color: {{ $textColor }};">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Map Placeholder --}}
        <div style="box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); {{ $roundedStyle }} overflow: hidden;">
            <div style="height: {{ $heightPx }}; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 50%, #7dd3fc 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
                {{-- Map pin icon --}}
                <svg style="width: 4rem; height: 4rem; color: {{ $primaryColor }}; margin-bottom: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>

                @if($hasCoordinates)
                    <p style="font-weight: 600; color: #0c4a6e; font-size: 1rem; margin-bottom: 0.25rem;">{{ $providerLabel }}</p>
                    <p style="color: #0369a1; font-size: 0.875rem;">{{ $lat }}, {{ $lng }} (Zoom: {{ $zoomLevel }})</p>
                    @if(!empty($marker_title))
                        <p style="color: #0369a1; font-size: 0.875rem; margin-top: 0.5rem; font-weight: 500;">{{ $marker_title }}</p>
                    @endif
                @else
                    <p style="color: #0c4a6e; font-size: 0.875rem;">No location configured</p>
                @endif

                <p style="position: absolute; bottom: 1rem; color: #0369a1; font-size: 0.75rem; opacity: 0.7;">Map preview only visible on frontend</p>
            </div>
        </div>

        {{-- Contact Info --}}
        @if(!empty($contact_info))
            <div style="margin-top: 1.5rem; background: #f9fafb; {{ $roundedStyle }} padding: 1.5rem;">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                    <svg style="width: 1.5rem; height: 1.5rem; color: {{ $primaryColor }}; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div style="font-size: 0.875rem; white-space: pre-line; color: {{ $textColor }};">{{ $contact_info }}</div>
                </div>
            </div>
        @endif
    </div>
</section>
@else
{{-- Frontend: Full interactive map --}}
@if(($provider ?? 'openstreetmap') === 'openstreetmap')
    @once
    {{-- Leaflet CSS & JS for OpenStreetMap --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        .pro-map-block .leaflet-container {
            font-family: inherit;
        }
    </style>
    @endonce
@endif

<section
    class="pro-map-block py-12 sm:py-16"
    style="{{ $customProperties }}"
>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="text-center mb-8">
                @if(!empty($heading))
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight" style="color: var(--block-heading-color);">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-3 text-lg max-w-2xl mx-auto" style="color: var(--block-text-color);">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Map Container --}}
        @if($hasCoordinates)
            <div class="shadow-lg {{ $roundedClass }} overflow-hidden">
                @if(($provider ?? 'openstreetmap') === 'openstreetmap')
                    {{-- OpenStreetMap with Leaflet --}}
                    <div
                        id="{{ $uniqueId }}"
                        style="height: {{ $heightPx }}; width: 100%;"
                    ></div>
                    <script>
                        (function() {
                            const map = L.map('{{ $uniqueId }}', {
                                scrollWheelZoom: {{ ($scrollwheel_zoom ?? false) ? 'true' : 'false' }}
                            }).setView([{{ $lat }}, {{ $lng }}], {{ $zoomLevel }});

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                            }).addTo(map);

                            @if($show_marker ?? true)
                                const marker = L.marker([{{ $lat }}, {{ $lng }}]).addTo(map);
                                @if(!empty($popupHtml))
                                    marker.bindPopup({!! $popupHtmlJson !!});
                                @endif
                            @endif
                        })();
                    </script>

                @elseif(($provider ?? 'openstreetmap') === 'google' && !empty($effectiveApiKey))
                    {{-- Google Maps --}}
                    <div
                        id="{{ $uniqueId }}"
                        style="height: {{ $heightPx }}; width: 100%;"
                    ></div>
                    @once
                    <script>
                        window.proGoogleMapsQueue = window.proGoogleMapsQueue || [];
                        window.proGoogleMapsReady = window.proGoogleMapsReady || false;

                        function proInitAllGoogleMaps() {
                            window.proGoogleMapsReady = true;
                            window.proGoogleMapsQueue.forEach(fn => fn());
                            window.proGoogleMapsQueue = [];
                        }

                        function proQueueGoogleMap(fn) {
                            if (window.proGoogleMapsReady && window.google && window.google.maps) {
                                fn();
                            } else {
                                window.proGoogleMapsQueue.push(fn);
                            }
                        }
                    </script>
                    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $effectiveApiKey }}&callback=proInitAllGoogleMaps"></script>
                    @endonce
                    <script>
                        proQueueGoogleMap(function() {
                            const mapOptions = {
                                center: { lat: {{ $lat }}, lng: {{ $lng }} },
                                zoom: {{ $zoomLevel }},
                                scrollwheel: {{ ($scrollwheel_zoom ?? false) ? 'true' : 'false' }},
                                mapTypeId: '{{ match($style ?? 'streets') {
                                    'satellite' => 'satellite',
                                    'hybrid' => 'hybrid',
                                    'terrain' => 'terrain',
                                    default => 'roadmap'
                                } }}'
                            };
                            const map = new google.maps.Map(document.getElementById('{{ $uniqueId }}'), mapOptions);

                            @if($show_marker ?? true)
                                const marker = new google.maps.Marker({
                                    position: { lat: {{ $lat }}, lng: {{ $lng }} },
                                    map: map,
                                    title: {!! $markerTitleJson !!}
                                });

                                @if(!empty($popupHtml))
                                    const infoWindow = new google.maps.InfoWindow({
                                        content: {!! $popupHtmlJson !!}
                                    });
                                    marker.addListener('click', () => infoWindow.open(map, marker));
                                @endif
                            @endif
                        });
                    </script>

                @elseif(($provider ?? 'openstreetmap') === 'mapbox' && !empty($effectiveApiKey))
                    {{-- Mapbox --}}
                    @once
                    <link href="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css" rel="stylesheet">
                    <script src="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js"></script>
                    @endonce
                    <div
                        id="{{ $uniqueId }}"
                        style="height: {{ $heightPx }}; width: 100%;"
                    ></div>
                    <script>
                        (function() {
                            mapboxgl.accessToken = '{{ $effectiveApiKey }}';
                            const map = new mapboxgl.Map({
                                container: '{{ $uniqueId }}',
                                style: 'mapbox://styles/mapbox/{{ match($style ?? 'streets') {
                                    'satellite' => 'satellite-v9',
                                    'hybrid' => 'satellite-streets-v12',
                                    'terrain' => 'outdoors-v12',
                                    default => 'streets-v12'
                                } }}',
                                center: [{{ $lng }}, {{ $lat }}],
                                zoom: {{ $zoomLevel }},
                                scrollZoom: {{ ($scrollwheel_zoom ?? false) ? 'true' : 'false' }}
                            });

                            @if($show_marker ?? true)
                                @if(!empty($popupHtml))
                                    const popup = new mapboxgl.Popup({ offset: 25 })
                                        .setHTML({!! $popupHtmlJson !!});

                                    new mapboxgl.Marker()
                                        .setLngLat([{{ $lng }}, {{ $lat }}])
                                        .setPopup(popup)
                                        .addTo(map);
                                @else
                                    new mapboxgl.Marker()
                                        .setLngLat([{{ $lng }}, {{ $lat }}])
                                        .addTo(map);
                                @endif
                            @endif
                        })();
                    </script>

                @else
                    {{-- No API key for selected provider --}}
                    <div class="bg-gray-100 dark:bg-gray-800 flex items-center justify-center" style="height: {{ $heightPx }};">
                        <div class="text-center text-gray-500 dark:text-gray-400 px-4">
                            <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <p class="text-sm">API key required for {{ ucfirst($provider ?? 'this') }} Maps.</p>
                            <p class="text-xs mt-1">Configure in Pro Settings or use OpenStreetMap (free).</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Contact Info --}}
            @if(!empty($contact_info))
                <div class="mt-6 bg-gray-50 dark:bg-gray-800 {{ $roundedClass }} p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--block-primary-color);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="text-sm whitespace-pre-line" style="color: var(--block-text-color);">
                            {{ $contact_info }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- No Location Configured --}}
            <div class="shadow-lg {{ $roundedClass }} overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center" style="height: {{ $heightPx }};">
                <div class="text-center text-gray-500 dark:text-gray-400 px-4">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p class="text-sm">No location configured. Click to edit this block.</p>
                    <p class="text-xs mt-2">Enter latitude and longitude to display a map.</p>
                </div>
            </div>
        @endif
    </div>
</section>
@endif
