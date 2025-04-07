<?php
// Enable error reporting for development (disable in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 0);

require_once 'config.php';

// --- Helper Functions ---

/**
 * Fetches data from a URL using cURL with specified User-Agent.
 *
 * @param string $url The URL to fetch.
 * @return array|null Decoded JSON data as an associative array, or null on failure.
 */
function fetch_data(string $url): ?array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, API_USER_AGENT); // Use configured User-Agent
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Set timeout
    // Required for HTTPS requests
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL cert
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // Check common name

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        log_error("cURL Error fetching $url: " . $error);
        $_SESSION['error_message'] = "Error communicating with weather service (cURL)."; // User friendly cURL error
        return null;
    }

    if ($http_code >= 400) {
        log_error("HTTP Error $http_code fetching $url. Response: " . substr($response, 0, 200));
         // Try decoding potential JSON error from API
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['detail'])) {
             // NOAA often provides details here
             $_SESSION['error_message'] = "API Error: " . htmlspecialchars($decoded['detail']);
        } elseif(isset($decoded['title'])) {
             $_SESSION['error_message'] = "API Error: " . htmlspecialchars($decoded['title']);
        } else {
             $_SESSION['error_message'] = "Weather API request failed (HTTP status " . $http_code . ").";
        }
        return null;
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        log_error("JSON Decode Error fetching $url: " . json_last_error_msg() . ". Response: " . substr($response, 0, 200));
        $_SESSION['error_message'] = "Error decoding weather API response.";
        return null;
    }

    return $decoded;
}

/**
 * Filters raw suggestions to ensure only one item per core type (pants/shorts, shoes, jacket, coat, shirt, sweater).
 * Prioritizes items suitable for the minimum forecast temperature for most types.
 * Chooses between pants and shorts based on max forecast temperature.
 * Allows multiple items for accessories.
 * Ensures essential items (shirt, pants/shorts, shoes) are included if possible via fallback.
 *
 * @param array $raw_suggestions Suggestions grouped by original type from suggest_clothing.
 * @param array $forecast Processed forecast data containing twelve_hour_summary.
 * @param string $selected_category The user-selected category. // ADDED PARAMETER
 * @return array Filtered suggestions, grouped by original type.
 */
function filter_suggestions(array $raw_suggestions, array $forecast, string $selected_category): array { // ADDED $selected_category
    $filtered_suggestions = [];
    // Define types where only ONE item should be suggested
    // ADDED: 'shirt', 'sweater'
    // REMOVED: 'shorts' temporarily, will handle pants/shorts logic separately
    $single_item_types = ['pants', 'shoes', 'jacket', 'coat', 'shirt', 'sweater'];

    // Retrieve forecast details needed for prioritization (handle potential nulls)
    $min_temp_scale = $forecast['twelve_hour_summary']['min_temp_scale'] ?? 5; // Default to moderate if null
    $max_temp_scale = $forecast['twelve_hour_summary']['max_temp_scale'] ?? 5; // Default to moderate if null
    // $max_precip = $forecast['twelve_hour_summary']['max_precip_prob'] ?? 0;
    // $max_wind = $forecast['twelve_hour_summary']['max_wind_speed'] ?? 0;

    // --- Step 1: Filter each type individually (except shorts for now) ---
    foreach ($raw_suggestions as $type => $item_keys) {
        // Skip shorts for now, handle pants/shorts conflict later
        if ($type === 'shorts') {
            continue;
        }

        if (in_array($type, $single_item_types) && count($item_keys) > 1) {
            // --- Prioritization Logic for Single Item Types ---
            $best_item_key = null;
            $best_item_min_temp = 11; // Initialize higher than max scale

            foreach ($item_keys as $item_key) {
                if (!isset(CLOTHING_ITEMS[$item_key])) continue;

                $item_details = CLOTHING_ITEMS[$item_key];
                $current_item_min_temp = $item_details['temp_min'] ?? 11;

                // Prioritize item suitable for the lowest temperature
                if ($current_item_min_temp < $best_item_min_temp) {
                    $best_item_min_temp = $current_item_min_temp;
                    $best_item_key = $item_key;
                }
                // Optional Tie-breaking could go here
            }

            if ($best_item_key !== null) {
                $filtered_suggestions[$type] = [$best_item_key]; // Add only the best one
            }

        } else {
            // --- Keep All Suggestions for Accessories OR if only one item was suggested initially ---
             // Don't add 'shorts' here yet.
            if ($type !== 'shorts') {
                 $filtered_suggestions[$type] = $item_keys;
            }
        }
    } // End loop through types

    // --- Step 2: Handle Pants vs. Shorts Conflict ---
    $pants_suggested = isset($filtered_suggestions['pants']) && !empty($filtered_suggestions['pants']);
    $shorts_suggested_initially = isset($raw_suggestions['shorts']) && !empty($raw_suggestions['shorts']);

    if ($pants_suggested && $shorts_suggested_initially) {
        // Both were potentially suggested. Choose based on max temperature.
        // Threshold: If max temp is 70F (scale 7) or higher, prefer shorts. Otherwise, prefer pants.
        if ($max_temp_scale >= 7) {
            // Prefer shorts: Remove pants suggestion, add the best shorts suggestion
            unset($filtered_suggestions['pants']);
             // Find the best shorts item (using same logic as above)
             $best_shorts_key = null;
             $best_shorts_min_temp = 11;
             foreach($raw_suggestions['shorts'] as $shorts_key) {
                  if (!isset(CLOTHING_ITEMS[$shorts_key])) continue;
                  $shorts_details = CLOTHING_ITEMS[$shorts_key];
                  $current_shorts_min_temp = $shorts_details['temp_min'] ?? 11;
                  if ($current_shorts_min_temp < $best_shorts_min_temp) {
                     $best_shorts_min_temp = $current_shorts_min_temp;
                     $best_shorts_key = $shorts_key;
                  }
             }
             if ($best_shorts_key) {
                 $filtered_suggestions['shorts'] = [$best_shorts_key];
             }

        } else {
            // Prefer pants: Keep the pants suggestion (already in $filtered_suggestions).
            // Do nothing here, pants are already kept, shorts were skipped earlier.
        }
    } elseif ($shorts_suggested_initially && !$pants_suggested) {
        // Only shorts were suggested initially, add the best one.
         $best_shorts_key = null;
         $best_shorts_min_temp = 11;
         foreach($raw_suggestions['shorts'] as $shorts_key) {
              if (!isset(CLOTHING_ITEMS[$shorts_key])) continue;
              $shorts_details = CLOTHING_ITEMS[$shorts_key];
              $current_shorts_min_temp = $shorts_details['temp_min'] ?? 11;
              if ($current_shorts_min_temp < $best_shorts_min_temp) {
                 $best_shorts_min_temp = $current_shorts_min_temp;
                 $best_shorts_key = $shorts_key;
              }
         }
         if ($best_shorts_key) {
             $filtered_suggestions['shorts'] = [$best_shorts_key];
         }
    }
    // If only pants were suggested, they are already in $filtered_suggestions.

    // --- Step 3: Re-add accessory types that might have been filtered out if mistaken as single types (safety check) ---
    // Ensure types explicitly meant for multiple items (accessories) are preserved if they existed in raw suggestions.
    $accessory_types = ['hat', 'gloves', 'scarf', 'umbrella', 'sunglasses', 'accessory', 'socks']; // Add socks here too
    foreach ($accessory_types as $acc_type) {
        if (isset($raw_suggestions[$acc_type]) && !isset($filtered_suggestions[$acc_type])) {
            // If it was suggested originally but somehow removed, put it back
            // (This shouldn't happen with current logic, but is a safeguard)
             $filtered_suggestions[$acc_type] = $raw_suggestions[$acc_type];
        } elseif (isset($raw_suggestions[$acc_type]) && isset($filtered_suggestions[$acc_type])) {
            // If it exists in both, ensure we didn't accidentally reduce it to one item if multiple accessories of same type were valid
             if(count($raw_suggestions[$acc_type]) > 1 && count($filtered_suggestions[$acc_type]) === 1 && !in_array($acc_type, $single_item_types)) {
                 $filtered_suggestions[$acc_type] = $raw_suggestions[$acc_type]; // Restore multiple if appropriate
             }
        }
    }
    // Special case for Socks: Usually one pair, but allow heavy/medium difference if needed later.
    // For now, let's treat socks like accessories (allow multiple if suggest_clothing provides them).
    // If you strictly want only ONE pair of socks, add 'socks' to $single_item_types.

    // --- Step 4: Safety Net - Ensure Essential Items Exist ---
    $essential_types = ['shirt', 'pants_or_shorts', 'shoes']; // Define essential types

    // Helper function to find a fallback item
    $find_fallback = function($target_type, $category, $forecast) use ($raw_suggestions) {
        $best_fallback_key = null;
        $best_score = -1; // Score based on temperature match

        $min_temp = $forecast['twelve_hour_summary']['min_temp_scale'] ?? 5;
        $max_temp = $forecast['twelve_hour_summary']['max_temp_scale'] ?? 5;
        $now_temp = $forecast['next_hour']['temperature_scale'] ?? 5;

        foreach (CLOTHING_ITEMS as $key => $item) {
            // Skip placeholder
            if ($key === 'placeholder') continue;

            // Check type match (handle pants_or_shorts)
            $item_type = $item['type'] ?? null;
            if ($target_type === 'pants_or_shorts') {
                if ($item_type !== 'pants' && $item_type !== 'shorts') continue;
            } elseif ($item_type !== $target_type) {
                continue;
            }

            // Check category match
            $item_categories = is_array($item['category']) ? $item['category'] : [$item['category']];
            if (!in_array($category, $item_categories)) continue;

            // Check temperature compatibility (basic overlap)
            $item_min = $item['temp_min'] ?? 0;
            $item_max = $item['temp_max'] ?? 10;
            if (!($item_min <= $max_temp && $item_max >= $min_temp)) continue;

            // Optional: Check if it was *originally* suggested but filtered out
            // This gives preference to items the logic initially considered valid.
            $was_suggested = false;
            if (isset($raw_suggestions[$item_type])) {
                $was_suggested = in_array($key, $raw_suggestions[$item_type]);
            }

            // --- Scoring (simple version) ---
            // Prefer items whose range includes the current temp
            // Prefer items whose range is closer to the forecast range
            // Prefer items that were originally suggested
            $score = 0;
            if ($now_temp >= $item_min && $now_temp <= $item_max) $score += 5; // Good bonus for current temp
            // Add points for overlap (can be refined)
            $overlap = max(0, min($max_temp, $item_max) - max($min_temp, $item_min));
            $score += $overlap;
            if ($was_suggested) $score += 2; // Bonus if originally suggested

            if ($score > $best_score) {
                $best_score = $score;
                $best_fallback_key = $key;
            }
        }
        // If a fallback is found, return it as [type => [key]] format
        if ($best_fallback_key) {
             $fallback_item_type = CLOTHING_ITEMS[$best_fallback_key]['type'];
             return [$fallback_item_type => [$best_fallback_key]];
        }
        return null; // No suitable fallback found
    };

    // Check each essential type
    foreach ($essential_types as $essential) {
        $found = false;
        if ($essential === 'pants_or_shorts') {
            if (!empty($filtered_suggestions['pants']) || !empty($filtered_suggestions['shorts'])) {
                $found = true;
            }
        } elseif (!empty($filtered_suggestions[$essential])) {
            $found = true;
        }

        if (!$found) {
            // Attempt to find and add a fallback
            $fallback = $find_fallback($essential, $selected_category, $forecast);
            if ($fallback) {
                // Merge the fallback into the filtered suggestions
                // Be careful not to overwrite existing items if the fallback is for a type
                // that might already exist but wasn't the one being checked (e.g., finding 'pants' for 'pants_or_shorts')
                $fallback_type = key($fallback);
                $fallback_key_array = current($fallback);
                if (!isset($filtered_suggestions[$fallback_type])) {
                    $filtered_suggestions[$fallback_type] = $fallback_key_array;
                    log_error("Safety Net: Added fallback item '{$fallback_key_array[0]}' for essential type '{$essential}' in category '{$selected_category}'."); // Log fallback action
                } else {
                    // This case shouldn't happen often if the primary check works, but log if it does
                     log_error("Safety Net Warning: Found fallback '{$fallback_key_array[0]}' for '{$essential}', but type '{$fallback_type}' already exists in filtered list. Fallback not added.");
                }

                 // --- Special Handling for Pants/Shorts after adding fallback ---
                 // If we just added a 'pants' fallback, and 'shorts' somehow still exist, remove shorts.
                 // If we just added a 'shorts' fallback, and 'pants' somehow still exist, remove pants.
                 // This ensures the final output respects the single bottom layer principle.
                 if ($essential === 'pants_or_shorts') {
                     $added_type = $fallback_type; // 'pants' or 'shorts'
                     if ($added_type === 'pants' && isset($filtered_suggestions['shorts'])) {
                         unset($filtered_suggestions['shorts']);
                         log_error("Safety Net Cleanup: Removed shorts after adding pants fallback.");
                     } elseif ($added_type === 'shorts' && isset($filtered_suggestions['pants'])) {
                         unset($filtered_suggestions['pants']);
                          log_error("Safety Net Cleanup: Removed pants after adding shorts fallback.");
                     }
                 }

            } else {
                // Log if even the fallback fails (might indicate config issues)
                 log_error("Safety Net Failed: Could not find any suitable fallback for essential type '{$essential}' in category '{$selected_category}'. Check CLOTHING_ITEMS configuration.");
                 // Optional: Set a user-facing warning?
                 // $_SESSION['warning_message'] = ($_SESSION['warning_message'] ?? '') . " Could not find suitable {$essential}. ";
            }
        }
    } // End loop essential_types


    return $filtered_suggestions;
} // End filter_suggestions

/**
 * Logs an error message (replace with more robust logging if needed).
 * @param string $message The error message.
 */
function log_error(string $message): void {
    error_log("whatJacket Error: " . $message);
}

/**
 * Converts a temperature in Fahrenheit to the 0-10 scale.
 * @param float $temp_f Temperature in Fahrenheit.
 * @return int Temperature on the 0-10 scale, clamped.
 */
function temp_f_to_scale(float $temp_f): int {
    $scale = round(($temp_f - TEMP_SCALE_MIN_F) / (TEMP_SCALE_MAX_F - TEMP_SCALE_MIN_F) * 10);
    return max(0, min(10, $scale)); // Clamp between 0 and 10
}

/**
 * Geocodes a US ZIP code using Nominatim. Requires attribution.
 * @param string $zip The 5-digit US ZIP code.
 * @return array|null ['lat' => float, 'lon' => float] or null on failure.
 */
function geocode_zip(string $zip): ?array {
    if (!preg_match('/^\d{5}$/', $zip)) {
        $_SESSION['error_message'] = "Invalid ZIP code format. Please enter 5 digits.";
        return null;
    }

    $url = GEOCODING_API_BASE_URL . "?postalcode=" . urlencode($zip) . "&countrycodes=us&format=json&limit=1";
    $data = fetch_data($url); // Uses the same fetch function with our user agent

    if ($data && !empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
        // Set the attribution message in the session
        $_SESSION['attribution_message'] = "ZIP code lookup uses <a href='https://operations.osmfoundation.org/policies/nominatim/' target='_blank' rel='noopener noreferrer'>Nominatim</a> Search Service.";
        return [
            'lat' => (float)$data[0]['lat'],
            'lon' => (float)$data[0]['lon']
        ];
    } else {
        // fetch_data might have set an error (e.g., cURL error)
        if (!isset($_SESSION['error_message'])) {
             $_SESSION['error_message'] = "Could not find coordinates for ZIP code " . htmlspecialchars($zip) . ". Check the ZIP code and try again.";
             log_error("Geocoding failed for ZIP $zip. Response: " . json_encode($data));
        }
        unset($_SESSION['attribution_message']); // Clear attribution if geocoding failed
        return null;
    }
}

/**
 * Gets the NOAA hourly forecast URL for given coordinates.
 * @param float $latitude
 * @param float $longitude
 * @return string|null The forecast URL or null on failure.
 */
function get_noaa_hourly_forecast_url(float $latitude, float $longitude): ?string {
    $points_url = NOAA_API_BASE_URL . "/points/" . sprintf("%.4f,%.4f", $latitude, $longitude);
    $points_data = fetch_data($points_url);

    if (!$points_data || !isset($points_data['properties']['forecastHourly'])) {
        log_error("Failed to get points data or hourly forecast URL for $latitude, $longitude");
         if (!isset($_SESSION['error_message'])) { // Don't overwrite specific API error
            $_SESSION['error_message'] = "Could not retrieve weather station information for the location.";
         }
        return null;
    }
    return $points_data['properties']['forecastHourly'];
}

/**
 * Fetches and processes the hourly forecast data.
 * @param string $forecast_url The specific hourly forecast URL from NOAA.
 * @return array|null Processed forecast data or null on failure.
 */
function get_processed_forecast(string $forecast_url): ?array {
    $forecast_data = fetch_data($forecast_url);

    if (!$forecast_data || !isset($forecast_data['properties']['periods'])) {
        // Error message likely set by fetch_data if it failed
        if (!isset($_SESSION['error_message'])) {
             log_error("Failed to get or parse hourly forecast data from $forecast_url");
             $_SESSION['error_message'] = "Could not retrieve hourly forecast details.";
        }
        return null;
    }

    $periods = $forecast_data['properties']['periods'];
    if (empty($periods)) {
        $_SESSION['error_message'] = "No forecast periods found in the API response.";
        log_error("Empty periods array received from $forecast_url");
        return null;
    }

    $now = new DateTime('now', new DateTimeZone('UTC')); // API times are typically UTC
    $relevant_periods = [];

    // Find the first period starting now or later, and collect the next 12
    $first_period_index = -1;
    foreach ($periods as $index => $period) {
        if (!isset($period['startTime'])) {
            log_error("Skipping period $index due to missing startTime.");
            continue;
        }
        try {
            $startTime = new DateTime($period['startTime']);
            $startTime->setTimezone(new DateTimeZone('UTC')); // Ensure comparison in UTC
            if ($startTime >= $now) {
                 $first_period_index = $index;
                 break;
            }
        } catch (Exception $e) {
             log_error("Error parsing startTime '{$period['startTime']}': " . $e->getMessage());
             continue; // Skip invalid period
        }
    }

    if ($first_period_index === -1) {
         // Maybe all periods are in the past? Check the last one.
         if (!empty($periods)) {
             $first_period_index = 0; // Default to first if all seem past (might be timezone issue)
             log_error("Could not find a future forecast period, using the first available.");
             // Add a warning?
             // $_SESSION['warning_message'] = "Forecast data might be slightly out of date.";
         } else {
            // This case should be caught by the empty($periods) check earlier
            $_SESSION['error_message'] = "No forecast periods available.";
            return null;
         }
    }

    $relevant_periods = array_slice($periods, $first_period_index, 12);

    if (count($relevant_periods) < 1) { // Need at least one period
         $_SESSION['error_message'] = "Not enough forecast data available for the current hour.";
         log_error("Relevant periods slice resulted in empty array from $forecast_url (Index: $first_period_index)");
        return null;
    }
     if (count($relevant_periods) < 12) { // Warn if less than 12 hours found
         // Append warning instead of overwriting
         $existing_warning = $_SESSION['warning_message'] ?? '';
         $_SESSION['warning_message'] = trim($existing_warning . " Forecast data available for less than the next 12 hours.");
     }


    // --- Process the relevant periods ---
    $next_hour = $relevant_periods[0];
    $twelve_hour_summary = [
        'min_temp_f' => 1000,
        'max_temp_f' => -1000,
        'max_precip_prob' => 0,
        'max_wind_speed' => 0,
        'conditions' => [], // Collect unique short forecasts
        'start_time' => $next_hour['startTime'] ?? null, // Use start time of the first relevant period
        'end_time' => end($relevant_periods)['endTime'] ?? null // Use end time of the last relevant period
    ];
    if ($twelve_hour_summary['start_time'] === null) {
        log_error("Missing startTime for the first relevant period.");
        // Potentially set error message or handle differently
    }
    if ($twelve_hour_summary['end_time'] === null) {
        log_error("Missing endTime for the last relevant period.");
         // Potentially set error message or handle differently
    }


    $wind_speed_pattern = '/(\d+)\s*(?:to\s*\d+\s*)?mph/i'; // Updated pattern to handle ranges like "5 to 10 mph"

    foreach ($relevant_periods as $period) {
        // Basic validation for essential fields
        if (!isset($period['temperature']) || !isset($period['probabilityOfPrecipitation']['value'])) {
            log_error("Skipping period due to missing temp or precip data: " . json_encode($period));
            continue;
        }

        $temp = (float)$period['temperature'];
        $twelve_hour_summary['min_temp_f'] = min($twelve_hour_summary['min_temp_f'], $temp);
        $twelve_hour_summary['max_temp_f'] = max($twelve_hour_summary['max_temp_f'], $temp);

        $precip_prob = $period['probabilityOfPrecipitation']['value'] ?? 0;
        $twelve_hour_summary['max_precip_prob'] = max($twelve_hour_summary['max_precip_prob'], (int)$precip_prob);

        if (preg_match($wind_speed_pattern, $period['windSpeed'] ?? '', $matches)) {
            // Use the first number matched (the lower end of a range, or the single value)
            $twelve_hour_summary['max_wind_speed'] = max($twelve_hour_summary['max_wind_speed'], (int)$matches[1]);
        }

        $short_forecast = strtolower(trim($period['shortForecast'] ?? ''));
        if ($short_forecast && !in_array($short_forecast, $twelve_hour_summary['conditions'])) {
            $twelve_hour_summary['conditions'][] = $short_forecast;
        }
    }
     // Handle case where no temp was found (maybe all skipped periods?)
     if ($twelve_hour_summary['min_temp_f'] === 1000) $twelve_hour_summary['min_temp_f'] = null;
     if ($twelve_hour_summary['max_temp_f'] === -1000) $twelve_hour_summary['max_temp_f'] = null;

    // Ensure next_hour has necessary data before proceeding
    if (!isset($next_hour['temperature']) || !isset($next_hour['probabilityOfPrecipitation']['value'])) {
         $_SESSION['error_message'] = "Missing critical data for the current hour's forecast.";
         log_error("Critical data missing in next_hour period: " . json_encode($next_hour));
         return null;
    }

    // Add scaled temperatures to summary
    $twelve_hour_summary['min_temp_scale'] = $twelve_hour_summary['min_temp_f'] !== null ? temp_f_to_scale($twelve_hour_summary['min_temp_f']) : null;
    $twelve_hour_summary['max_temp_scale'] = $twelve_hour_summary['max_temp_f'] !== null ? temp_f_to_scale($twelve_hour_summary['max_temp_f']) : null;

     // Extract next hour specific wind speed
     $next_hour_wind_speed = 0;
     if (preg_match($wind_speed_pattern, $next_hour['windSpeed'] ?? '', $matches)) {
            $next_hour_wind_speed = (int)$matches[1];
     }

    return [
        'next_hour' => [
            'temperature_f' => (float)$next_hour['temperature'],
            'temperature_scale' => temp_f_to_scale((float)$next_hour['temperature']),
            'short_forecast' => $next_hour['shortForecast'] ?? 'N/A',
            'precip_prob' => (int)($next_hour['probabilityOfPrecipitation']['value'] ?? 0),
            'wind_speed' => $next_hour_wind_speed,
            'wind_direction' => $next_hour['windDirection'] ?? 'N/A',
            'icon' => $next_hour['icon'] ?? null, // Get icon URL if available
            'startTime' => $next_hour['startTime'] ?? null // Make sure startTime exists
        ],
        'twelve_hour_summary' => $twelve_hour_summary
    ];
}

/**
 * Suggests clothing based on forecast and selected category.
 *
 * @param array $forecast Processed forecast data.
 * @param string $selected_category The user-selected category.
 * @return array An array of suggested clothing item keys, grouped by original type.
 */
function suggest_clothing(array $forecast, string $selected_category): array {
    $suggestions = []; // Keyed by original type ('shirt', 'pants', etc.)
    $next_hour_temp_scale = $forecast['next_hour']['temperature_scale'];
    $min_temp_scale = $forecast['twelve_hour_summary']['min_temp_scale'];
    $max_temp_scale = $forecast['twelve_hour_summary']['max_temp_scale'];
    $max_precip = $forecast['twelve_hour_summary']['max_precip_prob'];
    $max_wind = $forecast['twelve_hour_summary']['max_wind_speed'];
    $conditions = $forecast['twelve_hour_summary']['conditions']; // array of lowercase shortForecasts

    // Handle cases where temp scale might be null (if API failed partially)
    if ($min_temp_scale === null || $max_temp_scale === null || $next_hour_temp_scale === null) {
        // Don't overwrite existing warning, but add if needed
        $existing_warning = $_SESSION['warning_message'] ?? '';
        if (strpos($existing_warning, 'temperature range') === false) { // Avoid duplicate message part
            $_SESSION['warning_message'] = trim($existing_warning . " Could not determine full temperature range, suggestions may be less accurate.");
        }
        // Assign reasonable defaults or skip temp checks if null
        $min_temp_scale = $min_temp_scale ?? 5; // Default to moderate
        $max_temp_scale = $max_temp_scale ?? 5;
        $next_hour_temp_scale = $next_hour_temp_scale ?? 5;
    }


    foreach (CLOTHING_ITEMS as $key => $item) {
        // Skip placeholder item explicitly
        if ($key === 'placeholder') continue;

        // 1. Check Category
        $item_categories = is_array($item['category']) ? $item['category'] : [$item['category']];
        if (!in_array($selected_category, $item_categories)) {
            continue; // Skip item if not in the selected category
        }

        // 2. Check Temperature Range
        // Suggest item if its operational range overlaps with the forecast 12hr range.
        $temp_match = ($item['temp_min'] <= $max_temp_scale && $item['temp_max'] >= $min_temp_scale);
        if (!$temp_match) {
            continue;
        }

        // 3. Check Precipitation Threshold (if applicable) *** UPDATED LOGIC ***
        if (isset($item['precip_threshold']) && $item['precip_threshold'] > 0) {
            // FOR WATERPROOF ITEMS (like raincoats): Only suggest if precipitation meets/exceeds threshold.
            if ($item['waterproof'] && $max_precip < $item['precip_threshold']) {
                 continue; // Skip if not rainy enough for a waterproof item
            }
            // Special case: Umbrella shouldn't be suggested if wind is too high
            if ($key === 'umbrella' && $max_wind > UMBRELLA_MAX_WIND_MPH) { // Use config constant
                 continue; // Skip umbrella in high wind
            }
            // FOR NON-WATERPROOF ITEMS: Don't filter out based on *low* precipitation.
            // The threshold might be useful later for *prioritizing*, but not excluding basic items.
        }

        // 4. Check Wind Threshold (if applicable) *** UPDATED LOGIC ***
        if (isset($item['wind_threshold']) && $item['wind_threshold'] > 0) {
             // FOR WINDPROOF ITEMS (like windbreakers): Only suggest if wind meets/exceeds threshold.
             if ($item['windproof'] && $max_wind < $item['wind_threshold']) {
                 continue; // Skip if not windy enough for a windproof item
             }
             // FOR NON-WINDPROOF ITEMS: Don't filter out based on *low* wind.
        }


        // 5. Check Specific Conditions (if applicable)
        // If item lists specific conditions, at least one MUST match the forecast conditions.
        if (!empty($item['conditions'])) {
            $condition_match = false;
            foreach ($item['conditions'] as $item_condition) {
                foreach ($conditions as $forecast_condition) {
                    // Check if any part of the forecast condition string contains the item condition keyword
                    if (strpos($forecast_condition, $item_condition) !== false) {
                        $condition_match = true;
                        break 2; // Match found, break both loops
                    }
                }
            }
            // If the item requires specific conditions and none were matched, skip it.
            if (!$condition_match) {
                continue;
            }
        }

        // If all checks passed, add the item key, grouped by its original type
        $item_type = $item['type'] ?? 'accessory'; // Default type
        if (!isset($suggestions[$item_type])) {
            $suggestions[$item_type] = [];
        }
        $suggestions[$item_type][] = $key;
    }

    return $suggestions; // Return suggestions grouped by original type
}

/**
 * Gets the displayable image path, handling fallbacks.
 * @param string $item_key The key of the clothing item in CLOTHING_ITEMS.
 * @return string The web-accessible image path, or a default placeholder path.
 */
function get_image_path(string $item_key): string {
    $item = CLOTHING_ITEMS[$item_key] ?? null;
    $placeholder_path = './img/placeholder.png'; // Define default placeholder RELATIVE TO SCRIPT

    if (!$item || $item_key === 'placeholder') return $placeholder_path;

    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    // Base directory relative to document root
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $base_web_dir = rtrim($script_dir, '/\\'); // Get web path to script dir
    $base_web_dir = ($base_web_dir == '/' || $base_web_dir == '\\') ? '' : $base_web_dir; // Handle root

    // Function to resolve path for web access and check existence on server
    $resolve_path = function($rel_path) use ($doc_root, $base_web_dir, $placeholder_path) {
        if (empty($rel_path)) return null;

        $web_path = '';
        $server_check_path = '';

        if (strpos($rel_path, '/') === 0) { // Absolute web path
            $web_path = $rel_path;
            $server_check_path = $doc_root . $rel_path;
        } elseif (strpos($rel_path, './') === 0) { // Relative to script dir
            $clean_rel_path = substr($rel_path, 2); // Remove './'
            $web_path = $base_web_dir . '/' . $clean_rel_path;
            $server_check_path = $doc_root . $web_path;
        } elseif (strpos($rel_path, '../') === 0) { // Relative, going up
             // Calculate server path by navigating up from script's physical location
             $script_physical_dir = dirname(__FILE__); // Physical path to current script's directory
             $server_check_path = realpath($script_physical_dir . '/' . $rel_path);
             // Construct web path relative to base web dir
             $web_path = $base_web_dir . '/' . $rel_path; // This might need adjustment if nesting is deep
             // Basic normalization for web path
             $web_path = preg_replace('~/\./~', '/', $web_path); // Remove /./
             // Resolve ../ segments in web path - careful, can be complex
             $parts = explode('/', $web_path);
             $resolved_parts = [];
             foreach ($parts as $part) {
                 if ($part === '..') {
                     array_pop($resolved_parts);
                 } elseif ($part !== '' && $part !== '.') {
                     $resolved_parts[] = $part;
                 }
             }
             $web_path = '/' . implode('/', $resolved_parts);

        } else { // Assume relative to script dir if no prefix
            $web_path = $base_web_dir . '/' . $rel_path;
            $server_check_path = $doc_root . $web_path;
        }

        // Normalize directory separators for server check
        $server_check_path = str_replace('/', DIRECTORY_SEPARATOR, $server_check_path);

        if ($server_check_path && file_exists($server_check_path)) {
             // Return the web-accessible path
             return htmlspecialchars(rtrim($web_path, '/')); // Remove trailing slash just in case
        }
        return null;
    };


    // Try specific image
    $img_path = $resolve_path($item['img'] ?? null);
    if ($img_path) return $img_path;

    // Try fallback image
    $fallback_path = $resolve_path($item['img_fallback'] ?? null);
    if ($fallback_path) return $fallback_path;

    // Try type-based fallback (assuming convention: ./img/[type].jpg)
    $type = $item['type'] ?? 'accessory';
    $type_fallback_rel = './img/' . htmlspecialchars($type) . '.jpg';
    $type_fallback_path_resolved = $resolve_path($type_fallback_rel);
     if ($type_fallback_path_resolved) return $type_fallback_path_resolved;

    // Ultimate fallback
    log_error("Missing image and all fallbacks for item: $item_key (Checked: " . ($item['img'] ?? 'N/A') . ", " . ($item['img_fallback'] ?? 'N/A') . ", " . $type_fallback_rel . ")");

    // Try resolving the placeholder explicitly as a last resort before returning the hardcoded string
    $resolved_placeholder = $resolve_path($placeholder_path);
    return $resolved_placeholder ?? './img/placeholder.png'; // Return resolved or original hardcoded path
}


/**
 * Gets a simplified condition string based on NOAA short forecast.
 * Includes check for severe conditions first.
 * @param string $short_forecast The short forecast string from NOAA.
 * @param int $wind_speed The wind speed in mph.
 * @return string Simplified condition key (e.g., 'Severe', 'Snowy', 'Rainy', 'Windy', 'Cloudy', 'Sunny', 'Variable').
 */
function get_simple_condition_key(string $short_forecast, int $wind_speed): string {
    $forecast_lower = strtolower($short_forecast);

    // Check for severe weather keywords first
    if (strpos($forecast_lower, 'hurricane') !== false || strpos($forecast_lower, 'tornado') !== false || strpos($forecast_lower, 'tropical storm') !== false || strpos($forecast_lower, 'severe thunderstorm') !== false) return 'Severe';
    if (strpos($forecast_lower, 'snow') !== false || strpos($forecast_lower, 'sleet') !== false || strpos($forecast_lower, 'blizzard') !== false || strpos($forecast_lower, 'ice') !== false) return 'Snowy';
    if (strpos($forecast_lower, 'thunderstorm') !== false || strpos($forecast_lower, 'rain') !== false || strpos($forecast_lower, 'showers') !== false || strpos($forecast_lower, 'drizzle') !== false) return 'Rainy';
    if ($wind_speed >= WINDY_THRESHOLD_MPH) return 'Windy'; // Use config constant
    if (strpos($forecast_lower, 'sunny') !== false || strpos($forecast_lower, 'clear') !== false) return 'Sunny';
    // Cloudy covers more cases now
    if (strpos($forecast_lower, 'cloudy') !== false || strpos($forecast_lower, 'overcast') !== false || strpos($forecast_lower, 'fog') !== false || strpos($forecast_lower, 'mostly cloudy') !== false || strpos($forecast_lower, 'partly cloudy') !== false) return 'Cloudy';

    return 'Variable'; // Default fallback
}


// --- Main Logic ---

session_start(); // Start session for messages

// Initialize variables
$latitude = null;
$longitude = null;
$zip = $_SESSION['last_zip'] ?? null; // Persist zip across page loads via session
$selected_category = $_SESSION['last_category'] ?? null; // Persist category
$forecast = null;
$raw_suggestions = null; // Suggestions grouped by original type
$suggestions = null; // Suggestions grouped by display group (Tops, Bottoms, Accessories) - FINAL FILTERED LIST
$location_display = null;
$form_submitted = false;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_submitted = true;

    // Clear previous session messages explicitly on new POST
    unset($_SESSION['error_message']);
    unset($_SESSION['warning_message']);
    // Attribution is handled separately now, don't unset here if we want it to persist with zip/category
    // unset($_SESSION['attribution_message']);

    // Get Category
    if (isset($_POST['category']) && !empty($_POST['category'])) {
        $selected_category = htmlspecialchars($_POST['category']);
         // Validate Category
        if (!in_array($selected_category, CATEGORIES)) {
            $_SESSION['error_message'] = "Invalid category selected.";
            $selected_category = null; // Reset if invalid
             unset($_SESSION['last_category']);
        } else {
            $_SESSION['last_category'] = $selected_category; // Store valid category
        }
    } else {
        $_SESSION['error_message'] = "Please select a clothing category.";
        $selected_category = null;
         unset($_SESSION['last_category']);
    }


    // --- Determine Location (ZIP Code Only) ---
    if (isset($_POST['zip']) && !empty($_POST['zip'])) {
        $zip_from_form = trim($_POST['zip']);
        // Clear previous attribution before trying to geocode
        unset($_SESSION['attribution_message']);
        $coords = geocode_zip($zip_from_form); // Attempt geocoding (sets attribution on success)
        if ($coords) {
            $latitude = $coords['lat'];
            $longitude = $coords['lon'];
            $zip = $zip_from_form; // Store the successful zip
            $_SESSION['last_zip'] = $zip; // Store in session
            $location_display = "ZIP Code: " . htmlspecialchars($zip);
        } else {
            // geocode_zip sets the error message and unsets attribution
            $latitude = null; $longitude = null; $zip = null;
            unset($_SESSION['last_zip']);
        }
    } else {
         // Only set error if ZIP was missing and we haven't set another error
         if (!isset($_SESSION['error_message'])) {
             $_SESSION['error_message'] = "Please enter a US ZIP code.";
         }
         $zip = null;
         unset($_SESSION['last_zip']);
         unset($_SESSION['attribution_message']); // Clear attribution if ZIP is missing
    }


        // --- Fetch Forecast and Suggest Clothing (if location and category are valid) ---
        if ($latitude !== null && $longitude !== null && $selected_category !== null) {
            $forecast_url = get_noaa_hourly_forecast_url($latitude, $longitude);
            $forecast = null; // Reset forecast
            $raw_suggestions = null; // Reset raw suggestions
            $suggestions = null; // Reset final suggestions

            if ($forecast_url) {
                $forecast = get_processed_forecast($forecast_url);
            }

            if ($forecast) {
                // 1. Get ALL potential suggestions grouped by original type
                $raw_suggestions = suggest_clothing($forecast, $selected_category);

                // 2. Filter suggestions to enforce one item per core type AND add safety net
                if (!empty($raw_suggestions)) {
                     // Pass selected_category here *** UPDATED CALL ***
                     $suggestions = filter_suggestions($raw_suggestions, $forecast, $selected_category);
                } else {
                     $suggestions = []; // Ensure suggestions is an empty array if raw was empty
                     // Even if raw is empty, run filter_suggestions to trigger safety net
                     $suggestions = filter_suggestions([], $forecast, $selected_category);
                }


                // 3. Check if *filtered* suggestions are empty and set warning if needed
                if (empty($suggestions)) {
                     // Use warning for no suggestions found, not error
                      $existing_warning = $_SESSION['warning_message'] ?? '';
                      if (strpos($existing_warning, 'No specific clothing items') === false && strpos($existing_warning, 'Could not find suitable') === false) { // Avoid duplicate / safety net messages
                          $_SESSION['warning_message'] = trim($existing_warning . " No specific clothing items could be recommended after filtering.");
                      }
                }
                // If suggestions exist (even if empty), we proceed. Error messages handled earlier.

            }
            // If forecast fetch failed, error message should already be set by preceding functions
        }

} else {
     // If not POST, clear potentially stale operational messages
     // Keep session vars for zip, category, attribution
     unset($_SESSION['error_message']);
     unset($_SESSION['warning_message']);
}

// Retrieve messages for display (might have been set by POST or cleared by non-POST)
$error_message = $_SESSION['error_message'] ?? null;
$warning_message = $_SESSION['warning_message'] ?? null;
// Retrieve attribution message separately (set by geocode_zip, persists with zip/cat)
$attribution_message = $_SESSION['attribution_message'] ?? null;

// Clear operational messages after retrieving them for display THIS request
unset($_SESSION['error_message']);
unset($_SESSION['warning_message']);
// Don't clear attribution here, let it persist with zip/cat until explicitly cleared


// Determine if results should be shown
// Need forecast AND ($suggestions needs to be set, even if empty after processing)
$show_results = ($forecast && $suggestions !== null && $selected_category && $zip);

// --- Determine Forecast Background ---
$forecast_bg_style = '';
$forecast_bg_class = ''; // Add a class for overlay styling
if ($show_results) {
    $condition_key = get_simple_condition_key(
        $forecast['next_hour']['short_forecast'] ?? '',
        $forecast['next_hour']['wind_speed'] ?? 0
    );
    $bg_image_path = FORECAST_BACKGROUNDS[$condition_key] ?? FORECAST_BACKGROUNDS['Variable']; // Default to Variable

    // Resolve the background image path using the same logic as clothing images
    $bg_image_url_resolved = get_image_path('placeholder'); // Use placeholder logic to get base path resolver
    $bg_image_url = dirname($bg_image_url_resolved); // Get directory containing placeholder
    // Construct path relative to the script's web directory if background path is relative
    if (strpos($bg_image_path, './') === 0) {
        $script_web_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $script_web_dir = ($script_web_dir == '/' || $script_web_dir == '\\') ? '' : $script_web_dir;
        $bg_image_url = $script_web_dir . '/' . ltrim($bg_image_path, './');
    } elseif (strpos($bg_image_path, '/') === 0) { // Absolute path
         $bg_image_url = $bg_image_path;
    } else { // Assume relative to img/backgrounds/ if no prefix? Or error?
         // Assuming relative to script dir as default based on config description
         $script_web_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
         $script_web_dir = ($script_web_dir == '/' || $script_web_dir == '\\') ? '' : $script_web_dir;
         $bg_image_url = $script_web_dir . '/' . $bg_image_path; // This might need ./img/backgrounds/ prepended depending on exact config value
         // Let's assume the config path is fully relative from script root e.g. "./img/backgrounds/sunny.jpg"
         $bg_image_url = $script_web_dir . '/' . ltrim($bg_image_path, './');
    }

    $forecast_bg_style = 'style="background-image: url(\'' . htmlspecialchars($bg_image_url) . '\');"';
    $forecast_bg_class = 'has-background'; // Class to activate overlay etc.
}


// --- Prepare Display Group Suggestions (if results are shown) ---
$display_groups = [
    'Tops' => [],
    'Bottoms' => [],
    'Accessories' => []
];
$prominent_item_html = null;

// Check $show_results which implies $suggestions is set (but could be empty array)
if ($show_results && !empty($suggestions)) { // Use the filtered $suggestions
    // Mapping from config type to display group
    $type_to_display_group = TYPE_TO_DISPLAY_GROUP_MAP;

    // We operate directly on the filtered suggestions now.
    $temp_suggestions = $suggestions; // Use filtered suggestions

    // --- Extract Prominent Item (Jacket/Coat) FIRST ---
    // This logic remains similar, but now operates on a list guaranteed to have at most one jacket/coat.
    $prominent_types = ['jacket', 'coat'];
    foreach ($prominent_types as $p_type) {
        // Check if the type exists in filtered suggestions and has items (should be max 1 item)
        if (isset($temp_suggestions[$p_type]) && !empty($temp_suggestions[$p_type])) {
             $item_key = $temp_suggestions[$p_type][0]; // Take the first (and only) one
             if (isset(CLOTHING_ITEMS[$item_key])) { // Check item exists in config
                 $item_details = CLOTHING_ITEMS[$item_key];
                 $prominent_item_html = '<div class="clothing-item">'
                    . '<img src="' . get_image_path($item_key) . '" alt="' . htmlspecialchars($item_details['name']) . '">'
                    . '<span>' . htmlspecialchars($item_details['name']) . '</span>'
                    . '</div>';
             } else {
                  log_error("Prominent item key '$item_key' not found in CLOTHING_ITEMS after filtering.");
             }
             // Remove this type from the temporary suggestions array so it's not processed again
             unset($temp_suggestions[$p_type]);
             break; // Stop after finding one prominent item type
        }
    }

    // --- Group Remaining Items ---
    // This loop now processes the remaining filtered items (shirts, sweaters, pants, shoes, accessories etc.)
    foreach ($temp_suggestions as $original_type => $items) {
        if (empty($items)) continue;

        $target_group = $type_to_display_group[$original_type] ?? 'Accessories';

        if (!isset($display_groups[$target_group])) { $display_groups[$target_group] = []; }
        // Ensure $items is always treated as an array before merging
        $items_to_merge = is_array($items) ? $items : [$items];
        $display_groups[$target_group] = array_merge($display_groups[$target_group], $items_to_merge);
    }

     // Remove duplicates within each display group (still useful)
     foreach ($display_groups as $group => $items) {
         $display_groups[$group] = array_unique($items);
     }

} // End processing suggestions into display groups


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>whatJacket - Weather Based Clothing Suggestions</title>

    <!-- SEO and Social Media Meta Tags -->
    <meta name="description" content="Get personalized clothing suggestions based on the weather forecast for your US ZIP code. Choose your activity category!">
    <meta name="keywords" content="weather, clothing, suggestions, forecast, jacket, outfit, NOAA, what to wear">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"> <!-- Adjust domain if needed -->
    <meta property="og:title" content="whatJacket - Weather Based Clothing Suggestions">
    <meta property="og:description" content="Get clothing suggestions based on the weather forecast.">
    <meta property="og:image" content="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')); ?>/img/wj-logo-og.png"> <!-- Create an OG image - ensure path is correct -->

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"> <!-- Adjust domain if needed -->
    <meta property="twitter:title" content="whatJacket - Weather Based Clothing Suggestions">
    <meta property="twitter:description" content="Get clothing suggestions based on the weather forecast.">
    <meta property="twitter:image" content="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\')); ?>/img/wj-logo-og.png"> <!-- Use same OG image -->

    <!-- Favicon and Apple Touch Icons -->
    <link rel="icon" href="./favicon.ico" sizes="any"> <!-- Standard Favicon -->
    <link rel="icon" href="./img/icons/icon.svg" type="image/svg+xml"> <!-- SVG Favicon -->
    <link rel="apple-touch-icon" href="./img/icons/apple-touch-icon.png"> <!-- Apple Touch Icon -->
    <link rel="manifest" href="./manifest.json"> <!-- Web App Manifest -->

    <!-- Theme Color for Browsers -->
    <meta name="theme-color" content="#007bff"> <!-- Primary Blue -->

    <!-- Apple Specific Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"> <!-- Or 'default' or 'black' -->
    <meta name="apple-mobile-web-app-title" content="whatJacket">

    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <div class="header">
             <?php
                 // Resolve logo path similarly to other images
                 $logo_web_path = '';
                 $script_web_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                 $script_web_dir = ($script_web_dir == '/' || $script_web_dir == '\\') ? '' : $script_web_dir;
                 if (strpos(LOGO_IMAGE_PATH, './') === 0) {
                     $logo_web_path = $script_web_dir . '/' . ltrim(LOGO_IMAGE_PATH, './');
                 } elseif (strpos(LOGO_IMAGE_PATH, '/') === 0) {
                     $logo_web_path = LOGO_IMAGE_PATH;
                 } else {
                      $logo_web_path = $script_web_dir . '/' . LOGO_IMAGE_PATH; // Default assumption
                 }
             ?>
             <a href="index.php" title="whatJacket Home"><img src="<?php echo htmlspecialchars($logo_web_path); ?>" alt="whatJacket - Weather Based Clothing Suggestions"></a>
        </div>

        <!-- Show form only if results are NOT being shown -->
        <?php if (!$show_results): ?>
            <p style="text-align: center; margin-bottom: 20px;">Enter your US ZIP code and select an activity category for clothing suggestions based on the weather forecast.</p>
            <hr>

            <!-- Location Input Form -->
            <form action="index.php" method="post" id="locationForm" class="location-form">

                <label for="category">1. Select Clothing Category:</label>
                <select id="category" name="category" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach (CATEGORIES as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($selected_category === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                 <!-- Section for ZIP input and manual submit -->
                <div class="form-section form-section-zip">
                    <label for="zip">2. Enter US ZIP Code (5 digits):</label>
                    <input type="text" id="zip" name="zip" pattern="\d{5}" title="Enter a 5-digit US ZIP code" placeholder="e.g., 90210" value="<?php echo htmlspecialchars($zip ?? ''); ?>" required inputmode="numeric">
                    <button type="submit" title="Get Suggestion based on ZIP/Category">
                        <span class="icon">🧥</span> Get Suggestion <!-- Updated Icon -->
                    </button>
                 </div>
            </form>

            <!-- Display Messages if form is shown (errors/warnings only) -->
            <?php if ($error_message): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if ($warning_message): ?>
                <div class="message warning"><?php echo $warning_message; ?></div>
            <?php endif; ?>
             <?php if ($attribution_message): // Show attribution below form too if no results ?>
                 <div class="attribution form-attribution">
                     <?php echo $attribution_message; ?>
                 </div>
            <?php endif; ?>


        <?php endif; // End hiding form ?>


        <!-- Display Results -->
        <?php if ($show_results): ?>
            <div class="results">

                 <!-- Display Messages if results are shown (errors/warnings only) -->
                <?php if ($error_message): ?>
                    <div class="message error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                 <?php if ($warning_message): ?>
                    <div class="message warning"><?php echo $warning_message; ?></div>
                 <?php endif; ?>


                <!-- Current Info Section -->
                <div class="current-info">
                     <?php
                         $simple_condition_key = get_simple_condition_key(
                            $forecast['next_hour']['short_forecast'] ?? '',
                            $forecast['next_hour']['wind_speed'] ?? 0
                         );
                         // Use the key to get a user-friendly display name if defined, else use the key itself
                         $simple_condition_display = SIMPLE_CONDITION_DISPLAY[$simple_condition_key] ?? ucfirst($simple_condition_key);
                     ?>
                    <span class="info-item"><strong>Location:</strong> <?php echo htmlspecialchars($zip); ?></span>
                    <span class="info-item"><strong>Category:</strong> <?php echo htmlspecialchars($selected_category); ?></span>
                    <span class="info-item"><strong>Now:</strong> <?php echo round($forecast['next_hour']['temperature_f']); ?>°F</span>
                    <span class="info-item condition-<?php echo strtolower($simple_condition_key); ?>"> <!-- Add class for potential styling -->
                        <?php echo htmlspecialchars($simple_condition_display); ?>
                        <span class="details">(<?php echo $forecast['next_hour']['precip_prob']; ?>% Precip, <?php echo $forecast['next_hour']['wind_speed']; ?> mph Wind)</span>
                    </span>
                    <?php if ($forecast['next_hour']['icon']): ?>
                         <img src="<?php echo htmlspecialchars($forecast['next_hour']['icon']); ?>" alt="Weather icon" class="weather-icon-small" title="<?php echo htmlspecialchars($forecast['next_hour']['short_forecast']); ?>">
                    <?php endif; ?>
                </div>
                <hr>

                <h2>You should wear a:</h2>

                <div class="clothing-suggestions">
                    <!-- Render Prominent Item -->
                    <div class="prominent-item">
                        <?php echo $prominent_item_html ?? '<p>No specific jacket/coat suggested based on conditions.</p>'; ?>
                    </div>

                    <!-- Render Other Items by NEW Display Groups -->
                    <h3 style="margin-top: 25px;">Other things you should wear:</h3>
                    <div class="other-items">
                        <?php
                        // Define the order to display the groups
                        $display_group_order = ['Tops', 'Bottoms', 'Accessories'];
                        $other_items_found = false; // Flag to check if any item exists in these groups

                        foreach ($display_group_order as $group_name) {
                            // Check if the group exists and has items
                            if (!empty($display_groups[$group_name])) {
                                $other_items_found = true; // Mark that we found items
                                echo '<h4>' . htmlspecialchars($group_name) . '</h4>';
                                echo '<div class="clothing-type-group">'; // Start the flex container for this group

                                foreach ($display_groups[$group_name] as $item_key) {
                                     if ($item_key === 'placeholder') continue; // Skip placeholder explicitly
                                     if (!isset(CLOTHING_ITEMS[$item_key])) { // Safety check
                                         log_error("Item key '$item_key' found in display_groups['$group_name'] but not in CLOTHING_ITEMS.");
                                         continue;
                                     }
                                     $item_details = CLOTHING_ITEMS[$item_key];
                                     echo '<div class="clothing-item">'
                                         . '<img src="' . get_image_path($item_key) . '" alt="' . htmlspecialchars($item_details['name']) . '">' // Image
                                         . '<span>' . htmlspecialchars($item_details['name']) . '</span>' // Name
                                         . '</div>';
                                }
                                echo '</div>'; // End the flex container
                            }
                        }

                         // Display a message only if NO other items were found across all groups
                         if (!$other_items_found && $prominent_item_html) {
                             // Only show if prominent item exists but no others
                             echo '<p style="text-align: center; margin-top: 15px;">No additional items suggested beyond the top layer.</p>';
                         } elseif (!$other_items_found && !$prominent_item_html) {
                             // This case means absolutely nothing was suggested (should be covered by warning message usually)
                              echo '<p style="text-align: center; margin-top: 15px;">No clothing items could be suggested.</p>';
                         }

                        ?>
                    </div> <!-- /other-items -->

                </div> <!-- /clothing-suggestions -->


                <!-- Forecast Summary (with dynamic background) -->
                <div class="forecast-summary <?php echo $forecast_bg_class; ?>" <?php echo $forecast_bg_style; ?>>
                    <div class="forecast-content"> <!-- Added wrapper for content over overlay -->
                        <h3>Forecast Details (Next 12 Hours)</h3>
                        <?php
                             try {
                                // Attempt to use default system timezone if set, otherwise fallback to UTC
                                $local_tz_str = date_default_timezone_get() ?: 'UTC';
                                $local_tz = new DateTimeZone($local_tz_str);

                                // Parse start/end times, assuming they are UTC from the API
                                $start_utc = new DateTime($forecast['twelve_hour_summary']['start_time'] ?? 'now', new DateTimeZone('UTC'));
                                $end_utc = new DateTime($forecast['twelve_hour_summary']['end_time'] ?? 'now + 12 hours', new DateTimeZone('UTC'));

                                // Convert to local timezone for display
                                $start_local = $start_utc->setTimezone($local_tz);
                                $end_local = $end_utc->setTimezone($local_tz);

                                $start_time_str = $start_local->format('g:i A T'); // e.g., 3:00 PM EST
                                $end_time_str = $end_local->format('g:i A T');     // e.g., 3:00 AM EST

                             } catch (Exception $e) {
                                 log_error("Error formatting forecast times: " . $e->getMessage() . " | StartTime: " . ($forecast['twelve_hour_summary']['start_time'] ?? 'N/A') . " | EndTime: " . ($forecast['twelve_hour_summary']['end_time'] ?? 'N/A'));
                                 $start_time_str = "N/A";
                                 $end_time_str = "N/A";
                             }
                        ?>
                        <!-- <p><strong>Timeframe:</strong> approx <?php echo $start_time_str; ?> to <?php echo $end_time_str; ?></p> -->&nbsp;
                        <p><strong>Temperature Range:</strong> <?php echo ($forecast['twelve_hour_summary']['min_temp_f'] !== null) ? round($forecast['twelve_hour_summary']['min_temp_f']).'°F' : 'N/A'; ?>
                            to <?php echo ($forecast['twelve_hour_summary']['max_temp_f'] !== null) ? round($forecast['twelve_hour_summary']['max_temp_f']).'°F' : 'N/A'; ?>
                        </p>
                        <p><strong>Max Precipitation Chance:</strong> <?php echo $forecast['twelve_hour_summary']['max_precip_prob']; ?>%</p>
                        <p><strong>Max Wind Speed:</strong> <?php echo $forecast['twelve_hour_summary']['max_wind_speed']; ?> mph</p>
                         <p><i>Conditions may include: <?php echo !empty($forecast['twelve_hour_summary']['conditions']) ? htmlspecialchars(implode(', ', $forecast['twelve_hour_summary']['conditions'])) : 'N/A'; ?></i></p>
                    </div> <!-- /forecast-content -->
                </div>

            </div> <!-- /results -->
        <?php endif; ?>

    </div> <!-- /container -->

    <!-- Footer -->
    <footer>
         <div class="footer-content">
             <!-- Home Button -->
             <a href="index.php" class="footer-home-button" title="Start Over / New Location">
                 <span class="icon">🏠</span> <!-- House icon -->
                 <span class="button-text">Home</span>
            </a>
        </div>

         <!-- Footer Links & Copyright -->
        <div class="footer-links">
             <!-- Display Attribution Message Here (If Set) -->
             <?php if ($attribution_message): ?>
                 <div class="attribution">
                     <!-- Combine attribution and copyright -->
                     <p>© <?php echo date('Y'); ?> whatJacket | Weather: NOAA/NWS | Geocoding: <?php echo $attribution_message; ?></p>
                 </div>
             <?php else: ?>
                  <div class="attribution">
                     <!-- Show standard copyright if no specific attribution -->
                     <p>© <?php echo date('Y'); ?> whatJacket | Weather data courtesy of NOAA/NWS.</p>
                 </div>
             <?php endif; ?>

            <!-- Standard Footer Links -->
            <p class="external-links">
                <?php foreach (FOOTER_LINKS as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo htmlspecialchars($link['text']); ?>
                    </a>
                <?php endforeach; ?>
            </p>

        </div>
    </footer>

</body>
</html>
