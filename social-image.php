<?php
/**
 * Plugin Name: Social Image
 * Plugin URI: https://example.com/social-image
 * Description: Generate Pinterest pin images using templates directly from the WordPress editor.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: social-image
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SOCIAL_IMAGE_VERSION', '1.0.0');
define('SOCIAL_IMAGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SOCIAL_IMAGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SOCIAL_IMAGE_GITHUB_USERNAME', 'your-github-username'); // Change this to your GitHub username
define('SOCIAL_IMAGE_GITHUB_REPO', 'social-image'); // Change this to your GitHub repo name

/**
 * The code that runs during plugin activation.
 */
function activate_social_image() {
    // Create necessary database tables or options
    // Set up default templates
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_social_image() {
    // Clean up if necessary
}

register_activation_hook(__FILE__, 'activate_social_image');
register_deactivation_hook(__FILE__, 'deactivate_social_image');

/**
 * Load the required dependencies for this plugin.
 */
function social_image_load_dependencies() {
    // Admin pages
    if (is_admin()) {
        require_once SOCIAL_IMAGE_PLUGIN_DIR . 'admin/admin-page.php';
        require_once SOCIAL_IMAGE_PLUGIN_DIR . 'admin/template-editor.php';

        // Plugin updater
        require_once SOCIAL_IMAGE_PLUGIN_DIR . 'includes/plugin-updater.php';
    }

    // Core functionality
    require_once SOCIAL_IMAGE_PLUGIN_DIR . 'includes/image-generator.php';
    require_once SOCIAL_IMAGE_PLUGIN_DIR . 'includes/media-selector.php';
}

/**
 * Initialize the plugin.
 */
function social_image_init() {
    social_image_load_dependencies();

    // Initialize admin pages
    if (is_admin()) {
        $admin_page = new Social_Image_Admin_Page();
        $template_editor = new Social_Image_Template_Editor();

        // Initialize the plugin updater
        if (class_exists('Social_Image_Plugin_Updater')) {
            // You can add a GitHub personal access token here if needed for private repos
            $github_token = ''; // Leave empty for public repos

            new Social_Image_Plugin_Updater(
                __FILE__,
                SOCIAL_IMAGE_GITHUB_USERNAME,
                SOCIAL_IMAGE_GITHUB_REPO,
                $github_token
            );
        }
    }

    // Initialize editor integration
    add_action('enqueue_block_editor_assets', 'social_image_enqueue_editor_assets');
}
add_action('plugins_loaded', 'social_image_init');

/**
 * Enqueue assets for the block editor.
 */
function social_image_enqueue_editor_assets() {
    wp_enqueue_script(
        'social-image-editor',
        SOCIAL_IMAGE_PLUGIN_URL . 'assets/js/editor.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        SOCIAL_IMAGE_VERSION,
        true
    );

    wp_enqueue_style(
        'social-image-editor-css',
        SOCIAL_IMAGE_PLUGIN_URL . 'assets/css/editor.css',
        array(),
        SOCIAL_IMAGE_VERSION
    );
}
