<?php // FILE: config.php

/**
 * Configuration for whatJacket Application v0.8.2
 *
 * All user-modifiable settings should be placed here.
 */

// --- API Settings ---
define('API_USER_AGENT', 'whatJacketApp/0.8.2 (abuse@timnetworks.com)');
define('NOAA_API_BASE_URL', 'https://api.weather.gov');
define('GEOCODING_API_BASE_URL', 'https://nominatim.openstreetmap.org/search');

// --- Application Settings ---
define('APP_VERSION', '0.8.1');
define('APP_TITLE', 'whatJacket');
define('APP_NAME_SHORT', 'whatJacket');
define('APP_URL', 'https://whatjacket.timnetworks.net/');
define('LOGO_IMAGE_PATH', './img/wj-logo.png');
define('OG_IMAGE_PATH', './img/wj-logo-og.png');
define('DEFAULT_TEMP_UNIT', 'F'); // 'F' or 'C'
define('DEFAULT_CATEGORY', 'Casual');

// --- UI Theme ---
define('THEME_COLOR', '#007bff');       // Browser UI chrome
define('BACKGROUND_COLOR', '#f0e68c'); // Manifest background

// --- Activity Categories & Icons (Example Icons - Use real paths/classes) ---
// Assuming Font Awesome is NOT used based on user's HTML head, provide simple text/emoji fallbacks
define('CATEGORIES', [
    'Casual' => ['label' => 'Casual', 'icon' => '🧑', 'img' => './img/icons/casual.png'], // Added image path example
    'Professional' => ['label' => 'Professional', 'icon' => '💼', 'img' => './img/icons/professional.png'],
    'Hiking' => ['label' => 'Hiking', 'icon' => '🚶', 'img' => './img/icons/hiking.png'], // Using walking emoji
    'Tourism' => ['label' => 'Tourism', 'icon' => '📷', 'img' => './img/icons/tourism.png'],
]);

// --- Temperature Bands (Revised & Expanded) ---
// Values roughly based on Celsius for definition, Fahrenheit calculated.
// Added 'target_thermal_score' as a *starting point* for layering logic.
// Activity/wind/wetness will modify the required score.
define('TEMP_BANDS', [
    //         C Range        F Range          Target Score (Base)
    'Dangerous' => ['min_c' => 35, 'min_f' => 95,  'target_thermal_score' => 0],   // >= 35C / 95F
    'Hot'       => ['min_c' => 27, 'max_c' => 34.9,'min_f' => 81, 'max_f' => 94.9,'target_thermal_score' => 0],   // 27-34.9C / 81-94.9F
    'Warm'      => ['min_c' => 20, 'max_c' => 26.9,'min_f' => 68, 'max_f' => 80.9,'target_thermal_score' => 1],   // 20-26.9C / 68-80.9F
    'Mild'      => ['min_c' => 13, 'max_c' => 19.9,'min_f' => 55, 'max_f' => 67.9,'target_thermal_score' => 2],   // 13-19.9C / 55-67.9F
    'Crisp'     => ['min_c' => 7,  'max_c' => 12.9,'min_f' => 45, 'max_f' => 54.9,'target_thermal_score' => 4],   // 7-12.9C / 45-54.9F
    'Cold'      => ['min_c' => 0,  'max_c' => 6.9, 'min_f' => 32, 'max_f' => 44.9,'target_thermal_score' => 6],   // 0-6.9C / 32-44.9F
    'Frigid'    => ['min_c' => -10,'max_c' => -0.1,'min_f' => 14, 'max_f' => 31.9,'target_thermal_score' => 8],  // -10 to -0.1C / 14-31.9F
    'Freezing'  => [             'max_c' => -10.1,            'max_f' => 13.9,'target_thermal_score' => 10]  // <= -10.1C / 13.9F (Overlap handled by logic)
]);

// --- Condition Identification Keywords (Used in get_processed_forecast) ---
define('CONDITION_KEYWORDS', [
    'is_severe' => ['severe', 'hurricane', 'tornado', 'tropical storm', 'blizzard', 'dangerous', 'warning', 'watch'],
    'is_thunderstorm' => ['thunderstorm', 't-storm'],
    'is_snowy' => ['snow', 'sleet', 'ice', 'flurries', 'winter mix'], // 'ice', 'blizzard' also covered by severe
    'is_rainy' => ['rain', 'showers', 'precipitation', 'storm'], // General rain
    'is_drizzling' => ['drizzle', 'light rain', 'mist'],
    'is_foggy' => ['fog', 'haze', 'misty'], // Separate from drizzle
    'is_windy' => ['windy', 'breezy', 'gusts'], // Also check MPH threshold
    'is_cloudy' => ['cloudy', 'overcast', 'clouds'],
    'is_sunny' => ['sunny', 'clear'],
    'is_scorching' => [], // Primarily driven by 'Dangerous' temp band
]);

// --- Condition Thresholds ---
define('UMBRELLA_MAX_WIND_MPH', 18);
define('WINDY_THRESHOLD_MPH', 15);       // MPH to trigger 'is_windy' flag regardless of keywords
define('RAIN_PROBABILITY_THRESHOLD', 30); // % chance to consider it potentially wet
define('HEAVY_RAIN_PROBABILITY_THRESHOLD', 60); // % chance for heavier rain gear / 'is_very_wet' flag

// --- Standardized Footer Links ---
define('FOOTER_LINKS', [
    ['text' => 'NOAA API', 'url' => 'https://www.weather.gov/documentation/services-web-api'],
    ['text' => 'Nominatim/OSM', 'url' => 'https://operations.osmfoundation.org/policies/nominatim/'],
    ['text' => 'project on GitHub', 'url' => 'https://github.com/timnetworks/whatjacket']
]);

// --- Forecast Background Images (Map simplified condition keys) ---
define('FORECAST_BACKGROUNDS', [
    'Severe'   => './img/backgrounds/severe.jpg',
    'Thunderstorm' => './img/backgrounds/severe.jpg', // Reuse severe
    'Snowy'    => './img/backgrounds/snowy.jpg',
    'Rainy'    => './img/backgrounds/rainy.jpg',     // Covers heavy rain/showers
    'Drizzling'=> './img/backgrounds/rainy.jpg',     // Reuse rainy
    'Windy'    => './img/backgrounds/windy.jpg',
    'Foggy'    => './img/backgrounds/cloudy.jpg',    // Reuse cloudy
    'Cloudy'   => './img/backgrounds/cloudy.jpg',
    'Sunny'    => './img/backgrounds/sunny.jpg',     // Clear/Sunny
    'Variable' => './img/backgrounds/variable.jpg',  // Default/Mixed/Partly Cloudy
    'Scorching'=> './img/backgrounds/sunny.jpg',     // Reuse sunny
]);

// --- Simplified Condition Display Names ---
define('SIMPLE_CONDITION_DISPLAY', [
    'Severe'   => 'Severe Weather',
    'Thunderstorm' => 'Thunderstorms',
    'Snowy'    => 'Snowy',
    'Rainy'    => 'Rainy',
    'Drizzling'=> 'Drizzle/Mist',
    'Windy'    => 'Windy',
    'Foggy'    => 'Foggy/Misty',
    'Cloudy'   => 'Cloudy',
    'Sunny'    => 'Sunny/Clear',
    'Variable' => 'Variable Conditions',
    'Scorching'=> 'Scorching Heat', // Driven by temp band mainly
]);

// --- Mapping from Item Type to Display Group ---
define('TYPE_TO_DISPLAY_GROUP_MAP', [
    // Tops
    'shirt'     => 'Tops', 'sweater'   => 'Tops', 'jacket'    => 'Tops', 'coat'      => 'Tops',
    // Bottoms
    'base_pants'=> 'Bottoms', 'pants'     => 'Bottoms', 'shorts'    => 'Bottoms',
    // Footwear
    'socks'     => 'Footwear', 'shoes'     => 'Footwear', 'sneakers'  => 'Footwear', 'boots'     => 'Footwear',
    // Accessories
    'hat'       => 'Accessories', 'gloves'    => 'Accessories', 'scarf'     => 'Accessories',
    'umbrella'  => 'Accessories', 'sunglasses'=> 'Accessories', 'accessory' => 'Accessories',
]);

// --- Clothing Item Definitions v2.1 ---
/* Added 'thermal_value' (approximate):
 * 0: None (T-shirt, shorts, rain shell)
 * 1: Light (Long sleeve shirt, light sweater, chinos, sneakers, light socks)
 * 2: Medium (Fleece, jeans, standard socks, dress shoes)
 * 3: Heavy (Heavy sweater, wool socks, light insulated jacket)
 * 4: Very Heavy (Parka, insulated pants, heavy boots, heavy gloves)
 * Note: This is simplified. Real-world values vary greatly. Used for relative comparison.
*/
define('CLOTHING_ITEMS', [

    // === TOPS ===
    // -- Base Layer Shirts --
    'shirt-base-tee' => [
        'name' => 'White Tee', 'type' => 'shirt', 'layer' => 'base', 'category' => ['Casual','Tourism','Hiking','Professional'],
        'temp_bands' => ['Dangerous', 'Hot', 'Warm', 'Mild', 'Crisp', 'Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'none', 'insulation' => 'none', 'thermal_value' => 0,
        'wind_resistance' => 'none', 'breathability' => 'high', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_tee.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
    'shirt-base-casual-light' => [
        'name' => 'Casual Shirt', 'type' => 'shirt', 'layer' => 'base', 'category' => ['Casual','Tourism','Hiking','Professional'],
        'temp_bands' => ['Dangerous', 'Hot', 'Warm'], 'water_resistance' => 'none', 'insulation' => 'none', 'thermal_value' => 0,
        'wind_resistance' => 'none', 'breathability' => 'high', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_casual_light.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
    'shirt-base-casual-medium' => [
        'name' => 'Long Sleeve Base', 'type' => 'shirt', 'layer' => 'base', 'category' => ['Casual','Tourism','Hiking'],
        'temp_bands' => ['Mild', 'Crisp', 'Cold'], 'water_resistance' => 'none', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'none', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_casual_medium.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
    'shirt-base-casual-heavy' => [
        'name' => 'Long Sleeve Base', 'type' => 'shirt', 'layer' => 'base', 'category' => ['Casual','Tourism','Hiking'],
        'temp_bands' => ['Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'none', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'none', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_casual_heavy.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
     'shirt-base-professional-light' => [
        'name' => 'Light Dress Shirt', 'type' => 'shirt', 'layer' => 'base', 'category' => 'Professional',
        'temp_bands' => ['Dangerous', 'Hot'], 'water_resistance' => 'none', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'none', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_professional_light.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
     'shirt-base-professional-medium' => [
        'name' => 'Medium Dress Shirt', 'type' => 'shirt', 'layer' => 'base', 'category' => 'Professional',
        'temp_bands' => ['Warm', 'Mild', 'Crisp'], 'water_resistance' => 'none', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'none', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_professional_medium.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
     'shirt-base-professional-heavy' => [
        'name' => 'Heavy Dress Shirt', 'type' => 'shirt', 'layer' => 'base', 'category' => 'Professional',
        'temp_bands' => ['Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'none', 'insulation' => 'medium', 'thermal_value' => 2,
        'wind_resistance' => 'none', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_professional_heavy.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
    'shirt-base-hiking-light-sun' => [
        'name' => 'Sleeveless Shirt', 'type' => 'shirt', 'layer' => 'base', 'category' => 'Hiking',
        'temp_bands' => ['Hot', 'Warm', 'Mild', 'Crisp'], 'water_resistance' => 'none', 'insulation' => 'none', 'thermal_value' => 0,
        'wind_resistance' => 'light', 'breathability' => 'high', 'sun_protection' => true, 'special_conditions' => [],
        'img' => './img/shirt_hiking_light.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
     'shirt-base-hiking-medium' => [
        'name' => 'Hiking Base Layer (Med)', 'type' => 'shirt', 'layer' => 'base', 'category' => 'Hiking',
        'temp_bands' => ['Warm', 'Mild', 'Crisp', 'Cold'], 'water_resistance' => 'none', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'none', 'breathability' => 'high', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_hiking_medium.jpg', 'img_fallback' => './img/shirt.jpg'
    ],
     'shirt-base-thermal' => [
        'name' => 'Thermal Base Layer', 'type' => 'shirt', 'layer' => 'base',
        'category' => ['Hiking', 'Casual', 'Tourism'],
        'temp_bands' => ['Mild', 'Crisp', 'Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'none', 'insulation' => 'medium', 'thermal_value' => 2,
        'wind_resistance' => 'none', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shirt_base_thermal.jpg', 'img_fallback' => './img/shirt.jpg'
    ],

    // -- Mid Layer Sweaters/Fleece --
    'sweater-mid-casual-light' => [
        'name' => 'Light Hoodie', 'type' => 'sweater', 'layer' => 'mid', 'category' => ['Casual', 'Tourism', 'Hiking'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp'], 'water_resistance' => 'none', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'light', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/sweater_casual_light.jpg', 'img_fallback' => './img/sweater.jpg'
    ],
    'sweater-mid-casual-medium' => [
        'name' => 'Sweatshirt', 'type' => 'sweater', 'layer' => 'mid', 'category' => ['Casual', 'Tourism', 'Hiking'],
        'temp_bands' => ['Mild', 'Crisp', 'Cold'], 'water_resistance' => 'light', 'insulation' => 'medium', 'thermal_value' => 2,
        'wind_resistance' => 'medium', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/sweater_casual_medium.jpg', 'img_fallback' => './img/sweater.jpg'
    ],
    'sweater-mid-casual-heavy' => [
        'name' => 'Heavy Sweater', 'type' => 'sweater', 'layer' => 'mid', 'category' => ['Casual', 'Tourism', 'Hiking'],
        'temp_bands' => ['Crisp', 'Cold', 'Frigid'], 'water_resistance' => 'light', 'insulation' => 'heavy', 'thermal_value' => 3,
        'wind_resistance' => 'medium', 'breathability' => 'low', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/sweater_casual_heavy.jpg', 'img_fallback' => './img/sweater.jpg'
    ],
    'sweater-mid-professional' => [
        'name' => 'Professional Sweater', 'type' => 'sweater', 'layer' => 'mid', 'category' => 'Professional',
        'temp_bands' => ['Mild', 'Crisp', 'Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'light', 'insulation' => 'medium', 'thermal_value' => 2,
        'wind_resistance' => 'light', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/sweater_professional_medium.jpg', 'img_fallback' => './img/sweater.jpg'
    ],

    // -- Outer Layer Jackets/Coats -- (Less critical for *core* problem, but reviewed)
    'jacket-outer-casual-windbreaker' => [
        'name' => 'Windbreaker', 'type' => 'jacket', 'layer' => 'outer', 'category' => ['Casual', 'Tourism', 'Hiking'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp'], 'water_resistance' => 'light', 'insulation' => 'none', 'thermal_value' => 0,
        'wind_resistance' => 'proof', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/windbreaker_casual_light.jpg', 'img_fallback' => './img/jacket.jpg'
    ],
    'jacket-outer-casual-light' => [
        'name' => 'Light Casual Jacket', 'type' => 'jacket', 'layer' => 'outer', 'category' => ['Casual', 'Tourism'],
        'temp_bands' => ['Mild', 'Crisp'], 'water_resistance' => 'light', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'light', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/jacket_casual_light.jpg', 'img_fallback' => './img/jacket.jpg'
    ],
     'jacket-outer-general-rain' => [
        'name' => 'Raincoat', 'type' => 'jacket', 'layer' => 'outer', 'category' => ['Casual', 'Professional', 'Tourism', 'Hiking'],
        'temp_bands' => ['Hot', 'Warm', 'Mild', 'Crisp', 'Cold'], 'water_resistance' => 'proof', 'insulation' => 'none', 'thermal_value' => 0,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'sun_protection' => false, 'special_conditions' => ['rain', 'drizzle'],
        'img' => './img/raincoat_general.jpg', 'img_fallback' => './img/jacket.jpg'
    ],
    'jacket-outer-casual-medium' => [
        'name' => 'Medium Casual Jacket', 'type' => 'jacket', 'layer' => 'outer', 'category' => ['Casual', 'Tourism'],
        'temp_bands' => ['Mild', 'Crisp', 'Cold'], 'water_resistance' => 'light', 'insulation' => 'medium', 'thermal_value' => 2,
        'wind_resistance' => 'medium', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/jacket_casual_medium.jpg', 'img_fallback' => './img/jacket.jpg'
    ],
    'coat-outer-casual-heavy' => [
        'name' => 'Heavy Casual Coat', 'type' => 'coat', 'layer' => 'outer', 'category' => ['Casual', 'Tourism'],
        'temp_bands' => ['Crisp', 'Cold', 'Frigid'], 'water_resistance' => 'resistant', 'insulation' => 'heavy', 'thermal_value' => 3,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/coat_casual_heavy.jpg', 'img_fallback' => './img/coat.jpg'
    ],
    'coat-outer-professional-light' => [
        'name' => 'Trench Coat/Light Overcoat', 'type' => 'coat', 'layer' => 'outer', 'category' => 'Professional',
        'temp_bands' => ['Warm', 'Mild', 'Crisp'], 'water_resistance' => 'resistant', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'medium', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/jacket_professional_light.jpg', 'img_fallback' => './img/coat.jpg'
    ],
    'coat-outer-professional-medium' => [
        'name' => 'Wool Overcoat', 'type' => 'coat', 'layer' => 'outer', 'category' => 'Professional',
        'temp_bands' => ['Mild', 'Crisp', 'Cold'], 'water_resistance' => 'light', 'insulation' => 'medium', 'thermal_value' => 3,
        'wind_resistance' => 'medium', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/jacket_professional_medium.jpg', 'img_fallback' => './img/coat.jpg'
    ],
    'coat-outer-professional-heavy' => [
        'name' => 'Heavy Insulated Topcoat', 'type' => 'coat', 'layer' => 'outer', 'category' => 'Professional',
        'temp_bands' => ['Crisp', 'Cold', 'Frigid'], 'water_resistance' => 'resistant', 'insulation' => 'heavy', 'thermal_value' => 4,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/jacket_professional_heavy.jpg', 'img_fallback' => './img/coat.jpg'
    ],
     'coat-outer-parka-heavy' => [
        'name' => 'Parka', 'type' => 'coat', 'layer' => 'outer', 'category' => ['Casual', 'Tourism', 'Professional'],
        'temp_bands' => ['Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'proof', 'insulation' => 'heavy', 'thermal_value' => 4,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'sun_protection' => false, 'special_conditions' => ['snow', 'severe'],
        'img' => './img/parka_casual_heavy.jpg', 'img_fallback' => './img/coat.jpg'
    ],
     'jacket-outer-hiking-light-rain' => [
        'name' => 'Hiking Rain Shell', 'type' => 'jacket', 'layer' => 'outer', 'category' => 'Hiking',
        'temp_bands' => ['Hot', 'Warm', 'Mild', 'Crisp'], 'water_resistance' => 'proof', 'insulation' => 'none', 'thermal_value' => 0,
        'wind_resistance' => 'proof', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => ['rain', 'drizzle'],
        'img' => './img/jacket_hiking_light_rain.jpg', 'img_fallback' => './img/jacket.jpg'
    ],
     'jacket-outer-hiking-heavy' => [
        'name' => 'Insulated Hiking Jacket', 'type' => 'jacket', 'layer' => 'outer', 'category' => 'Hiking',
        'temp_bands' => ['Crisp', 'Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'proof', 'insulation' => 'heavy', 'thermal_value' => 4,
        'wind_resistance' => 'proof', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => ['snow', 'rain', 'severe'],
        'img' => './img/jacket_hiking_heavy.jpg', 'img_fallback' => './img/jacket.jpg'
    ],

    // === BOTTOMS ===
    // -- Base Layer Bottoms --
    'base_pants-thermal' => [
        'name' => 'Long Johns', 'type' => 'base_pants', 'layer' => 'base', 'category' => ['Casual', 'Tourism', 'Professional', 'Hiking'],
        'temp_bands' => ['Crisp', 'Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'none', 'insulation' => 'medium', 'thermal_value' => 2,
        'wind_resistance' => 'none', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/pants_waffled.jpg', 'img_fallback' => './img/pants.jpg'
    ],

    // -- Outer Layer Bottoms --
    'shorts-outer-casual' => [
        'name' => 'Shorts', 'type' => 'shorts', 'layer' => 'outer', 'category' => ['Casual', 'Tourism', 'Hiking'],
        'temp_bands' => ['Dangerous', 'Hot', 'Warm'], 'water_resistance' => 'none', 'insulation' => 'none', 'thermal_value' => 0,
        'wind_resistance' => 'none', 'breathability' => 'high', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/shorts_casual.jpg', 'img_fallback' => './img/pants.jpg'
    ],
    'pants-outer-casual-jeans' => [
        'name' => 'Jeans', 'type' => 'pants', 'layer' => 'outer', 'category' => ['Casual', 'Tourism'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp', 'Cold'], 'water_resistance' => 'light', 'insulation' => 'light', 'thermal_value' => 2,
        'wind_resistance' => 'medium', 'breathability' => 'low', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/jeans_casual.jpg', 'img_fallback' => './img/pants.jpg'
    ],
    'pants-outer-casual-chinos' => [
        'name' => 'Chinos/ Khakis', 'type' => 'pants', 'layer' => 'outer', 'category' => ['Casual', 'Professional', 'Tourism'],
        'temp_bands' => ['Warm', 'Mild'], 'water_resistance' => 'light', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'light', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/chinos_casual_professional.jpg', 'img_fallback' => './img/pants.jpg'
    ],
     'pants-outer-casual-heavy-chinos' => [
        'name' => 'Heavy Chinos', 'type' => 'pants', 'layer' => 'outer', 'category' => ['Casual', 'Professional', 'Tourism'],
        'temp_bands' => ['Crisp', 'Cold'], 'water_resistance' => 'resistant', 'insulation' => 'medium', 'thermal_value' => 2,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'special_conditions' => [],
        'img' => './img/chinos_casual_heavy.jpg', 'img_fallback' => './img/pants.jpg'
    ],
    'pants-outer-professional-slacks-light' => [
        'name' => 'Dress Slacks', 'type' => 'pants', 'layer' => 'outer', 'category' => 'Professional',
        'temp_bands' => ['Dangerous', 'Hot','Warm', 'Mild'], 'water_resistance' => 'none', 'insulation' => 'light', 'thermal_value' => 1,
        'wind_resistance' => 'light', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/slacks_professional_light.jpg', 'img_fallback' => './img/pants.jpg'
    ],
    'pants-outer-professional-slacks-heavy' => [
        'name' => 'Heavy Slacks', 'type' => 'pants', 'layer' => 'outer', 'category' => 'Professional',
        'temp_bands' => ['Crisp', 'Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'light', 'insulation' => 'medium', 'thermal_value' => 3,
        'wind_resistance' => 'medium', 'breathability' => 'medium', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/slacks_professional_medium.jpg', 'img_fallback' => './img/pants.jpg'
    ],
     'pants-outer-hiking-light' => [
        'name' => 'Light Hiking Pants', 'type' => 'pants', 'layer' => 'outer', 'category' => 'Hiking',
        'temp_bands' => ['Hot', 'Warm', 'Mild', 'Crisp', 'Cold'], 'water_resistance' => 'light', 'insulation' => 'none', 'thermal_value' => 1,
        'wind_resistance' => 'medium', 'breathability' => 'high', 'sun_protection' => true,
        'img' => './img/pants_hiking_light.jpg', 'img_fallback' => './img/pants.jpg'
    ],
     'pants-outer-hiking-medium' => [
        'name' => 'Standard Hiking Pants', 'type' => 'pants', 'layer' => 'outer', 'category' => 'Hiking',
        'temp_bands' => ['Mild', 'Crisp', 'Cold'], 'water_resistance' => 'resistant', 'insulation' => 'light', 'thermal_value' => 2,
        'wind_resistance' => 'proof', 'breathability' => 'medium', 'sun_protection' => true,
        'img' => './img/pants_hiking_medium.jpg', 'img_fallback' => './img/pants.jpg'
    ],
    'pants-outer-hiking-heavy' => [
        'name' => 'Insulated Hiking Pants', 'type' => 'pants', 'layer' => 'outer', 'category' => 'Hiking',
        'temp_bands' => ['Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'proof', 'insulation' => 'medium', 'thermal_value' => 4,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'sun_protection' => false, 'special_conditions' => [],
        'img' => './img/pants_hiking_heavy.jpg', 'img_fallback' => './img/pants.jpg'
    ],
    'pants-outer-rain' => [
        'name' => 'Rain Pants', 'type' => 'pants', 'layer' => 'outer', 'category' => ['Hiking', 'Casual', 'Tourism'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp', 'Cold', 'Frigid'], 'water_resistance' => 'resistant', 'insulation' => 'none', 'thermal_value' => 0,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'special_conditions' => [],
        'img' => './img/pants_rain.jpg', 'img_fallback' => './img/pants.jpg'
    ],
    'pants--outer-insulated' => [
        'name' => 'Insulated Pants', 'type' => 'pants', 'layer' => 'outer', 'category' => ['Hiking', 'Casual', 'Tourism'],
        'temp_bands' => ['Frigid', 'Freezing'], 'water_resistance' => 'proof', 'insulation' => 'heavy', 'thermal_value' => 4,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'special_conditions' => [],
        'img' => './img/pants_insulated.jpg', 'img_fallback' => './img/pants.jpg'
    ],

    // === FOOTWEAR ===
    // -- Socks (Single Layer) --
    'socks-light' => [
        'name' => 'Light Socks', 'type' => 'socks', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Tourism', 'Hiking'],
        'temp_bands' => ['Hot', 'Warm', 'Mild'], 'insulation' => 'none', 'thermal_value' => 0,
        'img' => './img/socks_light.jpg', 'img_fallback' => './img/socks.jpg'
    ],
    'socks-medium' => [
        'name' => 'Tube Socks', 'type' => 'socks', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Tourism', 'Hiking'],
        'temp_bands' => ['Mild', 'Crisp', 'Cold'], 'insulation' => 'light', 'thermal_value' => 1,
        'img' => './img/socks_medium.jpg', 'img_fallback' => './img/socks.jpg'
    ],
    'socks-heavy-wool' => [
        'name' => 'Heavy Socks', 'type' => 'socks', 'layer' => 'single', 'category' => ['Casual', 'Tourism', 'Hiking', 'Professional'],
        'temp_bands' => ['Cold', 'Frigid', 'Freezing'], 'insulation' => 'medium', 'thermal_value' => 2,
        'img' => './img/socks_heavy.jpg', 'img_fallback' => './img/socks.jpg'
    ],

    // -- Shoes (Single Layer) --
    'sandals-casual-light' => [
        'name' => 'Sandals', 'type' => 'sneakers', 'layer' => 'single', 'category' => ['Casual', 'Tourism'],
        'temp_bands' => ['Dangerous', 'Hot'], 'water_resistance' => 'none', 'breathability' => 'high', 'thermal_value' => 0,
        'img' => './img/sandals.jpg', 'img_fallback' => './img/sandals.jpg'
    ],
     'sneakers-casual-standard' => [
        'name' => 'Daily Sneakers', 'type' => 'sneakers', 'layer' => 'single',
        'category' => ['Casual', 'Tourism', 'Hiking'],
        'temp_bands' => ['Hot', 'Warm', 'Mild', 'Crisp'], 'water_resistance' => 'none', 'breathability' => 'medium', 'thermal_value' => 1,
        'img' => './img/shoes_casual.jpg', 'img_fallback' => './img/sneakers.jpg'
    ],
     'sneakers-casual-resistant' => [
        'name' => 'Weatherproof Sneakers', 'type' => 'sneakers', 'layer' => 'single',
        'category' => ['Casual', 'Tourism', 'Hiking'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp', 'Cold'], 'water_resistance' => 'light', 'breathability' => 'low', 'wind_resistance' => 'medium', 'thermal_value' => 2,
        'img' => './img/shoes_casual_medium.jpg', 'img_fallback' => './img/sneakers.jpg'
    ],
    'shoes-professional-dress' => [
        'name' => 'Dress Shoes', 'type' => 'shoes', 'layer' => 'single', 'category' => ['Professional', 'Tourism'],
        'temp_bands' => ['Dangerous', 'Hot', 'Warm', 'Mild', 'Crisp', 'Cold'], 'water_resistance' => 'light', 'breathability' => 'low', 'thermal_value' => 1,
        'img' => './img/shoes_professional_light.jpg', 'img_fallback' => './img/shoes.jpg'
    ],
    'shoes-professional-leather' => [
        'name' => 'Leather Boots', 'type' => 'shoes', 'layer' => 'single', 'category' => ['Professional', 'Tourism'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp', 'Cold', 'Frigid'], 'water_resistance' => 'resistant', 'breathability' => 'low', 'wind_resistance' => 'medium', 'insulation' => 'light', 'thermal_value' => 2,
        'img' => './img/shoes_professional_medium.jpg', 'img_fallback' => './img/shoes.jpg'
    ],
     'boots-hiking' => [
        'name' => 'Hiking Boots', 'type' => 'boots', 'layer' => 'single', 'category' => ['Hiking', 'Casual'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp', 'Cold', 'Frigid'], 'water_resistance' => 'resistant', 'breathability' => 'medium', 'insulation' => 'light', 'wind_resistance' => 'medium', 'thermal_value' => 3,
        'special_conditions' => ['rain', 'snow'],
        'img' => './img/shoes_boots_hiking.jpg', 'img_fallback' => './img/boots.jpg'
    ],
     'boots-insulated' => [
        'name' => 'Winter Boots', 'type' => 'boots', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Hiking', 'Tourism'],
        'temp_bands' => ['Crisp', 'Cold', 'Frigid', 'Freezing'], 'water_resistance' => 'proof', 'insulation' => 'heavy', 'thermal_value' => 4,
        'wind_resistance' => 'proof', 'breathability' => 'low', 'special_conditions' => ['snow', 'ice', 'severe'],
        'img' => './img/shoes_boots_insulated.jpg', 'img_fallback' => './img/boots.jpg'
    ],
     'boots-rain' => [
        'name' => 'Rain Boots', 'type' => 'boots', 'layer' => 'single', 'category' => ['Casual', 'Tourism', 'Professional'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp', 'Cold'], 'water_resistance' => 'proof', 'insulation' => 'none', 'thermal_value' => 1,
        'wind_resistance' => 'medium', 'breathability' => 'low', 'special_conditions' => ['rain', 'drizzle'],
        'img' => './img/shoes_boots_rain.jpg', 'img_fallback' => './img/boots.jpg'
    ],

    // --- Accessories (Single Layer) ---
    'hat-sun' => [
        'name' => 'Cap', 'type' => 'hat', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Hiking', 'Tourism'],
        'temp_bands' => ['Dangerous', 'Hot', 'Warm', 'Mild', 'Crisp'], 'sun_protection' => true, 'water_resistance' => 'none', 'thermal_value' => 0,
        'img' => './img/hat_sun.jpg', 'img_fallback' => './img/hat.jpg'
    ],
    'hat-casual' => [
        'name' => 'Beanie', 'type' => 'hat', 'layer' => 'single', 'category' => ['Casual', 'Hiking', 'Tourism'],
        'temp_bands' => ['Hot', 'Warm', 'Mild', 'Crisp'], 'sun_protection' => true, 'water_resistance' => 'none', 'insulation' => 'none', 'thermal_value' => 0,
        'img' => './img/hat_light.jpg', 'img_fallback' => './img/hat.jpg'
    ],
    'hat-warm' => [
        'name' => 'Warm Hat', 'type' => 'hat', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Hiking', 'Tourism'],
        'temp_bands' => ['Mild', 'Crisp', 'Cold', 'Frigid', 'Freezing'], 'sun_protection' => false, 'water_resistance' => 'light', 'insulation' => 'medium', 'wind_resistance' => 'medium', 'thermal_value' => 2,
        'img' => './img/hat_warm.jpg', 'img_fallback' => './img/hat.jpg'
    ],
    'scarf-light' => [
        'name' => 'Light Scarf', 'type' => 'scarf', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Hiking', 'Tourism'],
        'temp_bands' => ['Warm', 'Mild', 'Crisp'], 'insulation' => 'light', 'wind_resistance' => 'light', 'thermal_value' => 1,
        'img' => './img/scarf_light.jpg', 'img_fallback' => './img/accessory.jpg'
    ],
    'scarf-warm' => [
        'name' => 'Warm Scarf', 'type' => 'scarf', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Tourism', 'Hiking'],
        'temp_bands' => ['Mild', 'Crisp', 'Cold', 'Frigid', 'Freezing'], 'insulation' => 'medium', 'wind_resistance' => 'medium', 'thermal_value' => 2,
        'img' => './img/scarf_warm.jpg', 'img_fallback' => './img/accessory.jpg'
    ],
    'gloves-light' => [
        'name' => 'Light Gloves', 'type' => 'gloves', 'layer' => 'single', 'category' => ['Casual', 'Hiking', 'Tourism', 'Professional'],
        'temp_bands' => ['Mild', 'Crisp', 'Cold'], 'insulation' => 'light', 'wind_resistance' => 'light', 'thermal_value' => 1,
        'img' => './img/gloves_light.jpg', 'img_fallback' => './img/gloves.jpg'
    ],
    'gloves-heavy' => [
        'name' => 'Insulated Gloves/Mittens', 'type' => 'gloves', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Hiking', 'Tourism'],
        'temp_bands' => ['Cold', 'Frigid', 'Freezing'], 'insulation' => 'heavy', 'wind_resistance' => 'proof', 'water_resistance' => 'resistant', 'special_conditions' => ['snow'], 'thermal_value' => 3,
        'img' => './img/gloves_heavy.jpg', 'img_fallback' => './img/gloves.jpg'
    ],
     'umbrella' => [
        'name' => 'Umbrella', 'type' => 'umbrella', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Tourism'],
        'temp_bands' => ['Dangerous', 'Hot', 'Warm', 'Mild', 'Crisp', 'Cold'], 'water_resistance' => 'proof', 'wind_resistance' => 'none', 'thermal_value' => 0,
        'special_conditions' => ['rain', 'drizzle'],
        'img' => './img/umbrella.jpg', 'img_fallback' => './img/accessory.jpg'
    ],
     'sunglasses' => [
        'name' => 'Sunglasses', 'type' => 'sunglasses', 'layer' => 'single', 'category' => ['Casual', 'Professional', 'Hiking', 'Tourism'],
        'temp_bands' => ['Dangerous', 'Hot', 'Warm', 'Mild', 'Crisp', 'Cold', 'Frigid', 'Freezing'],
        'sun_protection' => true, 'special_conditions' => ['sunny'], 'thermal_value' => 0,
        'img' => './img/sunglasses.jpg', 'img_fallback' => './img/accessory.jpg'
    ],

    // Placeholder item definition
    'placeholder' => [
        'name' => 'Placeholder', 'type' => 'accessory', 'layer' => 'single', 'category' => [], 'temp_bands' => [],
        'water_resistance' => 'none', 'insulation' => 'none', 'wind_resistance' => 'none', 'breathability' => 'medium', 'sun_protection' => false, 'thermal_value' => 0,
        'img' => './img/placeholder.png', 'img_fallback' => './img/placeholder.png'
    ],
]);

?>
