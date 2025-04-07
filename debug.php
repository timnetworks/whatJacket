<?php

// --- Configuration ---
// Adjust this path if your config file is located elsewhere relative to this script.
$configFilePath = 'config.php';

// --- Script Start ---

// Error reporting for debugging (optional, but helpful)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file
if (file_exists($configFilePath)) {
    require_once $configFilePath;
} else {
    die("ERROR: Configuration file not found at '{$configFilePath}'. Please make sure it exists and the path is correct.");
}

// Check if CLOTHING_ITEMS constant is defined
if (!defined('CLOTHING_ITEMS') || !is_array(CLOTHING_ITEMS)) {
    die("ERROR: The 'CLOTHING_ITEMS' constant is not defined or is not an array in '{$configFilePath}'.");
}

$clothingItems = CLOTHING_ITEMS;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clothing Item Image Debugger</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            text-align: center;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); /* Responsive grid */
            gap: 20px;
        }
        .item-card {
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #fff;
            text-align: center;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Pushes content apart */
            min-height: 200px; /* Ensure cards have some height */
        }
        .item-card img {
            max-width: 100%;
            height: 100px; /* Fixed height for consistency */
            object-fit: contain; /* Scale image while maintaining aspect ratio */
            margin-top: 10px;
            margin-bottom: 10px;
            background-color: #eee; /* Background for transparent images */
            display: block; /* Center image */
            margin-left: auto;
            margin-right: auto;
        }
        .item-card .name {
            font-weight: bold;
            margin-bottom: 5px;
            word-wrap: break-word; /* Wrap long names */
            flex-grow: 1; /* Allow name to take available space */
        }
         .item-card .key {
            font-size: 0.8em;
            color: #555;
            margin-bottom: 5px;
            word-wrap: break-word;
        }
        .item-card .path {
            font-size: 0.7em;
            color: #888;
            word-wrap: break-word; /* Wrap long paths */
        }
        .missing-image {
            border: 2px dashed red;
        }
        .warning {
            color: red;
            font-weight: bold;
            font-size: 0.9em;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <h1>Clothing Item Image Debugger</h1>
    <p>This page displays all clothing items defined in '<?php echo htmlspecialchars($configFilePath); ?>'. Check for missing images (indicated by a placeholder/broken image icon and a red border/warning).</p>

    <div class="items-grid">
        <?php foreach ($clothingItems as $key => $item) : ?>
            <?php
                // Basic validation for essential keys
                $itemName = isset($item['name']) ? htmlspecialchars($item['name']) : 'NAME MISSING';
                $imagePath = isset($item['img']) ? htmlspecialchars($item['img']) : null;
                $itemKey = htmlspecialchars($key);
                $imageExists = $imagePath && file_exists($imagePath);
                $cardClass = $imageExists ? '' : ' missing-image';
            ?>
            <div class="item-card<?php echo $cardClass; ?>">
                <div>
                    <div class="name"><?php echo $itemName; ?></div>
                    <div class="key">[<?php echo $itemKey; ?>]</div>
                </div>
                <div>
                    <?php if ($imagePath): ?>
                        <img src="<?php echo $imagePath; ?>" alt="Image for <?php echo $itemName; ?>" title="<?php echo $itemName; ?> - <?php echo $imagePath; ?>">
                        <div class="path"><?php echo $imagePath; ?></div>
                        <?php if (!$imageExists): ?>
                            <div class="warning">WARNING: Image file not found!</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="warning">WARNING: 'img' path not defined for this item!</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Generic Fallback Images (Check Existence)</h2>
    <p>These are often used in `img_fallback`. Check if they exist in your `img` directory.</p>
    <ul>
        <?php
        // List common fallback images mentioned in config comments or likely needed
        $generic_fallbacks = [
            './img/shirt.jpg', './img/sweater.jpg', './img/pants.jpg', './img/jacket.jpg',
            './img/hat.jpg', './img/gloves.jpg', './img/accessory.jpg', './img/socks.jpg',
             './img/shoes.jpg', './img/placeholder.png'
        ];
        foreach ($generic_fallbacks as $fallback) {
            $exists = file_exists($fallback);
            echo "<li>" . htmlspecialchars($fallback) . " - ";
            if ($exists) {
                echo "<strong style='color: green;'>Exists</strong>";
                 echo " <img src='".htmlspecialchars($fallback)."' alt='".htmlspecialchars($fallback)."' style='height: 20px; vertical-align: middle; background: #eee;'>";
            } else {
                echo "<strong style='color: red;'>MISSING!</strong>";
            }
            echo "</li>";
        }
        ?>
    </ul>

</body>
</html>
