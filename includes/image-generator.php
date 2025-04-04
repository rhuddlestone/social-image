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
        // Check nonce for security
        check_ajax_referer('social_image_nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'social-image')));
        }
        
        // Get template data
        $template_data = isset($_POST['template_data']) ? json_decode(stripslashes($_POST['template_data']), true) : array();
        
        if (empty($template_data)) {
            wp_send_json_error(array('message' => __('No template data provided.', 'social-image')));
        }
        
        // Generate the preview image
        $image_url = $this->generate_image($template_data, true);
        
        if ($image_url) {
            wp_send_json_success(array(
                'image_url' => $image_url
            ));
        } else {
            wp_send_json_error(array('message' => __('Error generating preview image.', 'social-image')));
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
        // Check if GD or Imagick is available
        if (!function_exists('imagecreatetruecolor') && !class_exists('Imagick')) {
            return false;
        }
        
        // Extract template data
        $width = isset($template_data['width']) ? intval($template_data['width']) : 1000;
        $height = isset($template_data['height']) ? intval($template_data['height']) : 1500;
        $background_color = isset($template_data['background_color']) ? $template_data['background_color'] : '#ffffff';
        $background_image = isset($template_data['background_image']) ? $template_data['background_image'] : '';
        $text_elements = isset($template_data['text_elements']) ? $template_data['text_elements'] : array();
        $image_elements = isset($template_data['image_elements']) ? $template_data['image_elements'] : array();
        
        // Use GD library for image generation
        if (function_exists('imagecreatetruecolor')) {
            return $this->generate_image_gd($width, $height, $background_color, $background_image, $text_elements, $image_elements, $is_preview);
        }
        // Use Imagick if available
        else if (class_exists('Imagick')) {
            return $this->generate_image_imagick($width, $height, $background_color, $background_image, $text_elements, $image_elements, $is_preview);
        }
        
        return false;
    }
    
    /**
     * Generate an image using the GD library
     */
    private function generate_image_gd($width, $height, $background_color, $background_image, $text_elements, $image_elements, $is_preview) {
        // Create a new image
        $image = imagecreatetruecolor($width, $height);
        
        // Set background color
        $r = hexdec(substr($background_color, 1, 2));
        $g = hexdec(substr($background_color, 3, 2));
        $b = hexdec(substr($background_color, 5, 2));
        $bg_color = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $bg_color);
        
        // Add background image if provided
        if (!empty($background_image)) {
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
            }
        }
        
        // Add image elements
        foreach ($image_elements as $element) {
            if (empty($element['placeholder_image'])) {
                continue;
            }
            
            $img = $this->load_image_from_url($element['placeholder_image']);
            if (!$img) {
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
        }
        
        // Add text elements
        foreach ($text_elements as $element) {
            if (empty($element['text'])) {
                continue;
            }
            
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
            
            // Use a default font (this should be improved in a real plugin)
            $font_path = SOCIAL_IMAGE_PLUGIN_DIR . 'assets/fonts/OpenSans-Regular.ttf';
            if (!file_exists($font_path)) {
                $font_path = null; // Use built-in font if custom font is not available
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
                    imagettftext($image, $font_size, 0, $line_x, $line_y, $text_color, $font_path, $line);
                } else {
                    // Fallback to built-in font
                    imagestring($image, 5, $line_x, $line_y - $font_size, $line, $text_color);
                }
            }
        }
        
        // Save the image to a file
        $upload_dir = wp_upload_dir();
        $filename = 'social-image-' . ($is_preview ? 'preview-' : '') . time() . '.png';
        $file_path = $upload_dir['path'] . '/' . $filename;
        $file_url = $upload_dir['url'] . '/' . $filename;
        
        imagepng($image, $file_path);
        imagedestroy($image);
        
        return $file_url;
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
        // Get the file path from URL if it's a local file
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_dir = $upload_dir['basedir'];
        
        if (strpos($url, $base_url) === 0) {
            $file_path = str_replace($base_url, $base_dir, $url);
            if (file_exists($file_path)) {
                return $this->load_image_from_path($file_path);
            }
        }
        
        // If not a local file or local file doesn't exist, try to load from URL
        $temp_file = download_url($url);
        if (is_wp_error($temp_file)) {
            return false;
        }
        
        $image = $this->load_image_from_path($temp_file);
        @unlink($temp_file); // Delete the temp file
        
        return $image;
    }
    
    /**
     * Load an image from a file path
     */
    private function load_image_from_path($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $image_info = getimagesize($file_path);
        if ($image_info === false) {
            return false;
        }
        
        switch ($image_info[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($file_path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($file_path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($file_path);
            default:
                return false;
        }
    }
    
    /**
     * Wrap text to fit within a given width
     */
    private function wrap_text($text, $font, $font_size, $max_width) {
        $words = explode(' ', $text);
        $lines = array();
        $current_line = '';
        
        foreach ($words as $word) {
            // Check if adding this word would exceed the max width
            $test_line = $current_line . ' ' . $word;
            $test_line = ltrim($test_line); // Remove leading space
            
            if ($font) {
                $bbox = imagettfbbox($font_size, 0, $font, $test_line);
                $line_width = $bbox[2] - $bbox[0];
            } else {
                // Approximate width for built-in font
                $line_width = strlen($test_line) * ($font_size * 0.6);
            }
            
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
        
        return $lines;
    }
}
