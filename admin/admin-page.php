<?php
/**
 * Admin Page for Social Image Plugin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle the admin settings page
 */
class Social_Image_Admin_Page {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add options page to the admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Social Image', 'social-image'),
            __('Social Image', 'social-image'),
            'manage_options',
            'social-image',
            array($this, 'display_admin_page'),
            'dashicons-format-image',
            30
        );

        add_submenu_page(
            'social-image',
            __('Templates', 'social-image'),
            __('Templates', 'social-image'),
            'manage_options',
            'social-image-templates',
            array($this, 'display_templates_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'social_image_settings',
            'social_image_options',
            array($this, 'validate_options')
        );

        add_settings_section(
            'social_image_general_section',
            __('General Settings', 'social-image'),
            array($this, 'general_section_callback'),
            'social-image'
        );

        add_settings_field(
            'default_template',
            __('Default Template', 'social-image'),
            array($this, 'default_template_callback'),
            'social-image',
            'social_image_general_section'
        );
    }

    /**
     * Validate options before saving
     */
    public function validate_options($input) {
        // Validation logic here
        return $input;
    }

    /**
     * General section description
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for the Social Image plugin.', 'social-image') . '</p>';
    }

    /**
     * Default template field callback
     */
    public function default_template_callback() {
        $options = get_option('social_image_options');
        $default_template = isset($options['default_template']) ? $options['default_template'] : '';

        // Get available templates
        $templates = $this->get_available_templates();

        echo '<select name="social_image_options[default_template]">';
        foreach ($templates as $template_id => $template_name) {
            echo '<option value="' . esc_attr($template_id) . '" ' . selected($default_template, $template_id, false) . '>' . esc_html($template_name) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Get available templates
     */
    private function get_available_templates() {
        // This would normally query the database or file system for templates
        return array(
            'default' => __('Default Pinterest Template', 'social-image'),
            'tall' => __('Tall Pinterest Template', 'social-image'),
            'wide' => __('Wide Pinterest Template', 'social-image'),
        );
    }

    /**
     * Display the admin page
     */
    public function display_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('social_image_settings');
                do_settings_sections('social-image');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display the templates page
     */
    public function display_templates_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Social Image Templates', 'social-image'); ?></h1>
            <p><?php _e('Manage your Pinterest image templates here.', 'social-image'); ?></p>

            <div class="social-image-templates-list">
                <?php $this->display_template_list(); ?>
            </div>

            <div class="social-image-add-template">
                <h2><?php _e('Add New Template', 'social-image'); ?></h2>
                <?php
                // Include the template editor class if not already included
                if (!class_exists('Social_Image_Template_Editor')) {
                    require_once SOCIAL_IMAGE_PLUGIN_DIR . 'admin/template-editor.php';
                }

                // Create an instance of the template editor
                $template_editor = new Social_Image_Template_Editor();

                // Render the template editor interface
                $template_editor->render_template_editor();
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display the list of templates
     */
    private function display_template_list() {
        $templates = $this->get_available_templates();

        if (empty($templates)) {
            echo '<p>' . __('No templates found.', 'social-image') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Template Name', 'social-image') . '</th>';
        echo '<th>' . __('Preview', 'social-image') . '</th>';
        echo '<th>' . __('Actions', 'social-image') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($templates as $template_id => $template_name) {
            echo '<tr>';
            echo '<td>' . esc_html($template_name) . '</td>';
            echo '<td><img src="' . esc_url(SOCIAL_IMAGE_PLUGIN_URL . 'assets/templates/' . $template_id . '-preview.jpg') . '" width="100" height="auto" alt="' . esc_attr($template_name) . '"></td>';
            echo '<td>';
            echo '<a href="?page=social-image-templates&action=edit&template=' . esc_attr($template_id) . '" class="button">' . __('Edit', 'social-image') . '</a> ';
            echo '<a href="?page=social-image-templates&action=delete&template=' . esc_attr($template_id) . '" class="button" onclick="return confirm(\'' . __('Are you sure you want to delete this template?', 'social-image') . '\')">' . __('Delete', 'social-image') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
