<?php
// Check if GD is installed and enabled
if (extension_loaded('gd') && function_exists('gd_info')) {
    echo "GD is installed and enabled.\n";
    $info = gd_info();
    echo "GD Version: " . (isset($info['GD Version']) ? $info['GD Version'] : 'Unknown') . "\n";
    
    // Check for specific GD features
    echo "FreeType Support: " . (isset($info['FreeType Support']) && $info['FreeType Support'] ? 'Yes' : 'No') . "\n";
    echo "JPG Support: " . (isset($info['JPEG Support']) && $info['JPEG Support'] ? 'Yes' : 'No') . "\n";
    echo "PNG Support: " . (isset($info['PNG Support']) && $info['PNG Support'] ? 'Yes' : 'No') . "\n";
} else {
    echo "GD is NOT installed or enabled.\n";
}

// Check if Imagick is installed and enabled
if (extension_loaded('imagick') && class_exists('Imagick')) {
    echo "\nImagick is installed and enabled.\n";
    $imagick = new Imagick();
    echo "Imagick Version: " . $imagick->getVersion()['versionString'] . "\n";
} else {
    echo "\nImagick is NOT installed or enabled.\n";
}

// Check for temp directory and permissions
$upload_dir = wp_upload_dir();
if (function_exists('wp_upload_dir')) {
    $upload_dir = wp_upload_dir();
    echo "\nWordPress Upload Directory: " . $upload_dir['path'] . "\n";
    echo "Is Writable: " . (is_writable($upload_dir['path']) ? 'Yes' : 'No') . "\n";
} else {
    $temp_dir = sys_get_temp_dir();
    echo "\nSystem Temp Directory: " . $temp_dir . "\n";
    echo "Is Writable: " . (is_writable($temp_dir) ? 'Yes' : 'No') . "\n";
}
