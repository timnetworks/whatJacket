<?php // FILE: index.php
/**
 * whatJacket Application v0.8.2 - Main User Interface
 */

// Start session to store preferences (like temp unit)
session_start();

// Include configuration and functions
require_once 'config.php';
require_once 'functions.php';

// --- Variable Initialization ---
$app_title = APP_TITLE ?? 'whatJacket';
$app_version = APP_VERSION ?? '0.8.2';
$app_name_short = APP_NAME_SHORT ?? 'whatJacket';
$theme_color = THEME_COLOR ?? '#007bff';
$page_url = rtrim(APP_URL, '/') . $_SERVER['REQUEST_URI'];
$logo_path = LOGO_IMAGE_PATH ?? './img/wj-logo.png';
$og_image_url = rtrim(APP_URL, '/') . '/' . ltrim(OG_IMAGE_PATH ?? './img/wj-logo-og.png', './');
$favicon_url = './favicon.ico';
$apple_icon_url = './img/apple-touch-icon.png';
// $svg_icon_url = './favicon.svg'; // Assumed SVG icon path

$zip_code = '';
$selected_category = $_SESSION['last_category'] ?? DEFAULT_CATEGORY;
$selected_temp_unit = $_SESSION['last_temp_unit'] ?? DEFAULT_TEMP_UNIT; // Get from session or default

$location_info = null;
$forecast_data = null;
$clothing_recommendation = null;
$error_message = null;
$info_message = null;


// --- Handle Form Submission ---
$form_source = ''; // To track which form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Determine Form Source and Extract Data ---
    // Default to empty values before checking POST data
    $post_zip = '';
    $post_temp_unit_pref = DEFAULT_TEMP_UNIT;
    $post_category = DEFAULT_CATEGORY;


    if (isset($_POST['submit_main_form'])) {
        $form_source = 'main';
        $post_zip = isset($_POST['zip_code']) ? trim(filter_var($_POST['zip_code'], FILTER_SANITIZE_NUMBER_INT)) : '';
        $post_temp_unit_pref = isset($_POST['temp_unit_hidden']) ? strtoupper(trim($_POST['temp_unit_hidden'])) : DEFAULT_TEMP_UNIT;
        $post_category = isset($_POST['activity_category']) && array_key_exists($_POST['activity_category'], CATEGORIES) ? $_POST['activity_category'] : DEFAULT_CATEGORY;

    } elseif (isset($_POST['submit_results_form'])) {
        $form_source = 'results';
        // Use hidden fields from the results form
        $post_zip = isset($_POST['results_zip_code']) ? trim(filter_var($_POST['results_zip_code'], FILTER_SANITIZE_NUMBER_INT)) : '';
        $post_temp_unit_pref = isset($_POST['results_temp_unit']) ? strtoupper(trim($_POST['results_temp_unit'])) : DEFAULT_TEMP_UNIT;
        $post_category = isset($_POST['results_activity_category']) && array_key_exists($_POST['results_activity_category'], CATEGORIES) ? $_POST['results_activity_category'] : DEFAULT_CATEGORY;

    } else {
         // Fallback: Assume it's the main form if zip_code is posted (e.g., user hit Enter)
         if (isset($_POST['zip_code'])) {
             $form_source = 'main_fallback'; // Indicate potential fallback
             $post_zip = trim(filter_var($_POST['zip_code'], FILTER_SANITIZE_NUMBER_INT));
             // Still try to get other fields if they exist
             $post_temp_unit_pref = isset($_POST['temp_unit_hidden']) ? strtoupper(trim($_POST['temp_unit_hidden'])) : DEFAULT_TEMP_UNIT;
             $post_category = isset($_POST['activity_category']) && array_key_exists($_POST['activity_category'], CATEGORIES) ? $_POST['activity_category'] : DEFAULT_CATEGORY;
         } else {
             // Truly unknown POST - log this? Use session data as a last resort?
             // For now, stick with session data if POST source is unclear.
             $post_zip = $_SESSION['last_zip'] ?? '';
             $post_category = $_SESSION['last_category'] ?? DEFAULT_CATEGORY;
             $post_temp_unit_pref = $_SESSION['last_temp_unit'] ?? DEFAULT_TEMP_UNIT;
         }
    }

    // --- Update working variables ---
    $zip_code = $post_zip; // Use the determined ZIP code
    $selected_category = $post_category;
    $selected_temp_unit = ($post_temp_unit_pref === 'C') ? 'C' : 'F'; // Validate temp unit

    // --- Store preferences in session --- (Do this *after* determining values for the current request)
    // Only store a non-empty zip code to prevent overwriting a valid session zip with an empty one from a bad POST
    if (!empty($zip_code)) {
        $_SESSION['last_zip'] = $zip_code;
    }
    $_SESSION['last_category'] = $selected_category;
    $_SESSION['last_temp_unit'] = $selected_temp_unit;


    // --- Input Validation --- (Now uses the correctly assigned $zip_code)
    if (empty($zip_code)) {
        $error_message = "Missing ZIP code information.";
    } elseif (!preg_match('/^\d{5}$/', $zip_code)) {
        $error_message = "Invalid US ZIP code format provided."; // Changed message slightly
    } else {
        // --- Process Request ---
        $geocode_result = geocode_zip($zip_code);
        if (!$geocode_result['success']) {
            $error_message = $geocode_result['error'];
        } else {
            $location_info = $geocode_result;
            $grid_result = get_noaa_grid_url($location_info['lat'], $location_info['lon']);
            if (!$grid_result['success']) {
                $error_message = $grid_result['error'];
            } else {
                $forecast_result = get_noaa_forecast($grid_result['grid_url']);
                if (!$forecast_result['success']) {
                    $error_message = $forecast_result['error'];
                } else {
                    $processed_forecast = process_forecast($forecast_result['periods'], $selected_temp_unit);
                    if ($processed_forecast === null) {
                        $error_message = "Could not process the forecast data.";
                    } else {
                        $forecast_data = $processed_forecast;
                        $clothing_recommendation = select_clothing($forecast_data, $selected_category);
                        if (empty($clothing_recommendation['selected_items']) && $error_message === null) {
                            $info_message = "Could not determine specific clothing items for this activity/condition combination.";
                        }
                        // Success
                    }
                }
            }
        }
    } // End validation check
} else {
    // --- Handle GET Request (Page Load) ---
    // Variables were already initialized using session data at the top
}

// Determine if results should be shown
$show_results = ($forecast_data && !$error_message); // Show results section if we have forecast data and no error prevented it
$body_class = $show_results ? 'results-shown' : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Weather Based Clothing Suggestions</title>

    <!-- SEO -->
    <meta name="description" content="Get personalized, layered clothing suggestions based on the weather forecast for your US ZIP code. Select activity, get recommendations!">
    <meta name="keywords" content="weather, clothing, suggestions, forecast, jacket, outfit, NOAA, what to wear, layers, fahrenheit, celsius, layering, apparel, fashion, style, weather app">

    <!-- Open Graph / Facebook -->
    <meta property="og:title" content="<?php echo htmlspecialchars($app_title); ?> - Weather Clothing Suggestions">
    <meta property="og:description" content="Get layered clothing suggestions based on the weather forecast.">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image_url); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($page_url); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($app_title); ?>">
    <meta property="og:locale" content="en_US">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($app_title); ?> - Weather Clothing Suggestions">
    <meta name="twitter:description" content="Get layered clothing suggestions based on the weather forecast.">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image_url); ?>">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($page_url); ?>">
    <!-- <meta name="twitter:site" content="@YourTwitterHandle"> -->
    <!-- <meta name="twitter:creator" content="@YourTwitterHandle"> -->

    <!-- Icons -->
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_url); ?>" sizes="any">
<!--    <link rel="icon" href="<?php echo htmlspecialchars($svg_icon_url); ?>" type="image/svg+xml"> -->
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($apple_icon_url); ?>"> <!-- 180x180 recommended -->
    <link rel="manifest" href="./manifest.json"> <!-- Relative path to manifest -->

    <!-- PWA / Mobile -->
    <meta name="theme-color" content="<?php echo htmlspecialchars($theme_color); ?>">
    <meta name="color-scheme" content="light dark"> <!-- Indicate support for light/dark modes -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($app_name_short); ?>">
    <meta name="application-name" content="<?php echo htmlspecialchars($app_name_short); ?>">

    <!-- MS Tiles -->
    <meta name="msapplication-TileColor" content="<?php echo htmlspecialchars($theme_color); ?>">
    <meta name="msapplication-TileImage" content="<?php echo htmlspecialchars($apple_icon_url); ?>"> <!-- Ideally points to a 144x144 tile -->

    <!-- Font Awesome (Optional - include if using FA icons in config) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="style.css?v=<?php echo htmlspecialchars($app_version); ?>">

</head>
<body class="<?php echo $body_class; ?>">

    <div class="container">
        <header class="header"> <!-- Header is ALWAYS visible -->
             <a href="index.php" class="logo-link"><img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($app_title); ?> Logo"></a>
        </header>

        <p class="intro-text"> <!-- Hidden when results shown -->
                Weather data via <a href="https://www.weather.gov/documentation/services-web-api" target="_blank" rel="noopener">NOAA API</a>. Geocoding via <a href="https://nominatim.org/" target="_blank" rel="noopener">Nominatim</a> ©<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors.
        </p>

        <!-- Main Input Form (Hidden when results shown) -->
        <form action="index.php" method="post" class="location-form">
            <!-- Hidden input for temp unit, controlled by JS -->
            <input type="hidden" name="temp_unit_hidden" id="temp_unit_hidden" value="<?php echo htmlspecialchars($selected_temp_unit); ?>">

            <!-- Order: Temp -> Activity -> ZIP -->

            <!-- Temp Unit -->
            <div class="form-section temp-toggle-part">
                <label class="form-label">Temperature Unit:</label>
                <div class="temp-toggle-container">
                     <span class="toggle-label <?php echo ($selected_temp_unit === 'F') ? 'active' : ''; ?>" aria-hidden="true">°F</span>
                     <label class="switch" aria-label="Temperature unit toggle, Fahrenheit or Celsius">
                         <input type="checkbox" id="temp_unit_toggle" value="C" <?php echo ($selected_temp_unit === 'C') ? 'checked' : ''; ?>>
                         <span class="slider round"></span>
                     </label>
                     <span class="toggle-label <?php echo ($selected_temp_unit === 'C') ? 'active' : ''; ?>" aria-hidden="true">°C</span>
                </div>
            </div>

            <!-- Activity Selection -->
            <div class="form-section activity-part">
                <label class="form-label" id="activity-label">Select Activity:</label>
                <div class="activity-selector" role="radiogroup" aria-labelledby="activity-label">
                    <?php foreach (CATEGORIES as $key => $category): ?>
                        <?php $is_checked = ($key === $selected_category); ?>
                        <?php $icon_data = get_activity_icon($key); ?>
                        <div class="activity-option">
                            <input type="radio" name="activity_category" id="activity_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                            <label for="activity_<?php echo $key; ?>">
                                <?php if ($icon_data['type'] === 'img'): ?>
                                    <img src="<?php echo htmlspecialchars($icon_data['value']); ?>" alt="" class="activity-icon">
                                <?php else: ?>
                                    <span class="activity-icon-placeholder" aria-hidden="true"><?php echo htmlspecialchars($icon_data['value']); ?></span>
                                <?php endif; ?>
                                <span class="activity-label"><?php echo htmlspecialchars($category['label']); ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ZIP Code Input and Submit Button -->
            <div class="form-section form-section-zip">
                 <label for="zip_code" class="form-label">Enter US ZIP Code:</label>
                 <div class="zip-button-wrapper">
                    <input type="text" name="zip_code" id="zip_code" value="<?php echo htmlspecialchars($zip_code); ?>"
                           placeholder="e.g., 90210" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" required>
                    <button type="submit" name="submit_main_form" value="submit_main"> <!-- Added name -->
                        <span class="icon" aria-hidden="true">👕</span> Get Suggestion
                    </button>
                 </div>
            </div>

             <div class="attribution form-attribution">

             </div>

        </form>

        <!-- Messages Area -->
        <div id="messages" style="<?php echo ($error_message || $info_message) ? 'display: block;' : 'display: none;'; ?>">
            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($info_message && !$error_message): // Only show info if no error ?>
                <div class="message info"><?php echo htmlspecialchars($info_message); ?></div>
            <?php endif; ?>
        </div>


        <!-- Results Area -->
        <?php if ($show_results): ?>
            <div class="results">

                <!-- Current Info -->
                <div class="current-info">
                     <span class="info-item">
                        📍 <strong>Location:</strong> <?php echo htmlspecialchars($location_info['display_name'] ?? $zip_code); ?>
                     </span>
                     <span class="info-item">
                        🌡️ <strong>Temp:</strong> <?php echo format_temperature($forecast_data['temperature'], $forecast_data['temp_unit']); ?>
                     </span>
                     <span class="info-item">
                        <span class="details">(Feels like <?php echo format_temperature($forecast_data['feels_like'], $forecast_data['temp_unit']); ?>)</span>
                     </span>
                     <span class="info-item">
                        <span class="<?php echo 'condition-' . strtolower($forecast_data['primary_condition_key']); ?>">
                           <?php echo htmlspecialchars(SIMPLE_CONDITION_DISPLAY[$forecast_data['primary_condition_key']] ?? $forecast_data['primary_condition_key']); ?>
                        </span>
                     </span>
                      <?php if (!empty($forecast_data['wind_speed']) && $forecast_data['wind_speed'] !== 'N/A'): ?>
                      <span class="info-item">
                        💨 <?php echo htmlspecialchars($forecast_data['wind_speed']); ?> <?php echo htmlspecialchars($forecast_data['wind_direction']); ?>
                      </span>
                      <?php endif; ?>
                      <?php if ($forecast_data['precip_probability'] > 5): ?>
                       <span class="info-item">
                           💧 <?php echo htmlspecialchars($forecast_data['precip_probability']); ?>% chance precip
                       </span>
                      <?php endif; ?>
                       <span class="info-item">
                           <span class="details">(Current: <?php echo htmlspecialchars($selected_category); ?> activity)</span> <!-- Updated label -->
                       </span>
                </div>


                 <!-- Clothing Suggestions -->
                 <?php // Only show clothing if recommendation exists and is not empty ?>
                 <?php if ($clothing_recommendation && !empty($clothing_recommendation['selected_items'])): ?>
                    <div class="clothing-suggestions">
                         <h2>Outfit Suggestion:</h2>
                         <!-- Prominent Item -->
                         <div class="prominent-item">
                             <?php if ($clothing_recommendation['prominent_item_key'] && isset($clothing_recommendation['selected_items'][$clothing_recommendation['prominent_item_key']])): ?>
                                <?php $prominent_item = $clothing_recommendation['selected_items'][$clothing_recommendation['prominent_item_key']]; ?>
                                 <div class="clothing-item">
                                     <img src="<?php echo htmlspecialchars(get_image_path($prominent_item)); ?>" alt="<?php echo htmlspecialchars($prominent_item['name']); ?>">
                                     <span><?php echo htmlspecialchars($prominent_item['name']); ?></span>
                                </div>
                             <?php else: ?>
                                 <p class="no-prominent">No specific jacket/coat recommended for these conditions.</p>
                             <?php endif; ?>
                         </div>
                         <!-- Other Items -->
                         <?php if (!empty($clothing_recommendation['grouped_items'])): ?>
                             <h3 class="other-items-heading">Other Recommended Items:</h3>
                             <div class="other-items">
                                 <?php foreach ($clothing_recommendation['grouped_items'] as $group_name => $items_in_group): ?>
                                     <?php if (!empty($items_in_group)): ?>
                                         <h4><?php echo htmlspecialchars($group_name); ?></h4>
                                         <div class="clothing-type-group">
                                             <?php foreach ($items_in_group as $key => $item): ?>
                                                 <div class="clothing-item">
                                                     <img src="<?php echo htmlspecialchars(get_image_path($item)); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                     <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                 </div>
                                             <?php endforeach; ?>
                                         </div>
                                     <?php endif; ?>
                                 <?php endforeach; ?>
                             </div>
                         <?php else: ?>
                             <p class="no-other-items">No additional items specifically recommended.</p>
                         <?php endif; ?>
                    </div>
                 <?php endif; // End clothing suggestions check ?>


                <!-- Forecast Summary -->
                 <?php
                    $bg_key = $forecast_data['primary_condition_key'];
                    $bg_image = FORECAST_BACKGROUNDS[$bg_key] ?? FORECAST_BACKGROUNDS['Variable'];
                    $bg_style = file_exists($bg_image) ? 'background-image: url(\'' . htmlspecialchars($bg_image) . '\');' : '';
                    $bg_class = !empty($bg_style) ? 'has-background' : '';
                 ?>
                <div class="forecast-summary <?php echo $bg_class; ?>" style="<?php echo $bg_style; ?>">
                    <div class="forecast-content">
                        <h3>Forecast Details</h3>
                        <p><strong>Summary:</strong> <?php echo htmlspecialchars($forecast_data['short_forecast']); ?></p>
                        <?php if (!empty($forecast_data['detailed_forecast'])): ?>
                            <p><strong>Details:</strong> <?php echo htmlspecialchars($forecast_data['detailed_forecast']); ?></p>
                        <?php endif; ?>
                         <p>
                             <strong>Temp Range:</strong> <?php echo format_temperature($forecast_data['temperature'], $forecast_data['temp_unit']); ?>
                             (Feels like <?php echo format_temperature($forecast_data['feels_like'], $forecast_data['temp_unit']); ?>)
                             - Classified as: <strong><?php echo htmlspecialchars($forecast_data['temp_band_key']); ?></strong>
                         </p>
                         <p>
                            <strong>Conditions:</strong>
                            <?php
                                $active_conditions = [];
                                foreach($forecast_data['conditions'] as $key => $value) { if ($value === true) { $display_c = ucfirst(str_replace('is_', '', $key)); $active_conditions[] = $display_c; } }
                                echo !empty($active_conditions) ? htmlspecialchars(implode(', ', $active_conditions)) : 'Variable';
                            ?>
                         </p>
                         <i>Forecast valid around: <?php echo htmlspecialchars(date('M d, Y H:i T', strtotime($forecast_data['forecast_time']))); ?></i>
                    </div>
                </div>

                <!-- Abridged Activity Form -->
                <form action="index.php" method="post" id="results-activity-form">
                     <input type="hidden" name="results_zip_code" value="<?php echo htmlspecialchars($zip_code); ?>">
                     <input type="hidden" name="results_temp_unit" value="<?php echo htmlspecialchars($selected_temp_unit); ?>">
                     <h4>Change Activity:</h4>
                     <div class="activity-selector" role="radiogroup" aria-label="Change Activity">
                         <?php foreach (CATEGORIES as $key => $category): ?>
                             <?php $is_checked = ($key === $selected_category); ?>
                             <?php $icon_data = get_activity_icon($key); ?>
                             <div class="activity-option">
                                 <input type="radio" name="results_activity_category" id="results_activity_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                                 <label for="results_activity_<?php echo $key; ?>">
                                     <?php if ($icon_data['type'] === 'img'): ?>
                                         <img src="<?php echo htmlspecialchars($icon_data['value']); ?>" alt="" class="activity-icon">
                                     <?php else: ?>
                                         <span class="activity-icon-placeholder" aria-hidden="true"><?php echo htmlspecialchars($icon_data['value']); ?></span>
                                     <?php endif; ?>
                                     <span class="activity-label"><?php echo htmlspecialchars($category['label']); ?></span>
                                 </label>
                             </div>
                         <?php endforeach; ?>
                     </div>
                     <button type="submit" name="submit_results_form" value="submit_results">
                         <span class="icon" aria-hidden="true">🔄</span> Update Outfit
                     </button>
                 </form>


                 <!-- Optional Debug Output -->
                 <?php /*
                 <details>
                    <summary>Debug Info</summary>
                    <pre style="font-size: 0.8em; background: #eee; padding: 10px; border-radius: 4px; max-height: 400px; overflow: auto;">
Location Info: <?php print_r($location_info); ?>
Processed Forecast: <?php print_r($forecast_data); ?>
Clothing Recommendation: <?php print_r($clothing_recommendation); ?>
Form Source: <?php echo $form_source; ?>
Selected ZIP: <?php echo $zip_code; ?>
Selected Cat: <?php echo $selected_category; ?>
Selected Temp: <?php echo $selected_temp_unit; ?>
Error: <?php echo $error_message; ?>
Info: <?php echo $info_message; ?>
                    </pre>
                 </details>
                 */ ?>

            </div> <!-- /.results -->
        <?php endif; ?>


    </div> <!-- /.container -->

    <footer>
        <div class="footer-links">
             <p>
                 <?php foreach (FOOTER_LINKS as $link): ?>
                      <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($link['text']); ?></a>
                 <?php endforeach; ?>
             </p>
             <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($app_title); ?> v<?php echo htmlspecialchars($app_version); ?>. <a href="https://timnetworks.com/" target="_blank" rel="noopener">timnetworks</a> dot com.</p>
             <p><a href="./clothing-debug.php">clothing-items</a> | <a href="./outfit-simulator.php">clothing-sets</a>
        </div>
    </footer>

    <script src="script.js?v=<?php echo htmlspecialchars($app_version); ?>"></script>
    <?php if ($show_results): ?>
    <script>
        // Simple scroll-to-results after page load with results
        document.addEventListener('DOMContentLoaded', function() {
            const messagesDiv = document.getElementById('messages');
            const offset = 15; // Small offset from top edge
            // Scroll messages into view first if they exist and are visible, otherwise results container
            const targetElement = (messagesDiv && messagesDiv.style.display !== 'none') ? messagesDiv : document.querySelector('.results');
            if (targetElement) {
                // Check if the form submission source was the main form, only scroll then
                const submittedFromMain = <?php echo ($form_source === 'main') ? 'true' : 'false'; ?>;
                if (submittedFromMain) {
                    const elementTop = targetElement.getBoundingClientRect().top + window.pageYOffset - offset;
                     window.scrollTo({ top: elementTop, behavior: 'smooth' });
                }
            } else if (<?php echo ($form_source === 'main') ? 'true' : 'false'; ?>) {
                 // Fallback scroll slightly down only if submitted from main form
                 window.scrollTo({ top: 50, behavior: 'smooth' });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
