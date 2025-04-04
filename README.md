# Social Image - WordPress Pinterest Image Generator

A WordPress plugin that allows you to generate Pinterest pin images using customizable templates directly from the WordPress editor.

## Features

- Create and manage Pinterest image templates
- Customize text and image placeholders in templates
- Select images from your post to use in Pinterest pins
- Generate Pinterest-optimized images with a single click
- Easily customize text content for each pin
- Automatic updates via GitHub

## Installation

1. Upload the `social-image` folder to the `/wp-content/plugins/` directory
2. Configure GitHub updates by editing the plugin file (see INSTALL.md for details)
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to the 'Social Image' menu in your WordPress admin to manage templates

## Usage

### Creating Templates

1. Go to Social Image > Templates in your WordPress admin
2. In the "Add New Template" section at the bottom of the page, enter template details
3. Set the canvas size, background color, and background image
4. Add text elements and image placeholders using the respective buttons
5. Click "Save Template" to save your template

### Generating Pinterest Images

1. Edit a post or page
2. In the sidebar, find the "Pinterest Image" meta box
3. Select a template from the dropdown
4. Click "Customize Pinterest Image"
5. Replace placeholder images with images from your post
6. Customize text elements as needed
7. Click "Generate Pinterest Image"
8. Save your post to store the generated image

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- GD or Imagick PHP extension

## Development

This plugin is built with:

- PHP for the WordPress plugin backend
- JavaScript for the editor integration
- GD or Imagick PHP extension for image manipulation
- WordPress Media Library API for image selection

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- OpenSans font by Steve Matteson
- Icons by WordPress Dashicons

## Support

For support, please create an issue in the GitHub repository or contact the plugin author.

## GitHub Updates

This plugin supports automatic updates from GitHub. To release a new version:

1. Update the version number in `social-image.php`
2. Create a new release on GitHub with a tag name that matches the version (e.g., `v1.0.1`)
3. Include a detailed changelog in the release description
4. Attach a ZIP file of the plugin to the release (optional but recommended)

When a new release is published on GitHub, WordPress will detect the update and prompt users to update the plugin.

## Changelog

### 1.0.0
- Initial release
