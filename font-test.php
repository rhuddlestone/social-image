<?php
/**
 * Font Test Script
 * This script tests if PHP GD can use system fonts
 */

// Create a simple image
$image = imagecreatetruecolor(400, 200);
$bg_color = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 0, 0, 0);
imagefill($image, 0, 0, $bg_color);

// Try to use a system font
$font_path = __DIR__ . '/assets/fonts/OpenSans-Regular.ttf';
echo "Testing font: $font_path\n";
echo "File exists: " . (file_exists($font_path) ? 'Yes' : 'No') . "\n";
echo "File size: " . (file_exists($font_path) ? filesize($font_path) : 'N/A') . " bytes\n";

// Test if we can use the font
$result = imagettftext($image, 20, 0, 50, 100, $text_color, $font_path, 'Hello World');
echo "imagettftext result: " . ($result !== false ? 'Success' : 'Failed') . "\n";

// Clean up
imagedestroy($image);

// List available system fonts
echo "\nSystem fonts that might be available:\n";
$system_fonts = [
    '/System/Library/Fonts/Helvetica.ttc',
    '/System/Library/Fonts/Times.ttc',
    '/System/Library/Fonts/Arial.ttf',
    '/Library/Fonts/Arial.ttf',
    '/System/Library/Fonts/Supplemental/Arial.ttf',
    '/System/Library/Fonts/Supplemental/Courier New.ttf',
    '/System/Library/Fonts/Supplemental/Times New Roman.ttf'
];

foreach ($system_fonts as $font) {
    echo "$font: " . (file_exists($font) ? 'Exists' : 'Not found') . "\n";
}
