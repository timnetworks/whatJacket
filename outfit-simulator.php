<?php // FILE: outfit-simulator.php
/**
 * whatJacket Application - Outfit Simulator Debug View
 */

// Config is needed for clothing definitions, categories, temp bands etc.
require_once 'config.php';
// Functions needed for clothing selection and image paths
require_once 'functions.php';

// --- Page Variables ---
$app_title = APP_TITLE ?? 'whatJacket';
$app_version = APP_VERSION ?? 'debug';
$page_title = "Outfit Simulator - " . $app_title;
$theme_color = THEME_COLOR ?? '#ffffff';

// --- Form Defaults & Processing ---
$selected_temp_band = array_key_first(TEMP_BANDS); // Default to the first temp band key
$selected_category = DEFAULT_CATEGORY;
$simulated_conditions_flags = []; // e.g., ['is_windy' => false, 'is_rainy' => true, ...]
$simulated_wind_mph = 5; // Default low wind
$simulated_precip_prob = 0; // Default no precip
$clothing_recommendation = null;
$simulated_forecast_for_display = null; // Store the array we built

// Initialize all possible condition flags to false
$possible_conditions = array_keys(CONDITION_KEYWORDS);
// Map 'is_rainy' etc. to simpler keys for form names if desired, or use full keys
$form_condition_keys = [
    'windy' => 'is_windy',
    'rainy' => 'is_rainy',
    'drizzling' => 'is_drizzling',
    'snowy' => 'is_snowy',
    'sunny' => 'is_sunny',
    'cloudy' => 'is_cloudy',
    'foggy' => 'is_foggy',
    'thunderstorm' => 'is_thunderstorm',
    'severe' => 'is_severe',
    'scorching' => 'is_scorching', // Though this is usually temp driven
];
foreach ($form_condition_keys as $form_key => $flag_key) {
    $simulated_conditions_flags[$flag_key] = false;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get selections from form
    $selected_temp_band = isset($_POST['temp_band']) && array_key_exists($_POST['temp_band'], TEMP_BANDS)
                          ? $_POST['temp_band']
                          : array_key_first(TEMP_BANDS);

    $selected_category = isset($_POST['category']) && array_key_exists($_POST['category'], CATEGORIES)
                         ? $_POST['category']
                         : DEFAULT_CATEGORY;

    $simulated_wind_mph = isset($_POST['wind_mph']) ? (int)$_POST['wind_mph'] : 5;
    $simulated_precip_prob = isset($_POST['precip_prob']) ? (int)$_POST['precip_prob'] : 0;


    // Rebuild the conditions flags based on checkboxes
    foreach ($form_condition_keys as $form_key => $flag_key) {
        $simulated_conditions_flags[$flag_key] = isset($_POST['condition_' . $form_key]); // Check if checkbox was submitted
    }

    // --- Construct Simulated Forecast ---
    // Mimic the structure expected by select_clothing()
    $simulated_forecast = [
        'temp_band_key' => $selected_temp_band,
        'conditions' => $simulated_conditions_flags,
        'wind_mph' => $simulated_wind_mph,
        // Determine heavy precip based on probability AND specific condition flags
        'is_heavy_precip' => ($simulated_precip_prob >= HEAVY_RAIN_PROBABILITY_THRESHOLD)
                             && ($simulated_conditions_flags['is_rainy'] || $simulated_conditions_flags['is_snowy'] || $simulated_conditions_flags['is_thunderstorm']),
        // Add other keys used by select_clothing if any, otherwise null/defaults are fine
        'precip_probability' => $simulated_precip_prob,
        // These aren't directly used by select_clothing but good for display/context
        'temperature' => null,
        'feels_like' => null,
        'temp_unit' => 'SIM', // Indicate simulated unit
        'short_forecast' => 'Simulated Conditions',
        'detailed_forecast' => '',
        'wind_speed' => $simulated_wind_mph . ' mph',
        'wind_direction' => 'N/A',
        'primary_condition_key' => get_primary_condition_key($simulated_conditions_flags), // Use helper to get primary display key
        'forecast_time' => date('c'), // Current time
    ];
    $simulated_forecast_for_display = $simulated_forecast; // Keep a copy for display


    // --- Call the Selection Logic ---
    $clothing_recommendation = select_clothing($simulated_forecast, $selected_category);

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Prevent indexing of debug page -->
    <meta name="robots" content="noindex, nofollow">

    <!-- Basic PWA / Mobile settings -->
    <meta name="theme-color" content="<?php echo htmlspecialchars($theme_color); ?>">
    <link rel="icon" href="./img/favicon.ico" sizes="any">
    <link rel="icon" href="./img/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="./img/apple-touch-icon.png">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="style.css?v=<?php echo htmlspecialchars($app_version); ?>">

</head>
<body>

    <div class="container">
        <header class="header">
             <a href="index.php" class="logo-link"><img src="<?php echo htmlspecialchars(LOGO_IMAGE_PATH ?? './img/wj-logo.png'); ?>" alt="<?php echo htmlspecialchars($app_title); ?> Logo"></a>
        </header>

        <h1>Outfit Simulator</h1>
        <p>Select conditions manually to test the outfit generation logic from <code>functions.php</code>.</p>
        <p><a href="index.php">&laquo; Back to Main Application</a> | <a href="clothing-debug.php">View Clothing Items &raquo;</a></p>
        <hr>

        <div class="outfit-simulator-v2"> <?php // Use existing debug styles ?>

            <div class="simulator-controls">
                <h3>Simulation Controls</h3>
                <form action="outfit-simulator.php" method="post">

                    <div>
                        <label for="temp_band">Temperature Band:</label>
                        <select name="temp_band" id="temp_band">
                            <?php foreach (TEMP_BANDS as $key => $band): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($key === $selected_temp_band) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($key); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="category">Activity Category:</label>
                        <select name="category" id="category">
                             <?php foreach (CATEGORIES as $key => $cat): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($key === $selected_category) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="wind_mph">Simulated Wind (mph):</label>
                        <input type="number" name="wind_mph" id="wind_mph" value="<?php echo htmlspecialchars($simulated_wind_mph); ?>" min="0" max="100" step="1" style="width: 70px; padding: 5px;">
                         <small>(Used for logic like umbrella cutoff, windy flag)</small>
                    </div>

                     <div>
                        <label for="precip_prob">Simulated Precip Probability (%):</label>
                        <input type="number" name="precip_prob" id="precip_prob" value="<?php echo htmlspecialchars($simulated_precip_prob); ?>" min="0" max="100" step="5" style="width: 70px; padding: 5px;">
                         <small>(Used for `is_heavy_precip` check)</small>
                    </div>

                    <div>
                        <label>Simulated Conditions:</label>
                        <div class="condition-checkboxes">
                            <?php foreach ($form_condition_keys as $form_key => $flag_key): ?>
                                <div>
                                    <label for="condition_<?php echo $form_key; ?>">
                                        <input type="checkbox" name="condition_<?php echo $form_key; ?>" id="condition_<?php echo $form_key; ?>" value="1"
                                               <?php echo ($simulated_conditions_flags[$flag_key] ?? false) ? 'checked' : ''; ?> >
                                        <?php echo ucfirst($form_key); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <button type="submit">Simulate Outfit</button>
                </form>
            </div>


            <div class="simulator-results">
                 <h3>Generated Outfit</h3>
                 <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                     <?php if ($clothing_recommendation && !empty($clothing_recommendation['selected_items'])): ?>

                         <div class="outfit-results-display">
                             <!-- Prominent Item -->
                             <div class="prominent-item-container">
                                 <h4>Prominent Item:</h4>
                                 <?php if ($clothing_recommendation['prominent_item_key'] && isset($clothing_recommendation['selected_items'][$clothing_recommendation['prominent_item_key']])): ?>
                                    <?php $prominent_item = $clothing_recommendation['selected_items'][$clothing_recommendation['prominent_item_key']]; ?>
                                     <div class="outfit-item">
                                         <img src="<?php echo htmlspecialchars(get_image_path($prominent_item)); ?>" alt="">
                                         <span><?php echo htmlspecialchars($prominent_item['name']); ?><br><small>(<?php echo htmlspecialchars($clothing_recommendation['prominent_item_key']); ?>)</small></span>
                                    </div>
                                 <?php else: ?>
                                     <p class="no-results" style="font-size: 0.9em;">(None Selected)</p>
                                 <?php endif; ?>
                             </div>

                             <hr style="margin: 15px 0;">

                             <!-- Other Items -->
                             <h4>Other Items:</h4>
                              <?php if (!empty($clothing_recommendation['grouped_items'])): ?>
                                 <?php foreach ($clothing_recommendation['grouped_items'] as $group_name => $items_in_group): ?>
                                     <?php if (!empty($items_in_group)): ?>
                                         <h5><?php echo htmlspecialchars($group_name); ?>:</h5>
                                         <div class="outfit-display-group">
                                             <?php foreach ($items_in_group as $key => $item): ?>
                                                 <div class="outfit-item">
                                                     <img src="<?php echo htmlspecialchars(get_image_path($item)); ?>" alt="">
                                                     <span><?php echo htmlspecialchars($item['name']); ?><br><small>(<?php echo htmlspecialchars($key); ?>)</small></span>
                                                 </div>
                                             <?php endforeach; ?>
                                         </div>
                                     <?php endif; ?>
                                 <?php endforeach; ?>
                              <?php else: ?>
                                <p class="no-results">(None)</p>
                              <?php endif; ?>

                         </div><!-- /.outfit-results-display -->

                     <?php elseif($clothing_recommendation): // Recommendation generated, but empty ?>
                        <p class="no-results">No suitable clothing items found for the selected combination.</p>
                     <?php else: // Form submitted but something failed before selection (shouldn't happen here) ?>
                         <p class="no-results">Could not generate recommendation.</p>
                     <?php endif; ?>

                     <?php // Display the simulated forecast data used ?>
                      <details style="margin-top: 20px; font-size: 0.9em;">
                         <summary style="font-size: 1.1em; padding: 8px 12px;">View Simulated Forecast Input</summary>
                         <pre style="background: #eee; padding: 10px; border-radius: 4px; max-height: 300px; overflow: auto; font-size: 0.85em;"><?php
                             echo "Selected Temp Band: " . htmlspecialchars($selected_temp_band) . "\n";
                             echo "Selected Category: " . htmlspecialchars($selected_category) . "\n";
                             echo "--- Simulated Forecast Array Used ---\n";
                             print_r($simulated_forecast_for_display);
                             echo "\n--- Clothing Function Output ---\n";
                             print_r($clothing_recommendation); // Also print the raw output array
                         ?></pre>
                      </details>

                 <?php else: ?>
                    <p class="no-results">Select conditions and click "Simulate Outfit" to see results.</p>
                 <?php endif; ?>
            </div><!-- /.simulator-results -->

        </div><!-- /.outfit-simulator-v2 -->

        <hr style="margin-top: 30px;">
        <p><a href="index.php">&laquo; Back to Main Application</a> | <a href="clothing-debug.php">View Clothing Items &raquo;</a></p>

    </div><!-- /.container -->

     <footer>
        <div class="footer-links">
             <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($app_title); ?> v<?php echo htmlspecialchars($app_version); ?> Simulator Page</p>
        </div>
    </footer>

</body>
</html>