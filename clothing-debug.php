<?php // FILE: clothing-debug.php
/**
 * whatJacket Application - Clothing Item Debug View
 */

// Config is needed for clothing definitions and app details
require_once 'config.php';
// Functions *might* be needed if we use helpers, but we'll do checks directly here
// require_once 'functions.php'; // Optional, currently unused

// --- Page Variables ---
$app_title = APP_TITLE ?? 'whatJacket';
$app_version = APP_VERSION ?? 'debug';
$page_title = "Clothing Item Debug - " . $app_title;
$theme_color = THEME_COLOR ?? '#ffffff';

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
             <?php /* No home button here either */ ?>
        </header>

        <h1>Clothing Item Debug View</h1>
        <p>Displays all clothing items defined in <code>config.php</code> along with their properties and image status.</p>
        <p><a href="index.php">&laquo; Back to Main Application</a> | <a href="outfit-simulator.php">View Outfit Simulator &raquo;</a>
        <hr>

        <?php if (!defined('CLOTHING_ITEMS') || !is_array(CLOTHING_ITEMS)): ?>
            <div class="message error">Error: CLOTHING_ITEMS constant is not defined or not an array in config.php.</div>
        <?php else: ?>
            <div class="items-grid">
                <?php foreach (CLOTHING_ITEMS as $key => $item): ?>
                    <?php
                        // Image path checks
                        $primary_img = $item['img'] ?? null;
                        $fallback_img = $item['img_fallback'] ?? null;
                        $placeholder_img = './img/placeholder.png'; // Define a standard placeholder

                        $primary_exists = $primary_img && file_exists($primary_img);
                        $fallback_exists = $fallback_img && file_exists($fallback_img);

                        $display_img = $placeholder_img; // Default to placeholder
                        $status_message = '';
                        $status_class = 'status-error'; // Default to error if nothing found
                        $card_extra_class = '';

                        if ($primary_exists) {
                            $display_img = $primary_img;
                            $status_message = 'Primary OK';
                            $status_class = 'status-ok';
                        } elseif ($fallback_exists) {
                            $display_img = $fallback_img;
                            $status_message = 'Warning: Primary Missing, Fallback Used';
                            $status_class = 'status-warning';
                             $card_extra_class = 'primary-missing'; // Add class to card for styling
                        } else {
                            // Neither primary nor fallback exists
                            $status_message = 'Error: Primary & Fallback Missing';
                            $status_class = 'status-error';
                            $card_extra_class = 'primary-missing'; // Add class to card for styling
                            // Keep $display_img as the placeholder
                        }

                        // Ensure display_img exists, otherwise use placeholder (final safety check)
                        if (!file_exists($display_img)) {
                             $display_img = $placeholder_img;
                        }
                    ?>
                    <div class="item-card <?php echo $card_extra_class; ?>">
                        <div class="name"><?php echo htmlspecialchars($item['name'] ?? 'N/A'); ?></div>
                        <div class="key"><?php echo htmlspecialchars($key); ?></div>

                        <div class="image-container">
                            <img src="<?php echo htmlspecialchars($display_img); ?>" alt="<?php echo htmlspecialchars($item['name'] ?? $key); ?>">
                        </div>

                        <div class="details">
                             <span><strong>Type:</strong> <?php echo htmlspecialchars($item['type'] ?? 'N/A'); ?></span>
                             <span><strong>Layer:</strong> <?php echo htmlspecialchars($item['layer'] ?? 'N/A'); ?></span>
                             <span><strong>Categories:</strong> <?php echo htmlspecialchars(is_array($item['category'] ?? null) ? implode(', ', $item['category']) : ($item['category'] ?? 'N/A')); ?></span>
                             <span><strong>Temp Bands:</strong> <?php echo htmlspecialchars(is_array($item['temp_bands'] ?? null) ? implode(', ', $item['temp_bands']) : 'N/A'); ?></span>
                             <span><strong>Thermal Value:</strong> <?php echo htmlspecialchars($item['thermal_value'] ?? 'N/A'); ?></span>
                             <span><strong>Water Resist:</strong> <?php echo htmlspecialchars($item['water_resistance'] ?? 'N/A'); ?></span>
                             <span><strong>Wind Resist:</strong> <?php echo htmlspecialchars($item['wind_resistance'] ?? 'N/A'); ?></span>
                             <span><strong>Breathability:</strong> <?php echo htmlspecialchars($item['breathability'] ?? 'N/A'); ?></span>
                             <span><strong>Sun Protect:</strong> <?php echo isset($item['sun_protection']) ? ($item['sun_protection'] ? 'Yes' : 'No') : 'N/A'; ?></span>
                              <span><strong>Special Cond:</strong> <?php echo htmlspecialchars(is_array($item['special_conditions'] ?? null) && !empty($item['special_conditions']) ? implode(', ', $item['special_conditions']) : 'None'); ?></span>
                        </div>

                        <div class="path-info">
                             <span class="label">Primary Img:</span> <?php echo htmlspecialchars($primary_img ?? 'Not Set'); ?><br>
                             <span class="label">Fallback Img:</span> <?php echo htmlspecialchars($fallback_img ?? 'Not Set'); ?>
                        </div>

                        <div class="status-message <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($status_message); ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div><!-- /.items-grid -->
        <?php endif; // End check for CLOTHING_ITEMS ?>

        <hr>
        <p><a href="index.php">&laquo; Back to Main Application</a> | <a href="outfit-simulator.php">View Outfit Simulator &raquo;</a>

    </div><!-- /.container -->

    <footer>
        <div class="footer-links">
             <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($app_title); ?> v<?php echo htmlspecialchars($app_version); ?> Debug Page</p>
        </div>
    </footer>

</body>
</html>
