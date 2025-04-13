<?php // FILE: functions.php

/**
 * Core Functions for whatJacket Application v0.8.1
 */

// --- Helper Functions ---

/**
 * Basic HTTP GET request handler with User-Agent and error handling.
 *
 * @param string $url The URL to fetch.
 * @return array ['success' => bool, 'data' => mixed|null, 'error' => string|null, 'http_code' => int|null]
 */
function make_api_request(string $url): array {
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: ' . API_USER_AGENT . "\r\n" .
                        "Accept: application/geo+json, application/ld+json, application/json\r\n", // Accept relevant types
            'timeout' => 10, // 10 second timeout
            'ignore_errors' => true // Handle errors manually
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    $http_code = null;

    // Get HTTP status code from headers
    if (isset($http_response_header) && is_array($http_response_header)) {
        $status_line = $http_response_header[0];
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        if (isset($match[1])) {
            $http_code = intval($match[1]);
        }
    }

    if ($response === false || $http_code === null) {
        $error = error_get_last();
        return ['success' => false, 'data' => null, 'error' => "Network error fetching URL: " . ($error['message'] ?? 'Unknown error'), 'http_code' => $http_code];
    }

    if ($http_code >= 400) {
         $error_data = @json_decode($response, true);
         $error_message = $error_data['detail'] ?? ($error_data['title'] ?? "API error (HTTP $http_code)");
         // Add correlation ID if present (NOAA specific)
         foreach ($http_response_header as $header) {
             if (stripos($header, 'X-Correlation-Id:') === 0) {
                 $error_message .= " | CorrID: " . trim(substr($header, strlen('X-Correlation-Id:')));
                 break;
             }
         }
        return ['success' => false, 'data' => null, 'error' => $error_message, 'http_code' => $http_code];
    }

    $decoded_data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'data' => null, 'error' => "Failed to decode JSON response: " . json_last_error_msg(), 'http_code' => $http_code];
    }

    return ['success' => true, 'data' => $decoded_data, 'error' => null, 'http_code' => $http_code];
}

/**
 * Convert Celsius to Fahrenheit.
 * @param float $celsius Temperature in Celsius.
 * @return float Temperature in Fahrenheit.
 */
function celsius_to_fahrenheit(float $celsius): float {
    return ($celsius * 9 / 5) + 32;
}

/**
 * Convert Fahrenheit to Celsius.
 * @param float $fahrenheit Temperature in Fahrenheit.
 * @return float Temperature in Celsius.
 */
function fahrenheit_to_celsius(float $fahrenheit): float {
    return ($fahrenheit - 32) * 5 / 9;
}

/**
 * Format temperature value with unit symbol.
 * @param float|null $value The temperature value.
 * @param string $unit 'F' or 'C'.
 * @return string Formatted temperature string or 'N/A'.
 */
function format_temperature(?float $value, string $unit): string {
    if ($value === null) return 'N/A';
    return round($value) . '°' . strtoupper($unit);
}

/**
 * Get the correct image path for a clothing item, checking primary then fallback.
 * Returns relative path or placeholder if neither exists.
 *
 * @param array $item The clothing item array from CLOTHING_ITEMS.
 * @return string The verified image path.
 */
function get_image_path(array $item): string {
    $primary_path = $item['img'] ?? null;
    $fallback_path = $item['img_fallback'] ?? './img/placeholder.png'; // Default fallback

    if ($primary_path && file_exists($primary_path)) {
        return $primary_path;
    } elseif ($fallback_path && file_exists($fallback_path)) {
        return $fallback_path;
    } else {
        // Fallback to a generic placeholder if even the fallback is missing
        return './img/placeholder.png';
    }
}

/**
 * Get the icon for an activity category (image path or text fallback).
 * @param string $category_key The key from CATEGORIES.
 * @return array ['type' => 'img'|'text', 'value' => string]
 */
function get_activity_icon(string $category_key): array {
    $category_data = CATEGORIES[$category_key] ?? null;
    if ($category_data) {
        $img_path = $category_data['img'] ?? null;
        if ($img_path && file_exists($img_path)) {
            return ['type' => 'img', 'value' => $img_path];
        }
        // Fallback to emoji/text icon if image missing or not defined
        $text_icon = $category_data['icon'] ?? '?';
        return ['type' => 'text', 'value' => $text_icon];
    }
    return ['type' => 'text', 'value' => '?']; // Default if category not found
}

// --- API Interaction Functions ---

/**
 * Geocode a US ZIP code using Nominatim.
 *
 * @param string $zip The 5-digit US ZIP code.
 * @return array ['success' => bool, 'lat' => float|null, 'lon' => float|null, 'display_name' => string|null, 'error' => string|null]
 */
function geocode_zip(string $zip): array {
    if (!preg_match('/^\d{5}$/', $zip)) {
        return ['success' => false, 'error' => "Invalid US ZIP code format. Please enter 5 digits."];
    }

    $url = GEOCODING_API_BASE_URL . '?' . http_build_query([
        'postalcode' => $zip,
        'countrycodes' => 'us',
        'format' => 'json',
        'limit' => 1
    ]);

    $result = make_api_request($url);

    if (!$result['success']) {
        return ['success' => false, 'error' => "Geocoding API Error: " . $result['error']];
    }

    if (empty($result['data']) || !isset($result['data'][0]['lat'], $result['data'][0]['lon'])) {
        return ['success' => false, 'error' => "ZIP code '$zip' not found or no coordinates returned."];
    }

    $location = $result['data'][0];
    return [
        'success' => true,
        'lat' => (float)$location['lat'],
        'lon' => (float)$location['lon'],
        'display_name' => $location['display_name'] ?? "Location for $zip",
        'error' => null
    ];
}

/**
 * Get the NOAA grid point forecast URL for given coordinates.
 *
 * @param float $lat Latitude.
 * @param float $lon Longitude.
 * @return array ['success' => bool, 'grid_url' => string|null, 'error' => string|null]
 */
function get_noaa_grid_url(float $lat, float $lon): array {
    $url = NOAA_API_BASE_URL . "/points/" . sprintf('%.4f,%.4f', $lat, $lon); // NOAA requires 4 decimal places
    $result = make_api_request($url);

    if (!$result['success']) {
        return ['success' => false, 'error' => "NOAA Points API Error: " . $result['error']];
    }

    $grid_url = $result['data']['properties']['forecastHourly'] ?? null;

    if (!$grid_url) {
        return ['success' => false, 'error' => "Could not retrieve forecast URL from NOAA for the given coordinates."];
    }

    return ['success' => true, 'grid_url' => $grid_url, 'error' => null];
}

/**
 * Get the NOAA hourly forecast data.
 *
 * @param string $grid_url The specific forecast URL from get_noaa_grid_url.
 * @return array ['success' => bool, 'periods' => array|null, 'error' => string|null]
 */
function get_noaa_forecast(string $grid_url): array {
    $result = make_api_request($grid_url);

    if (!$result['success']) {
        return ['success' => false, 'error' => "NOAA Forecast API Error: " . $result['error']];
    }

    $periods = $result['data']['properties']['periods'] ?? null;

    if (!is_array($periods) || empty($periods)) {
        return ['success' => false, 'error' => "No forecast periods found in the NOAA response."];
    }

    return ['success' => true, 'periods' => $periods, 'error' => null];
}


// --- Forecast Processing Functions ---

/**
 * Determine the temperature band key based on temperature value and unit.
 *
 * @param float $temp_value The temperature value.
 * @param string $temp_unit 'F' or 'C'.
 * @return string The key for the matching TEMP_BANDS entry (e.g., 'Mild', 'Cold').
 */
function get_temp_band_key(float $temp_value, string $temp_unit): string {
    $temp_c = ($temp_unit === 'F') ? fahrenheit_to_celsius($temp_value) : $temp_value;

    // Iterate through bands to find the matching one
    foreach (TEMP_BANDS as $key => $band) {
        $min_c = $band['min_c'] ?? null;
        $max_c = $band['max_c'] ?? null;

        if ($min_c !== null && $max_c !== null) {
            // Range band (e.g., Hot, Warm, Mild...)
            if ($temp_c >= $min_c && $temp_c <= $max_c) {
                return $key;
            }
        } elseif ($min_c !== null) {
            // Minimum only band (Dangerous)
            if ($temp_c >= $min_c) {
                return $key;
            }
        } elseif ($max_c !== null) {
            // Maximum only band (Freezing)
            if ($temp_c <= $max_c) {
                return $key;
            }
        }
    }
    // Default fallback if no band matches (shouldn't happen with current config)
    return 'Mild';
}

/**
 * Determine weather conditions based on a forecast period.
 *
 * @param array $period A single forecast period from NOAA API.
 * @param string $current_temp_unit The user's selected temperature unit ('F' or 'C').
 * @return array An array of boolean flags for conditions (e.g., ['is_sunny' => true, 'is_rainy' => false...]).
 */
function determine_conditions(array $period, string $current_temp_unit): array {
    $conditions = [];
    foreach (array_keys(CONDITION_KEYWORDS) as $key) {
        $conditions[$key] = false; // Initialize all to false
    }

    $short_forecast_lower = strtolower($period['shortForecast'] ?? '');
    $detailed_forecast_lower = strtolower($period['detailedForecast'] ?? '');
    $combined_forecast = $short_forecast_lower . ' ' . $detailed_forecast_lower;

    // Check keywords
    foreach (CONDITION_KEYWORDS as $condition_key => $keywords) {
        if (empty($keywords)) continue; // Skip empty keyword lists (like is_scorching)
        foreach ($keywords as $keyword) {
            if (strpos($combined_forecast, $keyword) !== false) {
                // Handle overlaps/priorities:
                if ($condition_key === 'is_rainy' && ($conditions['is_thunderstorm'] || $conditions['is_severe'])) continue; // Don't mark as just rainy if it's a t-storm/severe
                if ($condition_key === 'is_drizzling' && ($conditions['is_rainy'] || $conditions['is_thunderstorm'] || $conditions['is_severe'])) continue; // Don't mark as drizzle if heavier precip exists
                if ($condition_key === 'is_snowy' && $conditions['is_severe']) continue; // Blizzard is severe
                // Let foggy and drizzling co-exist if 'mist' triggers both

                $conditions[$condition_key] = true;
                // Don't break here, allow multiple conditions like "windy and rainy"
            }
        }
    }

    // Check thresholds
    // Wind
    $wind_speed_str = $period['windSpeed'] ?? '0 mph';
    preg_match('/(\d+)/', $wind_speed_str, $wind_matches);
    $wind_mph = isset($wind_matches[1]) ? intval($wind_matches[1]) : 0;
    if ($wind_mph >= WINDY_THRESHOLD_MPH) {
        $conditions['is_windy'] = true;
    }

    // Precipitation Probability
    $precip_prob = $period['probabilityOfPrecipitation']['value'] ?? 0;
    if ($precip_prob >= RAIN_PROBABILITY_THRESHOLD) {
        // If precip is likely, but keywords didn't catch it (e.g., just "Chance Showers"), tentatively mark appropriate condition
        if (!$conditions['is_rainy'] && !$conditions['is_drizzling'] && !$conditions['is_snowy'] && !$conditions['is_thunderstorm'] && !$conditions['is_severe']) {
             if (strpos($combined_forecast, 'snow') !== false || strpos($combined_forecast, 'sleet') !== false || strpos($combined_forecast, 'flurries') !== false) {
                 $conditions['is_snowy'] = true;
             } else {
                 // Assume rain/drizzle if not explicitly snow
                 if ($precip_prob >= HEAVY_RAIN_PROBABILITY_THRESHOLD) {
                     $conditions['is_rainy'] = true; // Assume heavier rain if high probability
                 } else {
                      $conditions['is_drizzling'] = true; // Default to drizzle for lower probability threshold
                 }
             }
        }
    }

    // Temperature-driven conditions
    $temperature = $period['temperature'] ?? null;
    $temp_unit_api = $period['temperatureUnit'] ?? 'F';
    if ($temperature !== null) {
        $temp_value = ($temp_unit_api === $current_temp_unit) ? $temperature :
                      (($current_temp_unit === 'F') ? celsius_to_fahrenheit($temperature) : fahrenheit_to_celsius($temperature));
        $temp_band_key = get_temp_band_key($temp_value, $current_temp_unit);

        if ($temp_band_key === 'Dangerous') {
            $conditions['is_scorching'] = true;
        }
    }


    // Ensure at least one primary condition is set if none explicitly match
    // Prioritize flags: Severe > TStorm > Snow > Rain > Drizzle > Windy > Foggy > Cloudy > Sunny
    $primary_set = false;
    $primary_order = ['is_severe', 'is_thunderstorm', 'is_snowy', 'is_rainy', 'is_drizzling', 'is_windy', 'is_foggy', 'is_cloudy', 'is_sunny', 'is_scorching'];
    foreach ($primary_order as $key) {
        if ($conditions[$key]) {
            $primary_set = true;
            break;
        }
    }
    // If no primary weather condition set, default based on keywords or just 'variable'
    if (!$primary_set) {
        if (strpos($combined_forecast, 'clear') !== false || strpos($combined_forecast, 'sunny') !== false) {
             $conditions['is_sunny'] = true;
        } elseif (strpos($combined_forecast, 'cloud') !== false || strpos($combined_forecast, 'overcast') !== false) {
             $conditions['is_cloudy'] = true;
        }
    }


    return $conditions;
}

/**
 * Get the most significant condition key for display/background purposes.
 *
 * @param array $conditions The array of boolean condition flags.
 * @return string The key for SIMPLE_CONDITION_DISPLAY / FORECAST_BACKGROUNDS.
 */
function get_primary_condition_key(array $conditions): string {
    // Define priority order (highest first) corresponding to display names/background keys
    $priority = [
        'is_severe' => 'Severe',
        'is_thunderstorm' => 'Thunderstorm',
        'is_snowy' => 'Snowy',
        'is_rainy' => 'Rainy', // Catch heavy rain first
        'is_drizzling' => 'Drizzling',
        'is_scorching' => 'Scorching', // High heat priority
        'is_windy' => 'Windy', // Windy before cloudy/sunny if significant
        'is_foggy' => 'Foggy',
        'is_cloudy' => 'Cloudy',
        'is_sunny' => 'Sunny',
    ];

    foreach ($priority as $condition_flag => $display_key) {
        if (!empty($conditions[$condition_flag])) {
            return $display_key;
        }
    }

    // If none of the above match, default to variable/unknown
    return 'Variable';
}


/**
 * Processes the raw forecast periods to extract relevant info for the current time.
 * Focuses on the *first* forecast period for simplicity.
 *
 * @param array $periods Array of forecast periods from NOAA.
 * @param string $selected_temp_unit User's preferred temperature unit ('F' or 'C').
 * @return array|null Processed forecast data or null on error.
 */
function process_forecast(array $periods, string $selected_temp_unit): ?array {
    if (empty($periods)) {
        return null;
    }

    $current_period = $periods[0]; // Use the most immediate forecast period

    // Extract and convert temperature
    $temp_api = $current_period['temperature'] ?? null;
    $temp_unit_api = $current_period['temperatureUnit'] ?? 'F';
    $temperature = null;
    if ($temp_api !== null) {
        $temperature = ($temp_unit_api === $selected_temp_unit) ? (float)$temp_api :
                       (($selected_temp_unit === 'F') ? celsius_to_fahrenheit((float)$temp_api) : fahrenheit_to_celsius((float)$temp_api));
    }

    // Extract feels like temperature (NOAA 'apparentTemperature')
    $feels_like_api_c = $current_period['apparentTemperature']['value'] ?? null;
    $feels_like = null;
    if ($feels_like_api_c !== null) {
        $feels_like = ($selected_temp_unit === 'C') ? (float)$feels_like_api_c : celsius_to_fahrenheit((float)$feels_like_api_c);
    } else {
        $feels_like = $temperature; // Fallback to actual temp if not provided
    }

     // Determine effective temperature for clothing selection (use actual air temp)
     $effective_temperature = $temperature;
     if ($effective_temperature === null) return null; // Cannot proceed without temperature

    // Determine conditions and temp band
    $conditions = determine_conditions($current_period, $selected_temp_unit);
    $temp_band_key = get_temp_band_key($effective_temperature, $selected_temp_unit);
    $primary_condition_key = get_primary_condition_key($conditions); // For display

    // Wind
    $wind_speed_str = $current_period['windSpeed'] ?? 'N/A';
    $wind_direction = $current_period['windDirection'] ?? '';
    preg_match('/(\d+)/', $wind_speed_str, $wind_matches);
    $wind_mph = isset($wind_matches[1]) ? intval($wind_matches[1]) : 0;

    // Precipitation
    $precip_prob = $current_period['probabilityOfPrecipitation']['value'] ?? 0;
    $is_heavy_precip = ($precip_prob >= HEAVY_RAIN_PROBABILITY_THRESHOLD)
                       && ($conditions['is_rainy'] || $conditions['is_snowy'] || $conditions['is_thunderstorm']);


    return [
        'temperature' => $temperature,
        'feels_like' => $feels_like,
        'temp_unit' => $selected_temp_unit,
        'temp_band_key' => $temp_band_key,
        'short_forecast' => $current_period['shortForecast'] ?? 'N/A',
        'detailed_forecast' => $current_period['detailedForecast'] ?? 'N/A',
        'wind_speed' => $wind_speed_str,
        'wind_direction' => $wind_direction,
        'wind_mph' => $wind_mph, // Numeric wind speed
        'precip_probability' => $precip_prob,
        'conditions' => $conditions, // Array of flags like ['is_sunny' => true, ...]
        'primary_condition_key' => $primary_condition_key, // Single key like 'Rainy', 'Sunny'
        'is_heavy_precip' => $is_heavy_precip,
        'forecast_time' => $current_period['startTime'] ?? 'N/A', // Keep track of the forecast validity
    ];
}


// --- Clothing Selection Logic ---

/**
 * Selects appropriate clothing items based on processed forecast and category.
 * New Logic v0.8.1 - Simpler layering: Base + Bottom + Footwear + Optional Mid/Outer + Accessories
 * MODIFIED: Always add 'shirt-base-casual-light' under specific professional shirts.
 *
 * @param array $processed_forecast The result from process_forecast().
 * @param string $selected_category The user's selected activity category.
 * @return array ['selected_items' => array, 'prominent_item_key' => string|null]
 */
function select_clothing(array $processed_forecast, string $selected_category): array {
    $temp_band = $processed_forecast['temp_band_key'];
    $conditions = $processed_forecast['conditions'];
    $wind_mph = $processed_forecast['wind_mph'];
    $is_heavy_precip = $processed_forecast['is_heavy_precip'];

    $all_items = CLOTHING_ITEMS;
    $selected_items = []; // key => item array
    $prominent_item_key = null; // key of the main jacket/coat

    // --- Filter helper ---
    $filter_items = function(string $type = null, string $layer = null) use ($all_items, $selected_category, $temp_band, $conditions): array {
        $filtered = [];
        foreach ($all_items as $key => $item) {
            // Check category
            $item_categories = (array)$item['category']; // Ensure it's an array
            if (!in_array($selected_category, $item_categories)) continue;

            // Check type if specified
            if ($type !== null && $item['type'] !== $type) continue;

            // Check layer if specified
            if ($layer !== null && $item['layer'] !== $layer) continue;

            // Check temp band
            if (!in_array($temp_band, $item['temp_bands'])) continue;

            // Check special conditions match (Item requires a condition that is currently true)
            $special_conditions = $item['special_conditions'] ?? [];
            $condition_met = empty($special_conditions); // If item has no special needs, it's okay condition-wise
            if (!empty($special_conditions)) {
                foreach ($special_conditions as $cond_key) {
                     // map item condition key (e.g., 'rain') to forecast condition flag (e.g., 'is_rainy')
                     $flag_key = 'is_' . $cond_key;
                     if (isset($conditions[$flag_key]) && $conditions[$flag_key]) {
                         $condition_met = true;
                         break; // Only one required condition needs to be met
                     }
                }
            }
             if (!empty($special_conditions) && !$condition_met) continue;

            // --- Negative condition checks (Item should NOT be worn if condition is true) ---
            if ($item['type'] === 'pants' && ($key === 'pants-outer-casual-jeans') && ($conditions['is_rainy'] || $conditions['is_drizzling'])) continue;
            if ($item['type'] === 'sneakers' && $key === 'sneakers-casual-light' && ($conditions['is_rainy'] || $conditions['is_drizzling'] || $conditions['is_snowy'])) continue;

            $filtered[$key] = $item;
        }
        // Basic preference: prefer items with higher thermal value within the same type/layer if multiple options
        uasort($filtered, function($a, $b) {
            return ($b['thermal_value'] ?? 0) <=> ($a['thermal_value'] ?? 0);
        });
        return $filtered;
    };

    // --- Selection Process ---

    // 1. Base Layer Top (Shirt)
    $base_shirts = $filter_items('shirt', 'base');
    $chosen_shirt_key = null; // Initialize
    if (!empty($base_shirts)) {
        // --- Standard logic to pick ONE base shirt ---
        $chosen_shirt_key = key($base_shirts); // Default to highest thermal value first (due to sorting)
        // Prioritize sun protection if sunny and suitable temp band
        if ($conditions['is_sunny'] && in_array($temp_band, ['Hot', 'Warm', 'Mild'])) {
            foreach($base_shirts as $key => $item) {
                if (!empty($item['sun_protection'])) {
                    $chosen_shirt_key = $key;
                    break; // Found a sun shirt, use it
                }
            }
        }
        // Add the chosen primary base shirt to the list
        if ($chosen_shirt_key) {
             $selected_items[$chosen_shirt_key] = $base_shirts[$chosen_shirt_key];
        }
    }

    // ===========================================================
    // == START: Professional Undershirt Logic                  ==
    // ===========================================================
    if ($selected_category === 'Professional' && $chosen_shirt_key !== null) {
        // Define the keys for the professional "dress shirts" that require an undershirt
        // Add more keys here if you define medium/heavy professional base shirts later
        $professional_dress_shirt_keys = [
            'shirt-base-professional-light', 'shirt-base-professional-medium', 'shirt-base-professional-heavy'
            // Add 'shirt-base-professional-medium', etc. if they exist and need undershirts
        ];
        // Define the specific undershirt key
        $undershirt_key = 'shirt-base-tee';

        // Check if the CHOSEN base shirt is one of the dress shirts
        // AND also ensure the chosen shirt isn't the undershirt itself (unlikely but safe)
        if (in_array($chosen_shirt_key, $professional_dress_shirt_keys) && $chosen_shirt_key !== $undershirt_key) {
            // Check if the required undershirt item definition exists
            if (isset($all_items[$undershirt_key])) {
                // Add the undershirt definition to the selected items list,
                // regardless of its normal temp_bands suitability for this scenario.
                // If it was somehow already added (e.g., filter chose it THEN this rule ran),
                // adding it again by key won't hurt.
                $selected_items[$undershirt_key] = $all_items[$undershirt_key];
            } else {
                // Optional: Log a warning if the undershirt isn't defined in config
                // error_log("Warning: Required undershirt '$undershirt_key' not found in CLOTHING_ITEMS for Professional category rule.");
            }
        }
    }
    // ===========================================================
    // == END: Professional Undershirt Logic                    ==
    // ===========================================================


    // 2. Outer Bottoms (Shorts/Pants)
    $outer_bottoms = [];
    $rain_pants_key = null;
    if ($conditions['is_rainy'] || $conditions['is_drizzling'] || $conditions['is_snowy']) {
         $precip_pants = $filter_items(null, 'outer');
         foreach ($precip_pants as $key => $item) {
             if (isset($item['special_conditions']) && !empty(array_intersect(['rainy', 'drizzling', 'snowy', 'thunderstorm'], $item['special_conditions']))) {
                 if ($item['type'] === 'pants') {
                    $rain_pants_key = $key;
                    break;
                 }
             }
         }
    }

    if ($rain_pants_key !== null) {
         $selected_items[$rain_pants_key] = $all_items[$rain_pants_key];
    } else {
        $shorts = $filter_items('shorts', 'outer');
        $pants = $filter_items('pants', 'outer');
        if (!empty($shorts)) {
            $chosen_bottom_key = key($shorts);
            $selected_items[$chosen_bottom_key] = $shorts[$chosen_bottom_key];
        } elseif (!empty($pants)) {
            $chosen_bottom_key = key($pants);
            $selected_items[$chosen_bottom_key] = $pants[$chosen_bottom_key];
        }
    }


    // 3. Base Layer Bottoms (Long Johns)
    $is_wearing_pants = false;
    foreach ($selected_items as $item) {
        if ($item['layer'] === 'outer' && ($item['type'] === 'pants')) {
            $is_wearing_pants = true;
            break;
        }
    }
    if ($is_wearing_pants && in_array($temp_band, ['Cold', 'Frigid', 'Freezing'])) {
        $base_bottoms = $filter_items('base_pants', 'base');
        if (!empty($base_bottoms)) {
            $chosen_base_bottom_key = key($base_bottoms);
            $selected_items[$chosen_base_bottom_key] = $base_bottoms[$chosen_base_bottom_key];
        }
    }

    // 4. Footwear (Socks + Shoes/Sneakers/Boots)
    // Socks
    $socks = $filter_items('socks', 'single');
    if (!empty($socks)) {
        $chosen_sock_key = key($socks);
        $selected_items[$chosen_sock_key] = $socks[$chosen_sock_key];
    }
    // Shoes
    $shoes = []; $boots = []; $sneakers = [];
    $all_footwear = $filter_items(null, 'single');
    foreach ($all_footwear as $key => $item) {
        if ($item['type'] === 'shoes') $shoes[$key] = $item;
        elseif ($item['type'] === 'boots') $boots[$key] = $item;
        elseif ($item['type'] === 'sneakers') $sneakers[$key] = $item;
    }
    $chosen_footwear_key = null;
    // Priority: Insulated boots > Rain boots > Hiking boots > Weather resistant sneakers > Other boots > Regular shoes/sneakers
    if ($conditions['is_snowy'] || $conditions['is_severe'] || in_array($temp_band, ['Frigid', 'Freezing'])) {
        foreach($boots as $key => $item) if ($key === 'boots-insulated') $chosen_footwear_key = $key;
    }
    if ($chosen_footwear_key === null && ($conditions['is_rainy'] || $conditions['is_drizzling']) && $is_heavy_precip) {
         foreach($boots as $key => $item) if ($key === 'boots-rain') $chosen_footwear_key = $key;
    }
     if ($chosen_footwear_key === null && ($conditions['is_rainy'] || $conditions['is_drizzling'] || $conditions['is_snowy'] || in_array($temp_band, ['Cold','Frigid']))) {
         foreach($boots as $key => $item) if ($key === 'boots-hiking') $chosen_footwear_key = $key;
    }
    if ($chosen_footwear_key === null && ($conditions['is_rainy'] || $conditions['is_drizzling'] || $conditions['is_snowy'] || $conditions['is_windy'] || $temp_band == 'Cold')) {
        foreach($sneakers as $key => $item) if ($key === 'sneakers-casual-resistant') $chosen_footwear_key = $key;
        if ($chosen_footwear_key === null) foreach($shoes as $key => $item) if ($key === 'shoes-professional-leather') $chosen_footwear_key = $key;
    }
    if ($chosen_footwear_key === null && !empty($boots)) $chosen_footwear_key = key($boots);
    if ($chosen_footwear_key === null && !empty($sneakers)) $chosen_footwear_key = key($sneakers);
    if ($chosen_footwear_key === null && !empty($shoes)) $chosen_footwear_key = key($shoes);

    if ($chosen_footwear_key) {
         $selected_items[$chosen_footwear_key] = $all_items[$chosen_footwear_key];
    }


    // 5. Mid Layer Top (Sweater/Fleece)
    $mid_layers = [];
    if (in_array($temp_band, ['Mild', 'Crisp', 'Cold', 'Frigid'])) {
        $mid_layers = $filter_items('sweater', 'mid');
        if (!empty($mid_layers)) {
             $best_mid_key = key($mid_layers);
             $selected_items[$best_mid_key] = $mid_layers[$best_mid_key];
        }
    }

    // 6. Outer Layer Top (Jacket/Coat)
    $outer_layers = [];
    $needs_outer_layer = false;
    if (in_array($temp_band, ['Mild', 'Crisp', 'Cold', 'Frigid', 'Freezing'])) {
        $needs_outer_layer = true;
    }
    if ($conditions['is_rainy'] || $conditions['is_drizzling'] || $conditions['is_snowy'] || $conditions['is_windy'] || $conditions['is_severe'] || $conditions['is_thunderstorm']) {
        $needs_outer_layer = true;
    }
    $chosen_outer_key = null;
    if ($needs_outer_layer) {
        $jackets = $filter_items('jacket', 'outer');
        $coats = $filter_items('coat', 'outer');
        $possible_outers = $jackets + $coats;
        uasort($possible_outers, function($a, $b) {
             return ($b['thermal_value'] ?? 0) <=> ($a['thermal_value'] ?? 0);
        });
        if (!empty($possible_outers)) {
            // Priority logic for conditions...
            if ($chosen_outer_key === null && ($conditions['is_rainy'] || $conditions['is_drizzling'] || $conditions['is_thunderstorm'])) {
                foreach ($possible_outers as $key => $item) {
                    if (!empty($item['special_conditions']) && !empty(array_intersect(['rainy', 'drizzling', 'thunderstorm'], $item['special_conditions']))) { $chosen_outer_key = $key; break; }
                }
                 if ($chosen_outer_key === null && $selected_category === 'Professional') { foreach ($possible_outers as $key => $item) { if ($key === 'coat-outer-professional-light') { $chosen_outer_key = $key; break; } } }
            }
            if ($chosen_outer_key === null && $conditions['is_windy'] && !$conditions['is_rainy'] && !$conditions['is_snowy']) {
                 foreach ($possible_outers as $key => $item) { if (($item['wind_resistance'] ?? 'none') === 'proof') { if (!empty($item['special_conditions']) && in_array('windy', $item['special_conditions'])) { $chosen_outer_key = $key; break; } } }
                 if ($chosen_outer_key === null) { foreach ($possible_outers as $key => $item) { if (($item['wind_resistance'] ?? 'none') === 'proof') { $chosen_outer_key = $key; break; } } }
            }
            if ($chosen_outer_key === null && ($conditions['is_snowy'] || $conditions['is_severe'] || in_array($temp_band, ['Frigid', 'Freezing']))) {
                 foreach ($possible_outers as $key => $item) { if (!empty($item['special_conditions']) && !empty(array_intersect(['snowy', 'severe'], $item['special_conditions']))) { $chosen_outer_key = $key; break; } }
                 if ($chosen_outer_key === null) { $chosen_outer_key = key($possible_outers); }
            }
            // Fallback
            if ($chosen_outer_key === null) { $chosen_outer_key = key($possible_outers); }

            // Add chosen outer layer
            if ($chosen_outer_key !== null) {
                 $selected_items[$chosen_outer_key] = $possible_outers[$chosen_outer_key];
                 $prominent_item_key = $chosen_outer_key;
            }
        }
    }
    // If no jacket/coat, maybe use sweater?
    if ($prominent_item_key === null) {
        foreach ($selected_items as $key => $item) { if ($item['type'] === 'sweater') { $prominent_item_key = $key; break; } }
    }

    // 7. Accessories
    $accessories = [];
    if ($conditions['is_sunny'] || $conditions['is_scorching']) {
        $sunglasses = $filter_items('sunglasses', 'single');
        if(!empty($sunglasses)) $accessories[key($sunglasses)] = $sunglasses[key($sunglasses)];
    }
    if ($conditions['is_sunny'] && in_array($temp_band, ['Dangerous','Hot','Warm','Mild'])) {
         $sun_hats = $filter_items('hat', 'single');
         foreach($sun_hats as $key => $item) if(!empty($item['sun_protection'])) $accessories[$key] = $item;
    } elseif (in_array($temp_band, ['Crisp','Cold','Frigid','Freezing'])) {
         $warm_hats = $filter_items('hat', 'single');
         if (!empty($warm_hats)) {
             $snow_hat_key = null;
             if ($conditions['is_snowy']) { foreach($warm_hats as $key => $item) { if (!empty($item['special_conditions']) && in_array('snowy', $item['special_conditions'])) { $snow_hat_key = $key; break; } } }
             if ($snow_hat_key) $accessories[$snow_hat_key] = $warm_hats[$snow_hat_key];
             else $accessories[key($warm_hats)] = $warm_hats[key($warm_hats)];
         }
    }
    if (in_array($temp_band, ['Crisp','Cold'])) {
         $light_gloves = $filter_items('gloves', 'single');
         if (!empty($light_gloves)) { foreach($light_gloves as $key => $item) if($key === 'gloves-light') $accessories[$key] = $item; if (!isset($accessories['gloves-light']) && !empty($light_gloves)) $accessories[key($light_gloves)] = $light_gloves[key($light_gloves)]; }
    } elseif (in_array($temp_band, ['Frigid','Freezing'])) {
         $heavy_gloves = $filter_items('gloves', 'single');
         if (!empty($heavy_gloves)) { foreach($heavy_gloves as $key => $item) if($key === 'gloves-heavy') $accessories[$key] = $item; if (!isset($accessories['gloves-heavy']) && !empty($heavy_gloves)) $accessories[key($heavy_gloves)] = $heavy_gloves[key($heavy_gloves)]; }
    }
    if (in_array($temp_band, ['Mild','Crisp'])) {
         $light_scarves = $filter_items('scarf', 'single');
         if (!empty($light_scarves)) { foreach($light_scarves as $key => $item) if($key === 'scarf-light') $accessories[$key] = $item; if (!isset($accessories['scarf-light']) && !empty($light_scarves)) $accessories[key($light_scarves)] = $light_scarves[key($light_scarves)]; }
    } elseif (in_array($temp_band, ['Cold','Frigid','Freezing'])) {
         $warm_scarves = $filter_items('scarf', 'single');
          if (!empty($warm_scarves)) { foreach($warm_scarves as $key => $item) if($key === 'scarf-warm') $accessories[$key] = $item; if (!isset($accessories['scarf-warm']) && !empty($warm_scarves)) $accessories[key($warm_scarves)] = $warm_scarves[key($warm_scarves)]; }
    }
    if (($conditions['is_rainy'] || $conditions['is_drizzling'] || $conditions['is_thunderstorm']) && $wind_mph < UMBRELLA_MAX_WIND_MPH && $selected_category !== 'Hiking') {
        $umbrellas = $filter_items('umbrella', 'single');
         if(!empty($umbrellas)) $accessories[key($umbrellas)] = $umbrellas[key($umbrellas)];
    }
    // Add accessories
    foreach ($accessories as $key => $item) { if (!isset($selected_items[$key])) { $selected_items[$key] = $item; } }


    // --- Final Grouping for Display ---
    $grouped_items = [];
    foreach (TYPE_TO_DISPLAY_GROUP_MAP as $group) { $grouped_items[$group] = []; }
    $grouped_items['Other'] = [];
    foreach ($selected_items as $key => $item) {
        $type = $item['type'] ?? 'accessory';
        $group = TYPE_TO_DISPLAY_GROUP_MAP[$type] ?? 'Other';

        // Don't add the prominent item to the "Other Items" groups if it exists
        // Also, don't add the undershirt to the "Other Items" if it's the specific professional case
        if ($key !== $prominent_item_key) {
            // Check if it's the special undershirt case
            $is_special_undershirt = ($selected_category === 'Professional'
                                       && $key === 'shirt-base-casual-light'
                                       && isset($selected_items['shirt-base-professional-light'])); // Check if the dress shirt is also present
            if (!$is_special_undershirt) {
                 $grouped_items[$group][$key] = $item;
            }
            // If it *is* the special undershirt, it won't be added to grouped_items, only appear in the flat $selected_items list
        }
    }
    $grouped_items = array_filter($grouped_items);


    return [
        'selected_items' => $selected_items, // Flat list of all selected items (key => item)
        'grouped_items' => $grouped_items, // Items grouped for display (excluding prominent and special undershirt)
        'prominent_item_key' => $prominent_item_key, // Key of the prominent item
        'debug_info' => [ // Optional: Add some debug info
            'temp_band' => $temp_band,
            'conditions' => $conditions,
            'needs_outer' => $needs_outer_layer,
            'chosen_outer' => $chosen_outer_key,
            'chosen_base_shirt' => $chosen_shirt_key ?? 'None Selected', // Add chosen base shirt key
        ]
    ];
}

?>
