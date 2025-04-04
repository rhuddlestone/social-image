<?php
/**
 * Image Generator for Social Image Plugin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle image generation
 */
class Social_Image_Generator {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Add AJAX handlers for image generation
        add_action('wp_ajax_social_image_generate_preview', array($this, 'ajax_generate_preview'));
        add_action('wp_ajax_social_image_generate_image', array($this, 'ajax_generate_image'));
    }

    /**
     * AJAX handler for generating a preview image
     */
    public function ajax_generate_preview() {
        try {
            // Debug: Log function entry
            error_log('AJAX: Entering ajax_generate_preview');
            
            // Check nonce for security
            error_log('AJAX: Checking nonce');
            check_ajax_referer('social_image_nonce', 'nonce');

            // Check user capabilities
            error_log('AJAX: Checking user capabilities');
            if (!current_user_can('edit_posts')) {
                error_log('AJAX: User does not have edit_posts capability');
                wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'social-image')));
            }

            // Get template data
            error_log('AJAX: Getting template data from POST');
            $template_data = isset($_POST['template_data']) ? json_decode(stripslashes($_POST['template_data']), true) : array();

            // Debug: Log template data
            error_log('Template data: ' . print_r($template_data, true));

            if (empty($template_data)) {
                error_log('AJAX: No template data provided');
                wp_send_json_error(array('message' => __('No template data provided.', 'social-image')));
            }

            // Check if GD or Imagick is available
            error_log('AJAX: Checking for image processing libraries');
            if (!function_exists('imagecreatetruecolor')) {
                error_log('AJAX: GD library function imagecreatetruecolor not available');
            }
            if (!class_exists('Imagick')) {
                error_log('AJAX: Imagick class not available');
            }
            
            if (!function_exists('imagecreatetruecolor') && !class_exists('Imagick')) {
                error_log('AJAX: No image processing libraries available');
                wp_send_json_error(array('message' => __('Image processing libraries (GD or Imagick) are not available.', 'social-image')));
            }

            // Generate the preview image
            error_log('AJAX: Calling generate_image');
            $image_url = $this->generate_image($template_data, true);

            if ($image_url) {
                error_log('AJAX: Image generated successfully: ' . $image_url);
                wp_send_json_success(array(
                    'image_url' => $image_url
                ));
            } else {
                error_log('AJAX: Failed to generate image');
                wp_send_json_error(array('message' => __('Error generating preview image. Check server error logs for details.', 'social-image')));
            }
        } catch (Exception $e) {
            // Log the error
            error_log('Social Image preview generation error: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => __('Error generating preview image: ', 'social-image') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for generating a final image
     */
    public function ajax_generate_image() {
        // Check nonce for security
        check_ajax_referer('social_image_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'social-image')));
        }

        // Get template data and post ID
        $template_data = isset($_POST['template_data']) ? json_decode(stripslashes($_POST['template_data']), true) : array();
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($template_data) || empty($post_id)) {
            wp_send_json_error(array('message' => __('Missing required data.', 'social-image')));
        }

        // Generate the final image
        $image_url = $this->generate_image($template_data, false);

        if ($image_url) {
            // Save the image URL to post meta
            update_post_meta($post_id, '_social_image_pinterest', $image_url);

            wp_send_json_success(array(
                'image_url' => $image_url,
                'message' => __('Pinterest image generated successfully!', 'social-image')
            ));
        } else {
            wp_send_json_error(array('message' => __('Error generating Pinterest image.', 'social-image')));
        }
    }

    /**
     * Generate an image based on template data
     *
     * @param array $template_data The template data
     * @param bool $is_preview Whether this is a preview or final image
     * @return string|bool The URL of the generated image or false on failure
     */
    public function generate_image($template_data, $is_preview = false) {
        try {
            // Debug: Log function entry
            error_log('Generating image: ' . ($is_preview ? 'preview' : 'final'));

            // Check if GD or Imagick is available
            if (!function_exists('imagecreatetruecolor') && !class_exists('Imagick')) {
                error_log('Error: Neither GD nor Imagick is available');
                return false;
            }

            // Extract template data
            $width = isset($template_data['width']) ? intval($template_data['width']) : 1000;
            $height = isset($template_data['height']) ? intval($template_data['height']) : 1500;
            $background_color = isset($template_data['background_color']) ? $template_data['background_color'] : '#ffffff';
            $background_image = isset($template_data['background_image']) ? $template_data['background_image'] : '';
            $text_elements = isset($template_data['text_elements']) ? $template_data['text_elements'] : array();
            $image_elements = isset($template_data['image_elements']) ? $template_data['image_elements'] : array();

            // Debug: Log extracted data
            error_log("Image dimensions: {$width}x{$height}");
            error_log("Background color: {$background_color}");
            error_log("Background image: {$background_image}");
            error_log("Text elements: " . count($text_elements));
            error_log("Image elements: " . count($image_elements));

            // Use GD library for image generation
            if (function_exists('imagecreatetruecolor')) {
                error_log('Using GD library for image generation');
                return $this->generate_image_gd($width, $height, $background_color, $background_image, $text_elements, $image_elements, $is_preview);
            }
            // Use Imagick if available
            else if (class_exists('Imagick')) {
                error_log('Using Imagick library for image generation');
                return $this->generate_image_imagick($width, $height, $background_color, $background_image, $text_elements, $image_elements, $is_preview);
            }

            error_log('Error: No image processing library available');
            return false;
        } catch (Exception $e) {
            error_log('Error generating image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate an image using the GD library
     */
    private function generate_image_gd($width, $height, $background_color, $background_image, $text_elements, $image_elements, $is_preview) {
        try {
            error_log('Starting GD image generation');

            // Create a new image
            $image = imagecreatetruecolor($width, $height);
            if (!$image) {
                error_log('Failed to create GD image');
                return false;
            }
            error_log('Created GD image');

            // Set background color
            $r = hexdec(substr($background_color, 1, 2));
            $g = hexdec(substr($background_color, 3, 2));
            $b = hexdec(substr($background_color, 5, 2));
            $bg_color = imagecolorallocate($image, $r, $g, $b);
            imagefill($image, 0, 0, $bg_color);
            error_log('Set background color');

            // Add background image if provided
            if (!empty($background_image)) {
                error_log('Loading background image: ' . $background_image);
                $bg_img = $this->load_image_from_url($background_image);
                if ($bg_img) {
                    $bg_width = imagesx($bg_img);
                    $bg_height = imagesy($bg_img);

                    // Scale and center the background image
                    $scale = max($width / $bg_width, $height / $bg_height);
                    $new_bg_width = $bg_width * $scale;
                    $new_bg_height = $bg_height * $scale;
                    $x = ($width - $new_bg_width) / 2;
                    $y = ($height - $new_bg_height) / 2;

                    imagecopyresampled($image, $bg_img, $x, $y, 0, 0, $new_bg_width, $new_bg_height, $bg_width, $bg_height);
                    imagedestroy($bg_img);
                    error_log('Added background image');
                } else {
                    error_log('Failed to load background image');
                }
            }

            // Add image elements
            foreach ($image_elements as $element) {
                if (empty($element['placeholder_image'])) {
                    continue;
                }

                error_log('Loading image element: ' . $element['placeholder_image']);
                $img = $this->load_image_from_url($element['placeholder_image']);
                if (!$img) {
                    error_log('Failed to load image element');
                    continue;
                }

                $img_width = imagesx($img);
                $img_height = imagesy($img);

                // Calculate position and size
                $element_width = ($element['width'] / 100) * $width;
                $element_height = ($element['height'] / 100) * $height;
                $element_x = ($element['position_x'] / 100) * $width - ($element_width / 2);
                $element_y = ($element['position_y'] / 100) * $height - ($element_height / 2);

                imagecopyresampled($image, $img, $element_x, $element_y, 0, 0, $element_width, $element_height, $img_width, $img_height);
                imagedestroy($img);
                error_log('Added image element');
            }

            // Add text elements
            foreach ($text_elements as $element) {
                if (empty($element['text'])) {
                    continue;
                }

                error_log('Adding text element: ' . substr($element['text'], 0, 20) . '...');

                // Set text color
                $r = hexdec(substr($element['font_color'], 1, 2));
                $g = hexdec(substr($element['font_color'], 3, 2));
                $b = hexdec(substr($element['font_color'], 5, 2));
                $text_color = imagecolorallocate($image, $r, $g, $b);

                // Calculate position and size
                $font_size = $element['font_size'];
                $element_width = ($element['width'] / 100) * $width;
                $element_x = ($element['position_x'] / 100) * $width;
                $element_y = ($element['position_y'] / 100) * $height;

                // Try to use a system font that we know exists on Mac
                $system_fonts = [
                    '/System/Library/Fonts/Helvetica.ttc',
                    '/System/Library/Fonts/Times.ttc',
                    '/System/Library/Fonts/Supplemental/Arial.ttf',
                    '/System/Library/Fonts/Supplemental/Courier New.ttf',
                    '/System/Library/Fonts/Supplemental/Times New Roman.ttf',
                    SOCIAL_IMAGE_PLUGIN_DIR . 'assets/fonts/OpenSans-Regular.ttf' // Try our font as last resort
                ];
                
                $font_path = null;
                foreach ($system_fonts as $test_font) {
                    error_log('Testing font: ' . $test_font);
                    if (file_exists($test_font)) {
                        error_log('Font file exists: ' . $test_font . ', size: ' . filesize($test_font) . ' bytes');
                        
                        // Verify the font file is valid
                        $test_bbox = @imagettfbbox(12, 0, $test_font, 'Test');
                        if ($test_bbox !== false) {
                            error_log('Font validated successfully: ' . $test_font);
                            $font_path = $test_font;
                            break;
                        } else {
                            error_log('Font exists but failed validation: ' . $test_font);
                        }
                    } else {
                        error_log('Font file not found: ' . $test_font);
                    }
                }
                
                if ($font_path) {
                    error_log('Using font: ' . $font_path);
                } else {
                    error_log('No valid fonts found, will use built-in font');
                }

                // Wrap text to fit within the element width
                $lines = $this->wrap_text($element['text'], $font_path, $font_size, $element_width);

                // Calculate total text height
                $line_height = $font_size * 1.5;
                $total_height = count($lines) * $line_height;

                // Adjust Y position to center text vertically
                $start_y = $element_y - ($total_height / 2);

                // Draw each line of text
                foreach ($lines as $i => $line) {
                    $line_y = $start_y + ($i * $line_height);

                    // Calculate X position based on alignment
                    $bbox = imagettfbbox($font_size, 0, $font_path, $line);
                    if ($bbox === false) {
                        error_log('Failed to get text bounding box');
                        continue;
                    }
                    $line_width = $bbox[2] - $bbox[0];

                    switch ($element['alignment']) {
                        case 'left':
                            $line_x = $element_x - ($element_width / 2);
                            break;
                        case 'right':
                            $line_x = $element_x + ($element_width / 2) - $line_width;
                            break;
                        case 'center':
                        default:
                            $line_x = $element_x - ($line_width / 2);
                            break;
                    }

                    // Draw the text
                    if ($font_path) {
                        $result = imagettftext($image, $font_size, 0, $line_x, $line_y, $text_color, $font_path, $line);
                        if ($result === false) {
                            error_log('Failed to draw text with TrueType font');
                        }
                    } else {
                        // Fallback to built-in font
                        $result = imagestring($image, 5, $line_x, $line_y - $font_size, $line, $text_color);
                        if ($result === false) {
                            error_log('Failed to draw text with built-in font');
                        }
                    }
                }
                error_log('Added text element');
            }

            // Save the image to a file
            $upload_dir = wp_upload_dir();
            error_log('Upload directory: ' . print_r($upload_dir, true));

            $filename = 'social-image-' . ($is_preview ? 'preview-' : '') . time() . '.png';
            $file_path = $upload_dir['path'] . '/' . $filename;
            $file_url = $upload_dir['url'] . '/' . $filename;

            error_log('Saving image to: ' . $file_path);

            // Check if directory is writable
            if (!is_writable($upload_dir['path'])) {
                error_log('Upload directory is not writable: ' . $upload_dir['path']);
                return false;
            }

            $result = imagepng($image, $file_path);
            if ($result === false) {
                error_log('Failed to save PNG image');
                return false;
            }

            imagedestroy($image);
            error_log('Image saved successfully: ' . $file_url);

            return $file_url;
        } catch (Exception $e) {
            error_log('Error in generate_image_gd: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate an image using the Imagick library
     */
    private function generate_image_imagick($width, $height, $background_color, $background_image, $text_elements, $image_elements, $is_preview) {
        // This would be implemented in a real plugin
        // For now, we'll just return false to fall back to GD
        return false;
    }

    /**
     * Load an image from a URL
     */
    private function load_image_from_url($url) {
        try {
            error_log('Loading image from URL: ' . $url);

            // Get the file path from URL if it's a local file
            $upload_dir = wp_upload_dir();
            $base_url = $upload_dir['baseurl'];
            $base_dir = $upload_dir['basedir'];

            if (strpos($url, $base_url) === 0) {
                $file_path = str_replace($base_url, $base_dir, $url);
                error_log('Converted URL to local path: ' . $file_path);

                if (file_exists($file_path)) {
                    error_log('Local file exists, loading from path');
                    return $this->load_image_from_path($file_path);
                } else {
                    error_log('Local file does not exist: ' . $file_path);
                }
            }

            // If not a local file or local file doesn't exist, try to load from URL
            error_log('Downloading file from URL');
            $temp_file = download_url($url);
            if (is_wp_error($temp_file)) {
                error_log('Error downloading file: ' . $temp_file->get_error_message());
                return false;
            }

            error_log('File downloaded to: ' . $temp_file);
            $image = $this->load_image_from_path($temp_file);
            @unlink($temp_file); // Delete the temp file

            if ($image) {
                error_log('Image loaded successfully');
            } else {
                error_log('Failed to load image from path');
            }

            return $image;
        } catch (Exception $e) {
            error_log('Error in load_image_from_url: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Load an image from a file path
     */
    private function load_image_from_path($file_path) {
        try {
            error_log('Loading image from path: ' . $file_path);

            if (!file_exists($file_path)) {
                error_log('File does not exist: ' . $file_path);
                return false;
            }

            $image_info = getimagesize($file_path);
            if ($image_info === false) {
                error_log('Failed to get image size: ' . $file_path);
                return false;
            }

            error_log('Image type: ' . $image_info[2] . ', dimensions: ' . $image_info[0] . 'x' . $image_info[1]);

            switch ($image_info[2]) {
                case IMAGETYPE_JPEG:
                    error_log('Loading JPEG image');
                    $image = imagecreatefromjpeg($file_path);
                    break;
                case IMAGETYPE_PNG:
                    error_log('Loading PNG image');
                    $image = imagecreatefrompng($file_path);
                    break;
                case IMAGETYPE_GIF:
                    error_log('Loading GIF image');
                    $image = imagecreatefromgif($file_path);
                    break;
                default:
                    error_log('Unsupported image type: ' . $image_info[2]);
                    return false;
            }

            if ($image === false) {
                error_log('Failed to create image from file');
                return false;
            }

            error_log('Image loaded successfully');
            return $image;
        } catch (Exception $e) {
            error_log('Error in load_image_from_path: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Wrap text to fit within a given width
     */
    private function wrap_text($text, $font, $font_size, $max_width) {
        try {
            error_log('Wrapping text: "' . substr($text, 0, 30) . '..." with max width: ' . $max_width);

            $words = explode(' ', $text);
            $lines = array();
            $current_line = '';

            foreach ($words as $word) {
                // Check if adding this word would exceed the max width
                $test_line = $current_line . ' ' . $word;
                $test_line = ltrim($test_line); // Remove leading space

                if ($font) {
                    $bbox = imagettfbbox($font_size, 0, $font, $test_line);
                    if ($bbox === false) {
                        error_log('Failed to get text bounding box for: "' . $test_line . '"');
                        // Fall back to approximate width
                        $line_width = strlen($test_line) * ($font_size * 0.6);
                    } else {
                        $line_width = $bbox[2] - $bbox[0];
                    }
                } else {
                    // Approximate width for built-in font
                    $line_width = strlen($test_line) * ($font_size * 0.6);
                }

                error_log('Testing line: "' . $test_line . '", width: ' . $line_width . ' vs max: ' . $max_width);

                if ($line_width <= $max_width || empty($current_line)) {
                    $current_line = $test_line;
                } else {
                    $lines[] = $current_line;
                    $current_line = $word;
                }
            }

            // Add the last line
            if (!empty($current_line)) {
                $lines[] = $current_line;
            }

            error_log('Text wrapped into ' . count($lines) . ' lines');
            return $lines;
        } catch (Exception $e) {
            error_log('Error in wrap_text: ' . $e->getMessage());
            // Return the original text as a single line
            return array($text);
        }
    }
}
