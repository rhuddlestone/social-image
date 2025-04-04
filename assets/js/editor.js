/**
 * Social Image Editor Integration
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initSocialImageEditor();
    });

    /**
     * Initialize the Social Image editor integration
     */
    function initSocialImageEditor() {
        // Handle customize button click
        $('#social_image_customize_button').on('click', function() {
            openCustomizeModal();
        });

        // Handle remove button click
        $('#social_image_remove_button').on('click', function() {
            removePinterestImage();
        });
    }

    /**
     * Open the customize modal
     */
    function openCustomizeModal() {
        // Get post ID
        const postId = $('#post_ID').val();

        // Get selected template
        const templateId = $('#social_image_template').val();

        // Create modal
        const $modal = $('<div>', {
            id: 'social_image_modal',
            class: 'social-image-modal'
        });

        // Create modal content
        const $modalContent = $('<div>', {
            class: 'social-image-modal-content'
        });

        // Create modal header
        const $modalHeader = $('<div>', {
            class: 'social-image-modal-header'
        });

        $modalHeader.append($('<h2>', {
            text: 'Customize Pinterest Image'
        }));

        $modalHeader.append($('<span>', {
            class: 'social-image-modal-close',
            html: '&times;',
            click: function() {
                $modal.remove();
            }
        }));

        // Create modal body
        const $modalBody = $('<div>', {
            class: 'social-image-modal-body'
        });

        // Add loading indicator
        $modalBody.append($('<div>', {
            class: 'social-image-loading',
            text: 'Loading template...'
        }));

        // Create modal footer
        const $modalFooter = $('<div>', {
            class: 'social-image-modal-footer'
        });

        $modalFooter.append($('<button>', {
            class: 'button button-primary',
            text: 'Generate Pinterest Image',
            click: function() {
                generatePinterestImage(postId);
            }
        }));

        $modalFooter.append($('<button>', {
            class: 'button',
            text: 'Cancel',
            click: function() {
                $modal.remove();
            }
        }));

        // Assemble modal
        $modalContent.append($modalHeader);
        $modalContent.append($modalBody);
        $modalContent.append($modalFooter);
        $modal.append($modalContent);

        // Add modal to page
        $('body').append($modal);

        // Load template data
        loadTemplateData(templateId, postId);
    }

    /**
     * Load template data
     */
    function loadTemplateData(templateId, postId) {
        // Get template data from server
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'social_image_get_template',
                nonce: socialImageData.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success) {
                    // Load post images
                    loadPostImages(postId, response.data.template);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('Error loading template data.');
            }
        });
    }

    /**
     * Load images from the post
     */
    function loadPostImages(postId, templateData) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'social_image_get_post_images',
                nonce: socialImageData.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    renderTemplateEditor(templateData, response.data.images);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('Error loading post images.');
            }
        });
    }

    /**
     * Render the template editor
     */
    function renderTemplateEditor(templateData, postImages) {
        const $modalBody = $('.social-image-modal-body');

        // Clear loading indicator
        $modalBody.empty();

        // Create editor container
        const $editorContainer = $('<div>', {
            class: 'social-image-editor-container'
        });

        // Create editor sidebar
        const $sidebar = $('<div>', {
            class: 'social-image-sidebar'
        });

        // Create preview container
        const $previewContainer = $('<div>', {
            class: 'social-image-preview-container'
        });

        $previewContainer.append($('<div>', {
            class: 'social-image-preview',
            css: {
                width: templateData.width + 'px',
                height: templateData.height + 'px',
                backgroundColor: templateData.background_color
            }
        }));

        // Add text elements editor
        if (templateData.text_elements && templateData.text_elements.length > 0) {
            $sidebar.append($('<h3>', {
                text: 'Text Elements'
            }));

            const $textElementsContainer = $('<div>', {
                class: 'social-image-text-elements'
            });

            templateData.text_elements.forEach(function(element, index) {
                const $elementEditor = createTextElementEditor(element, index);
                $textElementsContainer.append($elementEditor);
            });

            $sidebar.append($textElementsContainer);
        }

        // Add image elements editor
        if (templateData.image_elements && templateData.image_elements.length > 0) {
            $sidebar.append($('<h3>', {
                text: 'Image Elements'
            }));

            const $imageElementsContainer = $('<div>', {
                class: 'social-image-image-elements'
            });

            templateData.image_elements.forEach(function(element, index) {
                const $elementEditor = createImageElementEditor(element, index, postImages);
                $imageElementsContainer.append($elementEditor);
            });

            $sidebar.append($imageElementsContainer);
        }

        // Assemble editor
        $editorContainer.append($sidebar);
        $editorContainer.append($previewContainer);
        $modalBody.append($editorContainer);

        // Generate initial preview
        generatePreview(templateData);
    }

    /**
     * Create a text element editor
     */
    function createTextElementEditor(element, index) {
        const $editor = $('<div>', {
            class: 'social-image-element-editor',
            'data-index': index,
            'data-type': 'text'
        });

        $editor.append($('<h4>', {
            text: 'Text Element #' + (index + 1)
        }));

        // Text input
        $editor.append($('<label>', {
            text: 'Text'
        }));

        $editor.append($('<textarea>', {
            class: 'social-image-text-input',
            'data-property': 'text',
            text: element.text,
            change: function() {
                updateTemplateData();
            }
        }));

        // Font size input
        $editor.append($('<label>', {
            text: 'Font Size'
        }));

        const $fontSizeContainer = $('<div>', {
            class: 'social-image-range-container'
        });

        $fontSizeContainer.append($('<input>', {
            type: 'range',
            class: 'social-image-range-input',
            'data-property': 'font_size',
            min: 8,
            max: 100,
            value: element.font_size,
            change: function() {
                updateTemplateData();
            },
            input: function() {
                $(this).next('.social-image-range-value').text($(this).val() + 'px');
            }
        }));

        $fontSizeContainer.append($('<span>', {
            class: 'social-image-range-value',
            text: element.font_size + 'px'
        }));

        $editor.append($fontSizeContainer);

        // Font color input
        $editor.append($('<label>', {
            text: 'Font Color'
        }));

        $editor.append($('<input>', {
            type: 'color',
            class: 'social-image-color-input',
            'data-property': 'font_color',
            value: element.font_color,
            change: function() {
                updateTemplateData();
            }
        }));

        return $editor;
    }

    /**
     * Create an image element editor
     */
    function createImageElementEditor(element, index, postImages) {
        const $editor = $('<div>', {
            class: 'social-image-element-editor',
            'data-index': index,
            'data-type': 'image'
        });

        $editor.append($('<h4>', {
            text: 'Image Element #' + (index + 1)
        }));

        // Image selector
        $editor.append($('<label>', {
            text: 'Select Image'
        }));

        const $imageSelector = $('<select>', {
            class: 'social-image-image-selector',
            'data-property': 'selected_image',
            change: function() {
                updateTemplateData();
            }
        });

        // Add placeholder option
        $imageSelector.append($('<option>', {
            value: '',
            text: 'Select an image...'
        }));

        // Add post images
        postImages.forEach(function(image, imageIndex) {
            $imageSelector.append($('<option>', {
                value: image.url,
                text: image.title || 'Image ' + (imageIndex + 1)
            }));
        });

        $editor.append($imageSelector);

        // Image preview
        const $imagePreview = $('<div>', {
            class: 'social-image-image-preview'
        });

        if (element.placeholder_image) {
            $imagePreview.append($('<img>', {
                src: element.placeholder_image,
                alt: 'Placeholder'
            }));
        }

        $editor.append($imagePreview);

        return $editor;
    }

    /**
     * Update template data from editor inputs
     */
    function updateTemplateData() {
        // Get current template data
        const templateData = getCurrentTemplateData();

        // Update text elements
        $('.social-image-element-editor[data-type="text"]').each(function() {
            const index = $(this).data('index');

            templateData.text_elements[index].text = $(this).find('.social-image-text-input').val();
            templateData.text_elements[index].font_size = $(this).find('.social-image-range-input[data-property="font_size"]').val();
            templateData.text_elements[index].font_color = $(this).find('.social-image-color-input[data-property="font_color"]').val();
        });

        // Update image elements
        $('.social-image-element-editor[data-type="image"]').each(function() {
            const index = $(this).data('index');
            const selectedImage = $(this).find('.social-image-image-selector').val();

            if (selectedImage) {
                templateData.image_elements[index].selected_image = selectedImage;
            }
        });

        // Generate preview with updated data
        generatePreview(templateData);
    }

    /**
     * Get current template data
     */
    function getCurrentTemplateData() {
        // Get template data from form inputs
        const templateData = {
            width: parseInt($('#canvas_width').val()) || 1000,
            height: parseInt($('#canvas_height').val()) || 1500,
            background_color: $('#background_color').val() || '#ffffff',
            background_image: $('#background_image').val() || '',
            text_elements: [],
            image_elements: []
        };

        // Get text elements
        $('.social-image-element-editor[data-type="text"]').each(function() {
            const index = $(this).data('index');

            templateData.text_elements.push({
                text: $(this).find('.social-image-text-input').val() || '',
                font_size: parseInt($(this).find('.social-image-range-input[data-property="font_size"]').val()) || 24,
                font_color: $(this).find('.social-image-color-input[data-property="font_color"]').val() || '#000000',
                position_x: parseInt($(this).find('.social-image-range-input[data-property="position_x"]').val()) || 50,
                position_y: parseInt($(this).find('.social-image-range-input[data-property="position_y"]').val()) || 50,
                width: parseInt($(this).find('.social-image-range-input[data-property="width"]').val()) || 80,
                alignment: $(this).find('select[data-property="alignment"]').val() || 'center'
            });
        });

        // Get image elements
        $('.social-image-element-editor[data-type="image"]').each(function() {
            const index = $(this).data('index');

            templateData.image_elements.push({
                placeholder_image: $(this).find('input[data-property="placeholder_image"]').val() || '',
                selected_image: $(this).find('.social-image-image-selector').val() || '',
                position_x: parseInt($(this).find('.social-image-range-input[data-property="position_x"]').val()) || 50,
                position_y: parseInt($(this).find('.social-image-range-input[data-property="position_y"]').val()) || 50,
                width: parseInt($(this).find('.social-image-range-input[data-property="width"]').val()) || 80,
                height: parseInt($(this).find('.social-image-range-input[data-property="height"]').val()) || 40
            });
        });

        return templateData;
    }

    /**
     * Generate a preview of the Pinterest image
     */
    function generatePreview(templateData) {
        // Show loading indicator
        const $preview = $('.social-image-preview');
        $preview.html('<div class="social-image-loading">Generating preview...</div>');

        // Send template data to server to generate preview
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'social_image_generate_preview',
                nonce: socialImageData.nonce,
                template_data: JSON.stringify(templateData)
            },
            success: function(response) {
                if (response.success) {
                    // Display preview image
                    $preview.html('');
                    $preview.append($('<img>', {
                        src: response.data.image_url,
                        alt: 'Preview'
                    }));
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('Error generating preview.');
            }
        });
    }

    /**
     * Generate the final Pinterest image
     */
    function generatePinterestImage(postId) {
        // Show loading indicator
        const $modal = $('#social_image_modal');
        $modal.addClass('social-image-loading');

        // Get template data
        const templateData = getCurrentTemplateData();

        // Send template data to server to generate final image
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'social_image_generate_image',
                nonce: socialImageData.nonce,
                post_id: postId,
                template_data: JSON.stringify(templateData)
            },
            success: function(response) {
                $modal.removeClass('social-image-loading');

                if (response.success) {
                    // Update hidden input with image URL
                    $('#social_image_pinterest').val(response.data.image_url);

                    // Update preview in meta box
                    const $metaBoxPreview = $('.pinterest-image-preview');
                    if ($metaBoxPreview.length) {
                        $metaBoxPreview.html('');
                        $metaBoxPreview.append($('<img>', {
                            src: response.data.image_url,
                            alt: 'Pinterest Image'
                        }));
                    } else {
                        $('.social-image-meta-box').prepend($('<div>', {
                            class: 'pinterest-image-preview'
                        }).append($('<img>', {
                            src: response.data.image_url,
                            alt: 'Pinterest Image'
                        })));
                    }

                    // Show remove button if not already visible
                    if ($('#social_image_remove_button').length === 0) {
                        $('.social-image-meta-box').append($('<p>').append($('<button>', {
                            type: 'button',
                            class: 'button',
                            id: 'social_image_remove_button',
                            text: 'Remove Pinterest Image',
                            click: function() {
                                removePinterestImage();
                            }
                        })));
                    }

                    // Close modal
                    $modal.remove();

                    // Show success message
                    showSuccess(response.data.message);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                $modal.removeClass('social-image-loading');
                showError('Error generating Pinterest image.');
            }
        });
    }

    /**
     * Remove the Pinterest image
     */
    function removePinterestImage() {
        // Clear hidden input
        $('#social_image_pinterest').val('');

        // Remove preview
        $('.pinterest-image-preview').remove();

        // Remove remove button
        $('#social_image_remove_button').parent().remove();

        // Show success message
        showSuccess('Pinterest image removed.');
    }

    /**
     * Show an error message
     */
    function showError(message) {
        // This would normally use WordPress admin notices
        alert(message);
    }

    /**
     * Show a success message
     */
    function showSuccess(message) {
        // This would normally use WordPress admin notices
        alert(message);
    }

})(jQuery);
