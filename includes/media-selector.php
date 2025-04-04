<?php
/**
 * Media Selector for Social Image Plugin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle media selection in the editor
 */
class Social_Image_Media_Selector {
    
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Add meta box to post editor
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        
        // Save post meta
        add_action('save_post', array($this, 'save_meta'));
        
        // Add AJAX handlers
        add_action('wp_ajax_social_image_get_post_images', array($this, 'ajax_get_post_images'));
    }
    
    /**
     * Add meta box to post editor
     */
    public function add_meta_box() {
        add_meta_box(
            'social_image_meta_box',
            __('Pinterest Image', 'social-image'),
            array($this, 'render_meta_box'),
            array('post', 'page'), // Post types
            'side',
            'default'
        );
    }
    
    /**
     * Render the meta box
     */
    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('social_image_meta_box', 'social_image_meta_box_nonce');
        
        // Get saved Pinterest image if any
        $pinterest_image = get_post_meta($post->ID, '_social_image_pinterest', true);
        
        // Get available templates
        $templates = $this->get_available_templates();
        
        // Get selected template
        $selected_template = get_post_meta($post->ID, '_social_image_template', true);
        if (empty($selected_template) && !empty($templates)) {
            // Default to first template
            $template_ids = array_keys($templates);
            $selected_template = reset($template_ids);
        }
        
        ?>
        <div class="social-image-meta-box">
            <?php if (!empty($pinterest_image)) : ?>
                <div class="pinterest-image-preview">
                    <img src="<?php echo esc_url($pinterest_image); ?>" alt="<?php _e('Pinterest Image', 'social-image'); ?>">
                </div>
            <?php endif; ?>
            
            <p>
                <label for="social_image_template"><?php _e('Template:', 'social-image'); ?></label>
                <select id="social_image_template" name="social_image_template">
                    <?php foreach ($templates as $template_id => $template_name) : ?>
                        <option value="<?php echo esc_attr($template_id); ?>" <?php selected($selected_template, $template_id); ?>>
                            <?php echo esc_html($template_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <button type="button" class="button" id="social_image_customize_button">
                    <?php _e('Customize Pinterest Image', 'social-image'); ?>
                </button>
            </p>
            
            <?php if (!empty($pinterest_image)) : ?>
                <p>
                    <button type="button" class="button" id="social_image_remove_button">
                        <?php _e('Remove Pinterest Image', 'social-image'); ?>
                    </button>
                </p>
            <?php endif; ?>
            
            <input type="hidden" id="social_image_pinterest" name="social_image_pinterest" value="<?php echo esc_attr($pinterest_image); ?>">
        </div>
        <?php
    }
    
    /**
     * Save post meta
     */
    public function save_meta($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['social_image_meta_box_nonce'])) {
            return;
        }
        
        // Verify the nonce
        if (!wp_verify_nonce($_POST['social_image_meta_box_nonce'], 'social_image_meta_box')) {
            return;
        }
        
        // If this is an autosave, we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (isset($_POST['post_type']) && 'page' === $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
        
        // Save the template selection
        if (isset($_POST['social_image_template'])) {
            update_post_meta($post_id, '_social_image_template', sanitize_text_field($_POST['social_image_template']));
        }
        
        // Save the Pinterest image URL
        if (isset($_POST['social_image_pinterest'])) {
            update_post_meta($post_id, '_social_image_pinterest', esc_url_raw($_POST['social_image_pinterest']));
        }
    }
    
    /**
     * AJAX handler to get images from a post
     */
    public function ajax_get_post_images() {
        // Check nonce for security
        check_ajax_referer('social_image_nonce', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'social-image')));
        }
        
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($post_id)) {
            wp_send_json_error(array('message' => __('No post specified.', 'social-image')));
        }
        
        // Get post content
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => __('Post not found.', 'social-image')));
        }
        
        // Get images from post content
        $images = $this->get_images_from_content($post->post_content);
        
        // Get featured image if any
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id) {
            $featured_image = wp_get_attachment_image_src($featured_image_id, 'full');
            if ($featured_image) {
                array_unshift($images, array(
                    'url' => $featured_image[0],
                    'width' => $featured_image[1],
                    'height' => $featured_image[2],
                    'title' => __('Featured Image', 'social-image')
                ));
            }
        }
        
        wp_send_json_success(array(
            'images' => $images
        ));
    }
    
    /**
     * Get images from post content
     */
    private function get_images_from_content($content) {
        $images = array();
        
        // Extract image tags
        preg_match_all('/<img[^>]+>/i', $content, $img_tags);
        
        foreach ($img_tags[0] as $img_tag) {
            // Extract src attribute
            preg_match('/src="([^"]+)"/i', $img_tag, $src);
            
            if (empty($src[1])) {
                continue;
            }
            
            // Extract width and height if available
            preg_match('/width="([^"]+)"/i', $img_tag, $width);
            preg_match('/height="([^"]+)"/i', $img_tag, $height);
            
            // Extract alt text
            preg_match('/alt="([^"]*)"/i', $img_tag, $alt);
            
            $images[] = array(
                'url' => $src[1],
                'width' => isset($width[1]) ? intval($width[1]) : 0,
                'height' => isset($height[1]) ? intval($height[1]) : 0,
                'title' => isset($alt[1]) ? $alt[1] : __('Post Image', 'social-image')
            );
        }
        
        // Extract images from Gutenberg blocks
        if (function_exists('parse_blocks') && strpos($content, '<!-- wp:') !== false) {
            $blocks = parse_blocks($content);
            $this->extract_images_from_blocks($blocks, $images);
        }
        
        return $images;
    }
    
    /**
     * Extract images from Gutenberg blocks
     */
    private function extract_images_from_blocks($blocks, &$images) {
        foreach ($blocks as $block) {
            // Check for image block
            if ($block['blockName'] === 'core/image' && !empty($block['attrs']['id'])) {
                $image_id = $block['attrs']['id'];
                $image = wp_get_attachment_image_src($image_id, 'full');
                
                if ($image) {
                    $images[] = array(
                        'url' => $image[0],
                        'width' => $image[1],
                        'height' => $image[2],
                        'title' => get_the_title($image_id)
                    );
                }
            }
            
            // Check for gallery block
            if ($block['blockName'] === 'core/gallery' && !empty($block['attrs']['ids']) && is_array($block['attrs']['ids'])) {
                foreach ($block['attrs']['ids'] as $image_id) {
                    $image = wp_get_attachment_image_src($image_id, 'full');
                    
                    if ($image) {
                        $images[] = array(
                            'url' => $image[0],
                            'width' => $image[1],
                            'height' => $image[2],
                            'title' => get_the_title($image_id)
                        );
                    }
                }
            }
            
            // Recursively check inner blocks
            if (!empty($block['innerBlocks'])) {
                $this->extract_images_from_blocks($block['innerBlocks'], $images);
            }
        }
    }
    
    /**
     * Get available templates
     */
    private function get_available_templates() {
        // Get templates from options
        $templates = get_option('social_image_templates', array());
        
        // Format for display
        $template_options = array();
        foreach ($templates as $template_id => $template) {
            $template_options[$template_id] = $template['name'];
        }
        
        // Add default templates if no custom templates exist
        if (empty($template_options)) {
            $template_options = array(
                'default' => __('Default Pinterest Template', 'social-image'),
                'tall' => __('Tall Pinterest Template', 'social-image'),
                'wide' => __('Wide Pinterest Template', 'social-image'),
            );
        }
        
        return $template_options;
    }
}
