<?php
/**
 * Plugin Updater for Social Image Plugin
 * 
 * Enables automatic updates from a GitHub repository.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle GitHub-based plugin updates
 */
class Social_Image_Plugin_Updater {
    
    private $slug;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_api_result;
    private $access_token;
    
    /**
     * Initialize the class and set its properties.
     * 
     * @param string $plugin_file Path to the plugin file
     * @param string $github_username GitHub username
     * @param string $github_repo GitHub repo name
     * @param string $access_token GitHub access token (optional)
     */
    public function __construct($plugin_file, $github_username, $github_repo, $access_token = '') {
        $this->plugin_file = $plugin_file;
        $this->username = $github_username;
        $this->repo = $github_repo;
        $this->access_token = $access_token;
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'set_transient'));
        add_filter('plugins_api', array($this, 'set_plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3);
    }
    
    /**
     * Get information regarding our plugin from WordPress
     */
    private function init_plugin_data() {
        $this->slug = plugin_basename($this->plugin_file);
        $this->plugin_data = get_plugin_data($this->plugin_file);
    }
    
    /**
     * Get information regarding our plugin from GitHub
     * 
     * @return array|false
     */
    private function get_repository_info() {
        if (!empty($this->github_api_result)) {
            return $this->github_api_result;
        }
        
        // Query the GitHub API
        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
        
        // Include access token if provided
        if (!empty($this->access_token)) {
            $url = add_query_arg(array('access_token' => $this->access_token), $url);
        }
        
        // Get the results
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            )
        ));
        
        // Check for errors
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body);
        
        if (!is_object($result)) {
            return false;
        }
        
        // Cache the result
        $this->github_api_result = $result;
        
        return $result;
    }
    
    /**
     * Push in plugin version information to get the update notification
     * 
     * @param object $transient
     * @return object
     */
    public function set_transient($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get plugin & GitHub release information
        $this->init_plugin_data();
        $release_info = $this->get_repository_info();
        
        // Check if we have valid release info
        if (false === $release_info) {
            return $transient;
        }
        
        // Check if a new version is available
        $current_version = $this->plugin_data['Version'];
        $latest_version = ltrim($release_info->tag_name, 'v');
        
        if (version_compare($latest_version, $current_version, '>')) {
            $download_url = $this->get_download_url($release_info);
            
            if (!empty($download_url)) {
                $obj = new stdClass();
                $obj->slug = $this->slug;
                $obj->new_version = $latest_version;
                $obj->url = $this->plugin_data['PluginURI'];
                $obj->package = $download_url;
                $obj->tested = isset($release_info->tested) ? $release_info->tested : '';
                $obj->requires = isset($release_info->requires) ? $release_info->requires : '';
                $obj->requires_php = isset($release_info->requires_php) ? $release_info->requires_php : '';
                
                $transient->response[$this->slug] = $obj;
            }
        }
        
        return $transient;
    }
    
    /**
     * Get the download URL for the release
     * 
     * @param object $release_info
     * @return string
     */
    private function get_download_url($release_info) {
        if (empty($release_info->assets) || !is_array($release_info->assets)) {
            // If no assets, use the zipball_url
            return $release_info->zipball_url;
        }
        
        // Look for a .zip asset
        foreach ($release_info->assets as $asset) {
            if (isset($asset->browser_download_url) && preg_match('/\.zip$/i', $asset->browser_download_url)) {
                return $asset->browser_download_url;
            }
        }
        
        // If no .zip asset found, use the zipball_url
        return $release_info->zipball_url;
    }
    
    /**
     * Push in plugin version information to display in the details lightbox
     * 
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return object|bool
     */
    public function set_plugin_info($result, $action, $args) {
        if ('plugin_information' !== $action) {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }
        
        $this->init_plugin_data();
        $release_info = $this->get_repository_info();
        
        if (false === $release_info) {
            return $result;
        }
        
        $plugin_info = new stdClass();
        $plugin_info->name = $this->plugin_data['Name'];
        $plugin_info->slug = $this->slug;
        $plugin_info->version = ltrim($release_info->tag_name, 'v');
        $plugin_info->author = $this->plugin_data['AuthorName'];
        $plugin_info->author_profile = $this->plugin_data['AuthorURI'];
        $plugin_info->requires = isset($release_info->requires) ? $release_info->requires : '';
        $plugin_info->tested = isset($release_info->tested) ? $release_info->tested : '';
        $plugin_info->requires_php = isset($release_info->requires_php) ? $release_info->requires_php : '';
        $plugin_info->downloaded = 0;
        $plugin_info->last_updated = $release_info->published_at;
        $plugin_info->sections = array(
            'description' => $this->plugin_data['Description'],
            'changelog' => $this->get_changelog($release_info)
        );
        $plugin_info->download_link = $this->get_download_url($release_info);
        
        return $plugin_info;
    }
    
    /**
     * Get the changelog from the release body
     * 
     * @param object $release_info
     * @return string
     */
    private function get_changelog($release_info) {
        if (empty($release_info->body)) {
            return 'No changelog provided.';
        }
        
        // Convert GitHub Markdown to HTML
        $changelog = $release_info->body;
        $changelog = nl2br($changelog);
        
        return $changelog;
    }
    
    /**
     * Perform additional actions to successfully install our plugin
     * 
     * @param bool $true
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function post_install($true, $hook_extra, $result) {
        // Get plugin information
        $this->init_plugin_data();
        
        // Remember if our plugin was previously activated
        $was_activated = is_plugin_active($this->slug);
        
        // Since we are hosted in GitHub, our plugin folder would have a dirname of
        // reponame-tagname. We want to rename it to our original plugin folder.
        global $wp_filesystem;
        
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->slug);
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;
        
        // Re-activate plugin if needed
        if ($was_activated) {
            $activate = activate_plugin($this->slug);
        }
        
        return $result;
    }
}
