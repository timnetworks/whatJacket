<?php

/**
 * Configuration for whatJacket Application
 *
 * All user-modifiable settings should be placed here.
 */

// --- API Settings ---

// User agent required by NOAA API Terms of Service.
// Format: AppName/Version (ContactInfo; WebsiteOptional)
define('API_USER_AGENT', 'whatJacketAPPv10 (abuse@timnetworks.com)');

// Base URL for the NOAA API
define('NOAA_API_BASE_URL', 'https://api.weather.gov');

// Base URL for OpenStreetMap Nominatim API (for ZIP code geocoding)
// Requires attribution if used. See: https://operations.osmfoundation.org/policies/nominatim/
define('GEOCODING_API_BASE_URL', 'https://nominatim.openstreetmap.org/search');

// --- Application Settings ---

// Path to the header logo image relative to the web root.
// Make sure it starts with './' or '/' as appropriate for your setup.
define('LOGO_IMAGE_PATH', './img/wj-logo.png');

// Available clothing categories
define('CATEGORIES', [
    'Casual',
    'Professional',
    'Hiking',
    'Tourism'
]);

// Temperature Scale Mapping (0-10 scale represents 0°F to 100°F)
define('TEMP_SCALE_MIN_F', 0);
define('TEMP_SCALE_MAX_F', 100);

// Maximum wind speed (mph) to suggest an umbrella
define('UMBRELLA_MAX_WIND_MPH', 20);

// Wind speed (mph) threshold to consider it "Windy" for background/summary
define('WINDY_THRESHOLD_MPH', 15);

// --- Standardized Footer Links ---
define('FOOTER_LINKS', [
    ['text' => 'NOAA', 'url' => 'https://www.weather.gov/documentation/services-web-api'],
    ['text' => 'Nominatim', 'url' => 'https://www.nominatim.com'],
    ['text' => 'OpenStreetMap', 'url' => 'https://www.openstreetmap.org/copyright'],
    ['text' => 'timnetworks', 'url' => 'https://timnetworks.com/']
]);

// --- Forecast Background Images ---
// Maps simplified condition keys (from get_simple_condition_key) to image paths.
// Paths should be relative to the script location (e.g., start with './img/backgrounds/')
// or absolute web paths (start with '/'). Ensure these images exist!
define('FORECAST_BACKGROUNDS', [
    'Severe'   => './img/backgrounds/severe.jpg',      // Tornado, Hurricane, Severe T-Storm
    'Snowy'    => './img/backgrounds/snowy.jpg',       // Snow, Sleet, Blizzard, Ice
    'Rainy'    => './img/backgrounds/rainy.jpg',       // Rain, Showers, Drizzle, T-Storm
    'Windy'    => './img/backgrounds/windy.jpg',       // If wind speed >= WINDY_THRESHOLD_MPH and not rainy/snowy
    'Cloudy'   => './img/backgrounds/cloudy.jpg',      // Cloudy, Overcast, Fog, Partly/Mostly Cloudy
    'Sunny'    => './img/backgrounds/sunny.jpg',       // Sunny, Clear
    'Variable' => './img/backgrounds/variable.jpg',    // Default/fallback
]);

// --- Simplified Condition Display Names ---
// Optional: Map keys from get_simple_condition_key to nicer display names
define('SIMPLE_CONDITION_DISPLAY', [
    'Severe'   => 'Severe Weather',
    'Snowy'    => 'Snowy',
    'Rainy'    => 'Rainy',
    'Windy'    => 'Windy', // Now triggered at 15mph+
    'Cloudy'   => 'Cloudy',
    'Sunny'    => 'Sunny',
    'Variable' => 'Variable Conditions',
]);

// --- Mapping from Item Type to Display Group ---
// Maps the 'type' defined in CLOTHING_ITEMS to one of the display groups.
// Ensure ALL types used in CLOTHING_ITEMS are listed here.
define('TYPE_TO_DISPLAY_GROUP_MAP', [
    'shirt'     => 'Tops',
    'sweater'   => 'Tops',
    'jacket'    => 'Tops',
    'coat'      => 'Tops',
    'pants'     => 'Bottoms',
    'shorts'    => 'Bottoms',
    'socks'     => 'Bottoms',
    'shoes'     => 'Bottoms',
    'hat'       => 'Accessories',
    'gloves'    => 'Accessories',
    'scarf'     => 'Accessories',
    'umbrella'  => 'Accessories',
    'sunglasses'=> 'Accessories',
    'accessory' => 'Accessories', // Catch-all for generic accessories
]);


// --- Clothing Item Definitions ---
/*
 * Structure: (See previous version for full details)
 * ... keys ...
 *  'type'          => (string) MUST exist as a key in TYPE_TO_DISPLAY_GROUP_MAP above.
 *
 * Temperature Scale Reminder: 0=0F, 1=10F, 2=20F, 3=30F, 4=40F, 5=50F, 6=60F, 7=70F, 8=80F, 9=90F, 10=100F
 * precip_threshold: Minimum percentage chance of precipitation to consider item.
 * wind_threshold: Minimum wind speed (mph) to consider item (often for windproof items).
 */
define('CLOTHING_ITEMS', [
    // --- Shirts (Type: 'shirt' maps to 'Tops') ---
    'shirt-casual-light' => [ // E.g., T-shirt
        'name' => 'Light Casual Shirt', 'type' => 'shirt', 'category' => ['Casual','Tourism'], 'thermal' => 'light',
        'temp_min' => 6, 'temp_max' => 10, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/shirt_casual_light.jpg', 'img_fallback' => './img/shirt.jpg' // NEEDS IMAGE
    ],
    'shirt-casual-medium' => [ // E.g., Long-sleeve T-shirt, flannel
        'name' => 'Medium Casual Shirt', 'type' => 'shirt', 'category' => ['Casual','Tourism'], 'thermal' => 'medium',
        'temp_min' => 4, 'temp_max' => 7, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/shirt_casual_medium.jpg', 'img_fallback' => './img/shirt.jpg' // NEEDS IMAGE
    ],
     'shirt-professional-light' => [ // E.g., Dress shirt
        'name' => 'Light Pro Shirt', 'type' => 'shirt', 'category' => 'Professional', 'thermal' => 'light',
        'temp_min' => 6, 'temp_max' => 9, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/shirt_professional_light.jpg', 'img_fallback' => './img/shirt.jpg' // NEEDS IMAGE
    ],
    'shirt-professional-medium' => [ // E.g., Heavier dress shirt
        'name' => 'Medium Pro Shirt', 'type' => 'shirt', 'category' => 'Professional', 'thermal' => 'medium',
        'temp_min' => 4, 'temp_max' => 7, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/shirt_professional_medium.jpg', 'img_fallback' => './img/shirt.jpg' // NEEDS IMAGE
    ],
     'shirt-hiking-light' => [ // E.g., Synthetic short-sleeve
        'name' => 'Light Hiking Shirt', 'type' => 'shirt', 'category' => 'Hiking', 'thermal' => 'light',
        'temp_min' => 5, 'temp_max' => 10, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => true, // Often UV protective
        'img' => './img/shirt_hiking_light.jpg', 'img_fallback' => './img/shirt.jpg' // NEEDS IMAGE
    ],
    'shirt-hiking-medium' => [ // E.g., Synthetic long-sleeve base layer
        'name' => 'Medium Hiking Shirt', 'type' => 'shirt', 'category' => 'Hiking', 'thermal' => 'medium',
        'temp_min' => 3, 'temp_max' => 6, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/shirt_hiking_medium.jpg', 'img_fallback' => './img/shirt.jpg' // NEEDS IMAGE
    ],

    // --- Sweaters ---
    'sweater-casual-light' => [ // E.g., Light V-neck, thin hoodie
        'name' => 'Light Sweater/ Hoodie', 'type' => 'sweater', 'category' => ['Casual', 'Tourism'], 'thermal' => 'light',
        'temp_min' => 6, 'temp_max' => 8, 'precip_threshold' => 0, 'wind_threshold' => 5, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/sweater_casual_light.jpg', 'img_fallback' => './img/sweater.jpg' // NEEDS IMAGE and fallback
    ],
    'sweater-casual-medium' => [ // E.g., Fleece, thicker hoodie, wool sweater
        'name' => 'Medium Sweater/ Fleece', 'type' => 'sweater', 'category' => ['Casual', 'Tourism', 'Hiking'], 'thermal' => 'medium',
        'temp_min' => 4, 'temp_max' => 7, 'precip_threshold' => 0, 'wind_threshold' => 10, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, // Fleece is somewhat wind resistant
        'sunshielding' => false,
        'img' => './img/sweater_casual_medium.jpg', 'img_fallback' => './img/sweater.jpg' // NEEDS IMAGE
    ],
    'sweater-professional-medium' => [ // E.g., Dressier V-neck or crew neck
        'name' => 'Medium Pro Sweater', 'type' => 'sweater', 'category' => 'Professional', 'thermal' => 'medium',
        'temp_min' => 4, 'temp_max' => 7, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/sweater_professional_medium.jpg', 'img_fallback' => './img/sweater.jpg' // NEEDS IMAGE
    ],

    // --- Pants & Shorts ---
    'shorts-casual' => [ // RENAMED from pants-casual-light, NEW type 'shorts'
        'name' => 'Shorts', 'type' => 'shorts', 'category' => ['Casual', 'Tourism', 'Hiking'], 'thermal' => 'light',
        'temp_min' => 7, 'temp_max' => 10, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => ['sunny', 'clear', 'mostly sunny', 'partly cloudy'], // Good for sun
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/shorts_casual.jpg', 'img_fallback' => './img/pants.jpg' // NEEDS IMAGE
    ],
    'jeans-casual' => [ // RENAMED from pants-casual-medium
        'name' => 'Jeans', 'type' => 'pants', 'category' => ['Casual', 'Tourism'], 'thermal' => 'medium',
        'temp_min' => 3, 'temp_max' => 8, 'precip_threshold' => 10, 'wind_threshold' => 10, // Ok in light wind/drizzle, bad if soaked
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/jeans_casual.jpg', 'img_fallback' => './img/pants.jpg' // NEEDS IMAGE
    ],
    'chinos-casual-professional' => [ // NEW
        'name' => 'Chinos/ Khakis', 'type' => 'pants', 'category' => ['Casual', 'Professional', 'Tourism'], 'thermal' => 'medium',
        'temp_min' => 4, 'temp_max' => 8, 'precip_threshold' => 15, 'wind_threshold' => 5, // Dry faster than jeans
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/chinos_casual_professional.jpg', 'img_fallback' => './img/pants.jpg' // NEEDS IMAGE
    ],
    'slacks-professional' => [ // RENAMED from pants-professional-medium
        'name' => 'Slacks/ Dress Pants', 'type' => 'pants', 'category' => 'Professional', 'thermal' => 'medium',
        'temp_min' => 4, 'temp_max' => 8, 'precip_threshold' => 10, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/slacks_professional.jpg', 'img_fallback' => './img/pants.jpg' // NEEDS IMAGE
    ],
     'pants-hiking-light' => [
        'name' => 'Hiking Pants', 'type' => 'pants', 'category' => 'Hiking', 'thermal' => 'light',
        'temp_min' => 7, 'temp_max' => 10, 'precip_threshold' => 25, 'wind_threshold' => 15, // Often quick-dry
        'waterproof' => false, 'windproof' => true, 'sunshielding' => true,
        'img' => './img/pants_hiking_light.jpg', 'img_fallback' => './img/pants.jpg' // NEEDS IMAGE
    ],
     'pants-hiking-medium' => [
        'name' => 'Hiking Pants', 'type' => 'pants', 'category' => 'Hiking', 'thermal' => 'medium',
        'temp_min' => 3, 'temp_max' => 6, 'precip_threshold' => 25, 'wind_threshold' => 15, // Often quick-dry, wind resistant
        'waterproof' => false, 'windproof' => true, 'sunshielding' => true,
        'img' => './img/pants_hiking_medium.jpg', 'img_fallback' => './img/pants.jpg' // NEEDS IMAGE
    ],
    'pants-hiking-heavy' => [
        'name' => 'Heavy Hiking Pants', 'type' => 'pants', 'category' => 'Hiking', 'thermal' => 'heavy',
        'temp_min' => 0, 'temp_max' => 4, 'precip_threshold' => 30, 'wind_threshold' => 20,
        'waterproof' => false, 'windproof' => true, 'sunshielding' => false, // Maybe waterproof if softshell
        'img' => './img/pants_hiking_heavy.jpg', 'img_fallback' => './img/pants.jpg' // NEEDS IMAGE
    ],
    'pants-insulated' => [ // NEW for cold weather
        'name' => 'Insulated Pants', 'type' => 'pants', 'category' => ['Casual', 'Hiking', 'Tourism'], 'thermal' => 'heavy',
        'temp_min' => 0, 'temp_max' => 4, 'precip_threshold' => 20, 'wind_threshold' => 15, 'conditions' => ['snow', 'sleet', 'cold'], // Check logic for 'cold' condition
        'waterproof' => false, 'windproof' => true, 'sunshielding' => false,
        'img' => './img/pants_insulated.jpg', 'img_fallback' => './img/pants.jpg' // NEEDS IMAGE
    ],

    // --- Jackets / Coats ---
    'windbreaker-casual-light' => [ // NEW
        'name' => 'Windbreaker', 'type' => 'jacket', 'category' => ['Casual', 'Tourism', 'Hiking'], 'thermal' => 'light',
        'temp_min' => 6, 'temp_max' => 8, 'precip_threshold' => 10, 'wind_threshold' => 10, // Specific for wind
        'waterproof' => false, 'windproof' => true, 'sunshielding' => false,
        'img' => './img/windbreaker_casual_light.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],
    'jacket-casual-light' => [ // E.g., Denim jacket, light bomber - less windproof than windbreaker
        'name' => 'Light Casual Jacket', 'type' => 'jacket', 'category' => ['Casual', 'Tourism'], 'thermal' => 'light',
        'temp_min' => 6, 'temp_max' => 8, 'precip_threshold' => 10, 'wind_threshold' => 5, // Wider temp range
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/jacket_casual_light.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],
     'raincoat-general' => [ // NEW - General purpose raincoat
        'name' => 'Raincoat', 'type' => 'jacket', 'category' => ['Casual', 'Professional', 'Tourism', 'Hiking'], 'thermal' => 'light', // Thermal is light as it's often a shell
        'temp_min' => 4, 'temp_max' => 9, // Wide range, worn over other clothes
        'precip_threshold' => 35, // Lower threshold to trigger more readily
        'wind_threshold' => 0, // Rain is the trigger, windproof helps
        'conditions' => ['rain', 'showers', 'drizzle', 'thunderstorms'],
        'waterproof' => true, 'windproof' => true, 'sunshielding' => false,
        'img' => './img/raincoat_general.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],
    'jacket-casual-medium' => [ // E.g., Heavier bomber, lined jacket
        'name' => 'Medium Casual Jacket', 'type' => 'jacket', 'category' => ['Casual', 'Tourism'], 'thermal' => 'medium',
        'temp_min' => 4, 'temp_max' => 7, 'precip_threshold' => 15, 'wind_threshold' => 15, // Adjusted temp slightly
        'waterproof' => false, 'windproof' => true, // Often wind resistant
        'sunshielding' => false,
        'img' => './img/jacket_casual_medium.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],
     'coat-casual-heavy' => [ // RENAMED from jacket-casual-heavy (using 'jacket' type still) E.g., Wool coat, Duffel coat
        'name' => 'Heavy Casual Coat', 'type' => 'jacket', 'category' => ['Casual', 'Tourism'], 'thermal' => 'heavy',
        'temp_min' => 1, 'temp_max' => 5, // Slightly adjusted range
        'precip_threshold' => 20, 'wind_threshold' => 20,
        'waterproof' => false, 'windproof' => true, 'sunshielding' => false,
        'img' => './img/coat_casual_heavy.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],
     'jacket-professional-medium' => [ // E.g., Overcoat/Trench coat
        'name' => 'Overcoat/ Trenchcoat', 'type' => 'jacket', 'category' => 'Professional', 'thermal' => 'medium',
        'temp_min' => 3, 'temp_max' => 6, // Adjusted range
        'precip_threshold' => 20, // More likely worn in potential rain
        'wind_threshold' => 10,
        'conditions' => ['rain', 'drizzle', 'showers'], // Relevant for trench
        'waterproof' => true, 'windproof' => true, 'sunshielding' => false, // Trench coats are usually water/wind resistant
        'img' => './img/jacket_professional_medium.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],
    'parka-casual-heavy' => [ // NEW - Very Warm Coat, using 'coat' type
        'name' => 'Parka', 'type' => 'coat', 'category' => ['Casual', 'Tourism'], 'thermal' => 'heavy',
        'temp_min' => 0, 'temp_max' => 3, // For very cold temps (0F-30F)
        'precip_threshold' => 30, 'wind_threshold' => 15, 'conditions' => ['snow', 'sleet', 'cold', 'blizzard'],
        'waterproof' => true, 'windproof' => true, 'sunshielding' => false,
        'img' => './img/parka_casual_heavy.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],
     'jacket-hiking-light-rain' => [ // Existing, adjusted precip
        'name' => 'Light Hiking Rain Jacket', 'type' => 'jacket', 'category' => 'Hiking', 'thermal' => 'light',
        'temp_min' => 5, 'temp_max' => 9,
        'precip_threshold' => 25, // Lowered threshold
        'wind_threshold' => 15, 'conditions' => ['rain', 'showers', 'drizzle'],
        'waterproof' => true, 'windproof' => true, 'sunshielding' => false,
        'img' => './img/jacket_hiking_light_rain.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],
     'jacket-hiking-heavy' => [ // Existing, adjusted precip
        'name' => 'Heavy Hiking Jacket', 'type' => 'jacket', 'category' => 'Hiking', 'thermal' => 'heavy',
        'temp_min' => 0, 'temp_max' => 4,
        'precip_threshold' => 40, // Lowered threshold slightly
        'wind_threshold' => 25, 'conditions' => ['rain', 'showers', 'snow', 'sleet', 'blizzard'],
        'waterproof' => true, 'windproof' => true, 'sunshielding' => false,
        'img' => './img/jacket_hiking_heavy.jpg', 'img_fallback' => './img/jacket.jpg' // NEEDS IMAGE
    ],

    // --- Accessories ---
    'hat-sun' => [
        'name' => 'Sun Hat', 'type' => 'hat', 'category' => ['Casual', 'Hiking', 'Tourism'], 'thermal' => 'light',
        'temp_min' => 7, 'temp_max' => 10, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => ['sunny', 'clear', 'mostly sunny'],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => true,
        'img' => './img/hat_sun.jpg', 'img_fallback' => './img/hat.jpg' // NEEDS IMAGE and fallback
    ],
    'hat-warm' => [ // E.g., Beanie
        'name' => 'Warm Hat/ Beanie', 'type' => 'hat', 'category' => ['Casual', 'Hiking', 'Tourism'], 'thermal' => 'medium',
        'temp_min' => 0, 'temp_max' => 5, // Adjusted max temp slightly higher
        'precip_threshold' => 0, 'wind_threshold' => 10, 'conditions' => ['cold'],
        'waterproof' => false, 'windproof' => false, // Wool/fleece can be wind resistant
        'sunshielding' => false,
        'img' => './img/hat_warm.jpg', 'img_fallback' => './img/hat.jpg' // NEEDS IMAGE
    ],
     'scarf-warm' => [ // NEW
        'name' => 'Scarf', 'type' => 'scarf', 'category' => ['Casual', 'Professional', 'Tourism', 'Hiking'], 'thermal' => 'medium',
        'temp_min' => 0, 'temp_max' => 5, // Similar range to warm hat/light gloves
        'precip_threshold' => 0, 'wind_threshold' => 10, 'conditions' => ['cold', 'windy'], // Good for wind chill
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/scarf_warm.jpg', 'img_fallback' => './img/accessory.jpg' // NEEDS IMAGE and fallback
    ],
    'gloves-light' => [
        'name' => 'Light Gloves', 'type' => 'gloves', 'category' => ['Casual', 'Hiking', 'Tourism', 'Professional'], 'thermal' => 'light',
        'temp_min' => 3, 'temp_max' => 5, 'precip_threshold' => 0, 'wind_threshold' => 10, 'conditions' => ['cold'],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/gloves_light.jpg', 'img_fallback' => './img/gloves.jpg' // NEEDS IMAGE and fallback
    ],
    'gloves-heavy' => [
        'name' => 'Heavy Gloves/ Mittens', 'type' => 'gloves', 'category' => ['Casual', 'Hiking', 'Tourism'], 'thermal' => 'heavy',
        'temp_min' => 0, 'temp_max' => 3, 'precip_threshold' => 20, 'wind_threshold' => 15, 'conditions' => ['snow', 'sleet', 'cold', 'blizzard'],
        'waterproof' => false, 'windproof' => true, // Often are wind/water resistant
        'sunshielding' => false,
        'img' => './img/gloves_heavy.jpg', 'img_fallback' => './img/gloves.jpg' // NEEDS IMAGE
    ],
     'umbrella' => [
        'name' => 'Umbrella', 'type' => 'accessory', 'category' => ['Casual', 'Professional', 'Tourism'], 'thermal' => 'all',
        'temp_min' => 0, 'temp_max' => 10,
        'precip_threshold' => 30, // Slightly higher threshold than raincoats now
        'wind_threshold' => 0, // Wind check done separately in suggest_clothing (using UMBRELLA_MAX_WIND_MPH)
        'conditions' => ['rain', 'showers', 'drizzle'],
        'waterproof' => true, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/umbrella.jpg', 'img_fallback' => './img/accessory.jpg' // NEEDS IMAGE
    ],
     'sunglasses' => [ 
        'name' => 'Sunglasses', 'type' => 'sunglasses', 'category' => ['Casual', 'Professional', 'Hiking', 'Tourism'], 'thermal' => 'all',
        'temp_min' => 0, 'temp_max' => 10, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => ['sunny', 'clear', 'mostly sunny'],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => true,
        'img' => './img/sunglasses.jpg', 'img_fallback' => './img/accessory.jpg' // NEEDS IMAGE and fallback
    ],

    // --- Socks & Shoes ---
    'socks-medium' => [
        'name' => 'Standard Socks', 'type' => 'socks', 'category' => ['Casual', 'Professional', 'Tourism', 'Hiking'], 'thermal' => 'medium',
        'temp_min' => 0, 'temp_max' => 10, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [], // Always needed except maybe with sandals? (No sandals defined yet)
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/socks_medium.jpg', 'img_fallback' => './img/socks.jpg' // NEEDS IMAGE and fallback
    ],
     'socks-hiking-heavy' => [
        'name' => 'Hiking/ Wool Socks', 'type' => 'socks', 'category' => 'Hiking', 'thermal' => 'heavy',
        'temp_min' => 0, 'temp_max' => 6, // Good for cooler temps or long hikes
        'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => ['cold'],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/socks_hiking_heavy.jpg', 'img_fallback' => './img/socks.jpg' // NEEDS IMAGE
    ],
     'shoes-casual' => [
        'name' => 'Casual Shoes/Sneakers', 'type' => 'shoes', 'category' => ['Casual', 'Tourism'], 'thermal' => 'medium',
        'temp_min' => 3, 'temp_max' => 10, 'precip_threshold' => 20, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/shoes_casual.jpg', 'img_fallback' => './img/shoes.jpg' // NEEDS IMAGE and fallback
    ],
    'shoes-professional' => [
        'name' => 'Leather/ Dress Shoes', 'type' => 'shoes', 'category' => 'Professional', 'thermal' => 'medium',
        'temp_min' => 3, 'temp_max' => 9, 'precip_threshold' => 30, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, // Maybe slightly water resistant if treated
        'windproof' => false, 'sunshielding' => false,
        'img' => './img/shoes_professional.jpg', 'img_fallback' => './img/shoes.jpg' // NEEDS IMAGE
    ],
     'shoes-hiking' => [
        'name' => 'Hiking Boots/ Shoes', 'type' => 'shoes', 'category' => 'Hiking', 'thermal' => 'medium', // Can be heavy too
        'temp_min' => 1, 'temp_max' => 8, // Wider range, includes colder temps
        'precip_threshold' => 30, // Often waterproof/resistant
        'wind_threshold' => 10, 'conditions' => ['rain', 'snow', 'sleet'],
        'waterproof' => false, // Varies, many are WP
        'windproof' => false, 'sunshielding' => false,
        'img' => './img/shoes_hiking.jpg', 'img_fallback' => './img/shoes.jpg' // NEEDS IMAGE
    ],
     'shoes-boots-insulated' => [
        'name' => 'Insulated Boots', 'type' => 'shoes', 'category' => ['Casual', 'Hiking', 'Tourism'], 'thermal' => 'heavy',
        'temp_min' => 0, 'temp_max' => 4, // Cold weather
        'precip_threshold' => 40, 'wind_threshold' => 0, 'conditions' => ['snow', 'sleet', 'ice', 'cold', 'blizzard'],
        'waterproof' => true, 'windproof' => true, 'sunshielding' => false,
        'img' => './img/shoes_boots_insulated.jpg', 'img_fallback' => './img/shoes.jpg' // NEEDS IMAGE
    ],

    // Placeholder item definition - useful for get_image_path fallback logic
    'placeholder' => [
        'name' => 'Placeholder', 'type' => 'accessory', 'category' => [], 'thermal' => 'all',
        'temp_min' => 0, 'temp_max' => 10, 'precip_threshold' => 0, 'wind_threshold' => 0, 'conditions' => [],
        'waterproof' => false, 'windproof' => false, 'sunshielding' => false,
        'img' => './img/placeholder.png', 'img_fallback' => './img/placeholder.png' // NEEDS IMAGE
    ],
]);

?>
