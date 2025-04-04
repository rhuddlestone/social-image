<?php
/**
 * Template Editor for Social Image Plugin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle the template editor functionality
 */
class Social_Image_Template_Editor {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Add AJAX handlers for template operations
        add_action('wp_ajax_social_image_get_template', array($this, 'ajax_get_template'));
        add_action('wp_ajax_social_image_save_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_social_image_delete_template', array($this, 'ajax_delete_template'));

        // Add scripts and styles for the template editor
        add_action('admin_enqueue_scripts', array($this, 'enqueue_editor_assets'));
    }

    /**
     * Enqueue assets for the template editor
     */
    public function enqueue_editor_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'social-image') === false) {
            return;
        }

        wp_enqueue_media(); // For WordPress media uploader

        wp_enqueue_script(
            'social-image-template-editor',
            SOCIAL_IMAGE_PLUGIN_URL . 'assets/js/template-editor.js',
            array('jquery', 'wp-color-picker'),
            SOCIAL_IMAGE_VERSION,
            true
        );

        wp_enqueue_style(
            'wp-color-picker'
        );

        wp_enqueue_style(
            'social-image-template-editor-css',
            SOCIAL_IMAGE_PLUGIN_URL . 'assets/css/template-editor.css',
            array(),
            SOCIAL_IMAGE_VERSION
        );

        // Pass data to JavaScript
        wp_localize_script(
            'social-image-template-editor',
            'socialImageData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('social_image_nonce'),
                'i18n' => array(
                    'saveSuccess' => __('Template saved successfully!', 'social-image'),
                    'saveError' => __('Error saving template.', 'social-image'),
                    'deleteSuccess' => __('Template deleted successfully!', 'social-image'),
                    'deleteError' => __('Error deleting template.', 'social-image'),
                )
            )
        );
    }

    /**
     * AJAX handler for getting template data
     */
    public function ajax_get_template() {
        // Check nonce for security
        check_ajax_referer('social_image_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'social-image')));
        }

        // Get template ID
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';

        // Get template data
        $template = $this->get_template($template_id);

        if ($template) {
            wp_send_json_success(array(
                'template' => $template
            ));
        } else {
            wp_send_json_error(array('message' => __('Template not found.', 'social-image')));
        }
    }

    /**
     * AJAX handler for saving a template
     */
    public function ajax_save_template() {
        // Check nonce for security
        check_ajax_referer('social_image_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'social-image')));
        }

        // Get and sanitize template data
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        $template_name = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';
        $template_data = isset($_POST['template_data']) ? json_decode(stripslashes($_POST['template_data']), true) : array();

        // Validate required fields
        if (empty($template_name) || empty($template_data)) {
            wp_send_json_error(array('message' => __('Missing required template data.', 'social-image')));
        }

        // Save the template (in a real plugin, this would save to the database)
        $saved = $this->save_template($template_id, $template_name, $template_data);

        if ($saved) {
            wp_send_json_success(array(
                'message' => __('Template saved successfully!', 'social-image'),
                'template_id' => $saved
            ));
        } else {
            wp_send_json_error(array('message' => __('Error saving template.', 'social-image')));
        }
    }

    /**
     * AJAX handler for deleting a template
     */
    public function ajax_delete_template() {
        // Check nonce for security
        check_ajax_referer('social_image_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'social-image')));
        }

        // Get template ID
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';

        if (empty($template_id)) {
            wp_send_json_error(array('message' => __('No template specified.', 'social-image')));
        }

        // Delete the template
        $deleted = $this->delete_template($template_id);

        if ($deleted) {
            wp_send_json_success(array('message' => __('Template deleted successfully!', 'social-image')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting template.', 'social-image')));
        }
    }

    /**
     * Save a template to the database
     */
    private function save_template($template_id, $template_name, $template_data) {
        // In a real plugin, this would save to the database
        // For now, we'll just return a success value

        // If no template_id is provided, generate a new one
        if (empty($template_id)) {
            $template_id = 'template_' . time();
        }

        // Get existing templates
        $templates = get_option('social_image_templates', array());

        // Add or update the template
        $templates[$template_id] = array(
            'name' => $template_name,
            'data' => $template_data
        );

        // Save templates
        update_option('social_image_templates', $templates);

        return $template_id;
    }

    /**
     * Get a template by ID
     */
    private function get_template($template_id) {
        // Get existing templates
        $templates = get_option('social_image_templates', array());

        // Check if template exists
        if (!empty($template_id) && isset($templates[$template_id])) {
            return array(
                'id' => $template_id,
                'name' => $templates[$template_id]['name'],
                'width' => isset($templates[$template_id]['data']['width']) ? $templates[$template_id]['data']['width'] : 1000,
                'height' => isset($templates[$template_id]['data']['height']) ? $templates[$template_id]['data']['height'] : 1500,
                'background_color' => isset($templates[$template_id]['data']['background_color']) ? $templates[$template_id]['data']['background_color'] : '#ffffff',
                'background_image' => isset($templates[$template_id]['data']['background_image']) ? $templates[$template_id]['data']['background_image'] : '',
                'text_elements' => isset($templates[$template_id]['data']['text_elements']) ? $templates[$template_id]['data']['text_elements'] : array(),
                'image_elements' => isset($templates[$template_id]['data']['image_elements']) ? $templates[$template_id]['data']['image_elements'] : array()
            );
        }

        // Return default template if no template ID provided or template not found
        return array(
            'id' => '',
            'name' => __('New Template', 'social-image'),
            'width' => 1000,
            'height' => 1500,
            'background_color' => '#ffffff',
            'background_image' => '',
            'text_elements' => array(
                array(
                    'text' => __('Your Text Here', 'social-image'),
                    'font_size' => 24,
                    'font_color' => '#000000',
                    'position_x' => 50,
                    'position_y' => 50,
                    'width' => 80,
                    'alignment' => 'center'
                )
            ),
            'image_elements' => array(
                array(
                    'placeholder_image' => '',
                    'position_x' => 50,
                    'position_y' => 25,
                    'width' => 80,
                    'height' => 40
                )
            )
        );
    }

    /**
     * Delete a template from the database
     */
    private function delete_template($template_id) {
        // Get existing templates
        $templates = get_option('social_image_templates', array());

        // Check if template exists
        if (!isset($templates[$template_id])) {
            return false;
        }

        // Remove the template
        unset($templates[$template_id]);

        // Save templates
        update_option('social_image_templates', $templates);

        return true;
    }

    /**
     * Render the template editor interface
     */
    public function render_template_editor($template_id = '') {
        // Get template data if editing an existing template
        $template_data = array();
        $template_name = '';

        if (!empty($template_id)) {
            $templates = get_option('social_image_templates', array());
            if (isset($templates[$template_id])) {
                $template_name = $templates[$template_id]['name'];
                $template_data = $templates[$template_id]['data'];
            }
        }

        ?>
        <div class="social-image-template-editor">
            <div class="template-editor-form">
                <div class="form-field">
                    <label for="template_name"><?php _e('Template Name', 'social-image'); ?></label>
                    <input type="text" id="template_name" name="template_name" value="<?php echo esc_attr($template_name); ?>" required>
                </div>

                <div class="form-field">
                    <label><?php _e('Canvas Size', 'social-image'); ?></label>
                    <div class="size-inputs">
                        <input type="number" id="canvas_width" name="canvas_width" value="<?php echo isset($template_data['width']) ? esc_attr($template_data['width']) : '1000'; ?>" min="100" max="2000">
                        <span>×</span>
                        <input type="number" id="canvas_height" name="canvas_height" value="<?php echo isset($template_data['height']) ? esc_attr($template_data['height']) : '1500'; ?>" min="100" max="2000">
                        <span>px</span>
                    </div>
                </div>

                <div class="form-field">
                    <label><?php _e('Background Color', 'social-image'); ?></label>
                    <input type="text" id="background_color" name="background_color" class="color-picker" value="<?php echo isset($template_data['background_color']) ? esc_attr($template_data['background_color']) : '#ffffff'; ?>">
                </div>

                <div class="form-field">
                    <label><?php _e('Background Image', 'social-image'); ?></label>
                    <div class="media-field">
                        <input type="hidden" id="background_image" name="background_image" value="<?php echo isset($template_data['background_image']) ? esc_attr($template_data['background_image']) : ''; ?>">
                        <button type="button" class="button upload-image-button"><?php _e('Select Image', 'social-image'); ?></button>
                        <div class="image-preview">
                            <?php if (isset($template_data['background_image']) && !empty($template_data['background_image'])) : ?>
                                <img src="<?php echo esc_url($template_data['background_image']); ?>" alt="">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <h3><?php _e('Text Elements', 'social-image'); ?></h3>
                <div id="text_elements" class="elements-container">
                    <?php
                    // Render existing text elements
                    if (isset($template_data['text_elements']) && is_array($template_data['text_elements'])) {
                        foreach ($template_data['text_elements'] as $index => $element) {
                            $this->render_text_element($index, $element);
                        }
                    }
                    ?>
                </div>
                <button type="button" class="button add-text-element"><?php _e('Add Text Element', 'social-image'); ?></button>

                <h3><?php _e('Image Placeholders', 'social-image'); ?></h3>
                <div id="image_elements" class="elements-container">
                    <?php
                    // Render existing image elements
                    if (isset($template_data['image_elements']) && is_array($template_data['image_elements'])) {
                        foreach ($template_data['image_elements'] as $index => $element) {
                            $this->render_image_element($index, $element);
                        }
                    }
                    ?>
                </div>
                <button type="button" class="button add-image-element"><?php _e('Add Image Placeholder', 'social-image'); ?></button>

                <div class="form-actions">
                    <input type="hidden" id="template_id" value="<?php echo esc_attr($template_id); ?>">
                    <button type="button" class="button button-primary save-template"><?php _e('Save Template', 'social-image'); ?></button>
                </div>
            </div>

            <div class="template-preview">
                <h3><?php _e('Preview', 'social-image'); ?></h3>
                <div class="preview-container">
                    <!-- Preview will be rendered here by JavaScript -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render a text element form
     */
    private function render_text_element($index, $element = array()) {
        $element = wp_parse_args($element, array(
            'text' => '',
            'font_size' => '24',
            'font_color' => '#000000',
            'position_x' => '50',
            'position_y' => '50',
            'width' => '80',
            'alignment' => 'center'
        ));

        ?>
        <div class="element-item text-element" data-index="<?php echo esc_attr($index); ?>">
            <h4><?php _e('Text Element', 'social-image'); ?> #<?php echo esc_html($index + 1); ?></h4>

            <div class="form-field">
                <label><?php _e('Text', 'social-image'); ?></label>
                <textarea name="text_elements[<?php echo esc_attr($index); ?>][text]"><?php echo esc_textarea($element['text']); ?></textarea>
            </div>

            <div class="form-field">
                <label><?php _e('Font Size', 'social-image'); ?></label>
                <input type="number" name="text_elements[<?php echo esc_attr($index); ?>][font_size]" value="<?php echo esc_attr($element['font_size']); ?>" min="8" max="100">
                <span>px</span>
            </div>

            <div class="form-field">
                <label><?php _e('Font Color', 'social-image'); ?></label>
                <input type="text" name="text_elements[<?php echo esc_attr($index); ?>][font_color]" class="color-picker" value="<?php echo esc_attr($element['font_color']); ?>">
            </div>

            <div class="form-field">
                <label><?php _e('Position X', 'social-image'); ?></label>
                <input type="range" name="text_elements[<?php echo esc_attr($index); ?>][position_x]" value="<?php echo esc_attr($element['position_x']); ?>" min="0" max="100">
                <span class="range-value"><?php echo esc_html($element['position_x']); ?>%</span>
            </div>

            <div class="form-field">
                <label><?php _e('Position Y', 'social-image'); ?></label>
                <input type="range" name="text_elements[<?php echo esc_attr($index); ?>][position_y]" value="<?php echo esc_attr($element['position_y']); ?>" min="0" max="100">
                <span class="range-value"><?php echo esc_html($element['position_y']); ?>%</span>
            </div>

            <div class="form-field">
                <label><?php _e('Width', 'social-image'); ?></label>
                <input type="range" name="text_elements[<?php echo esc_attr($index); ?>][width]" value="<?php echo esc_attr($element['width']); ?>" min="10" max="100">
                <span class="range-value"><?php echo esc_html($element['width']); ?>%</span>
            </div>

            <div class="form-field">
                <label><?php _e('Alignment', 'social-image'); ?></label>
                <select name="text_elements[<?php echo esc_attr($index); ?>][alignment]">
                    <option value="left" <?php selected($element['alignment'], 'left'); ?>><?php _e('Left', 'social-image'); ?></option>
                    <option value="center" <?php selected($element['alignment'], 'center'); ?>><?php _e('Center', 'social-image'); ?></option>
                    <option value="right" <?php selected($element['alignment'], 'right'); ?>><?php _e('Right', 'social-image'); ?></option>
                </select>
            </div>

            <button type="button" class="button remove-element"><?php _e('Remove', 'social-image'); ?></button>
        </div>
        <?php
    }

    /**
     * Render an image element form
     */
    private function render_image_element($index, $element = array()) {
        $element = wp_parse_args($element, array(
            'placeholder_image' => '',
            'position_x' => '50',
            'position_y' => '50',
            'width' => '80',
            'height' => '40'
        ));

        ?>
        <div class="element-item image-element" data-index="<?php echo esc_attr($index); ?>">
            <h4><?php _e('Image Placeholder', 'social-image'); ?> #<?php echo esc_html($index + 1); ?></h4>

            <div class="form-field">
                <label><?php _e('Placeholder Image', 'social-image'); ?></label>
                <div class="media-field">
                    <input type="hidden" name="image_elements[<?php echo esc_attr($index); ?>][placeholder_image]" value="<?php echo esc_attr($element['placeholder_image']); ?>">
                    <button type="button" class="button upload-image-button"><?php _e('Select Image', 'social-image'); ?></button>
                    <div class="image-preview">
                        <?php if (!empty($element['placeholder_image'])) : ?>
                            <img src="<?php echo esc_url($element['placeholder_image']); ?>" alt="">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-field">
                <label><?php _e('Position X', 'social-image'); ?></label>
                <input type="range" name="image_elements[<?php echo esc_attr($index); ?>][position_x]" value="<?php echo esc_attr($element['position_x']); ?>" min="0" max="100">
                <span class="range-value"><?php echo esc_html($element['position_x']); ?>%</span>
            </div>

            <div class="form-field">
                <label><?php _e('Position Y', 'social-image'); ?></label>
                <input type="range" name="image_elements[<?php echo esc_attr($index); ?>][position_y]" value="<?php echo esc_attr($element['position_y']); ?>" min="0" max="100">
                <span class="range-value"><?php echo esc_html($element['position_y']); ?>%</span>
            </div>

            <div class="form-field">
                <label><?php _e('Width', 'social-image'); ?></label>
                <input type="range" name="image_elements[<?php echo esc_attr($index); ?>][width]" value="<?php echo esc_attr($element['width']); ?>" min="10" max="100">
                <span class="range-value"><?php echo esc_html($element['width']); ?>%</span>
            </div>

            <div class="form-field">
                <label><?php _e('Height', 'social-image'); ?></label>
                <input type="range" name="image_elements[<?php echo esc_attr($index); ?>][height]" value="<?php echo esc_attr($element['height']); ?>" min="10" max="100">
                <span class="range-value"><?php echo esc_html($element['height']); ?>%</span>
            </div>

            <button type="button" class="button remove-element"><?php _e('Remove', 'social-image'); ?></button>
        </div>
        <?php
    }
}
