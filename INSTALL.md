# Social Image - WordPress Pinterest Image Generator

## Installation Instructions

### Prerequisites
- WordPress 5.0 or higher
- PHP 7.0 or higher
- GD or Imagick PHP extension

### Installation Steps

1. **Download the plugin files**
   - Download all the files from this directory

2. **Create a plugin directory in WordPress**
   - Navigate to your WordPress installation
   - Go to `wp-content/plugins/`
   - Create a new directory called `social-image`

3. **Upload the plugin files**
   - Upload all the files to the `social-image` directory you created

4. **Configure GitHub Updates**
   - Open the `social-image.php` file
   - Update the GitHub username and repository name:
     ```php
     define('SOCIAL_IMAGE_GITHUB_USERNAME', 'your-github-username'); // Change this to your GitHub username
     define('SOCIAL_IMAGE_GITHUB_REPO', 'social-image'); // Change this to your GitHub repo name
     ```
   - If your repository is private, add a GitHub personal access token in the `social_image_init()` function

5. **Activate the plugin**
   - Log in to your WordPress admin dashboard
   - Go to Plugins > Installed Plugins
   - Find "Social Image" in the list and click "Activate"

6. **Complete the font installation**
   - Download the OpenSans-Regular.ttf font file
   - Replace the placeholder file in `assets/fonts/OpenSans-Regular.ttf` with the actual font file

7. **Create template preview images**
   - Create preview images for the default templates
   - Save them as JPG files in the `assets/templates/` directory with the following names:
     - `default-preview.jpg`
     - `tall-preview.jpg`
     - `wide-preview.jpg`

## Testing the Plugin

1. **Create a template**
   - Go to Social Image > Templates in your WordPress admin
   - In the "Add New Template" section at the bottom of the page, enter template details
   - Add text elements and image placeholders
   - Click "Save Template"

2. **Generate a Pinterest image**
   - Create or edit a post
   - In the sidebar, find the "Pinterest Image" meta box
   - Select your template from the dropdown
   - Click "Customize Pinterest Image"
   - Replace placeholder images with images from your post
   - Customize text elements
   - Click "Generate Pinterest Image"
   - Save your post

## Troubleshooting

- **Image generation fails**: Make sure the GD or Imagick PHP extension is enabled on your server
- **Font doesn't appear**: Ensure you've replaced the placeholder font file with the actual OpenSans-Regular.ttf file
- **Template previews don't show**: Check that you've created the preview images in the correct location

## Development Notes

If you want to modify the plugin, here's a quick overview of the file structure:

- `social-image.php`: Main plugin file with WordPress header
- `admin/`: Admin-related files
  - `admin-page.php`: Main admin settings page
  - `template-editor.php`: Template editor functionality
- `includes/`: Core functionality
  - `image-generator.php`: Image generation class
  - `media-selector.php`: Media selection class
- `assets/`: CSS, JS, and templates
  - `css/`: Stylesheets
  - `js/`: JavaScript files
  - `templates/`: Default template previews
  - `fonts/`: Font files

For more detailed information, see the README.md file.
