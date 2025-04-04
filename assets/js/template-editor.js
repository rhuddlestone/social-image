/**
 * Social Image Template Editor
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initTemplateEditor();
    });
    
    /**
     * Initialize the template editor
     */
    function initTemplateEditor() {
        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker({
                change: function() {
                    // Trigger preview update when color changes
                    setTimeout(function() {
                        updatePreview();
                    }, 100);
                }
            });
        }
        
        // Handle range input changes
        $('.social-image-template-editor').on('input', 'input[type="range"]', function() {
            // Update range value display
            $(this).next('.range-value').text($(this).val() + '%');
            
            // Update preview
            updatePreview();
        });
        
        // Handle text input changes
        $('.social-image-template-editor').on('input', 'input[type="text"], textarea', function() {
            updatePreview();
        });
        
        // Handle media uploads
        $('.social-image-template-editor').on('click', '.upload-image-button', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const imagePreview = button.siblings('.image-preview');
            const hiddenInput = button.siblings('input[type="hidden"]');
            
            // Create media frame
            const frame = wp.media({
                title: 'Select or Upload an Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            // When an image is selected in the media frame...
            frame.on('select', function() {
                // Get media attachment details
                const attachment = frame.state().get('selection').first().toJSON();
                
                // Set hidden input value
                hiddenInput.val(attachment.url);
                
                // Update image preview
                imagePreview.html('<img src="' + attachment.url + '" alt="">');
                
                // Update preview
                updatePreview();
            });
            
            // Open the media frame
            frame.open();
        });
        
        // Handle add text element button
        $('.add-text-element').on('click', function() {
            addTextElement();
        });
        
        // Handle add image element button
        $('.add-image-element').on('click', function() {
            addImageElement();
        });
        
        // Handle remove element button
        $('.social-image-template-editor').on('click', '.remove-element', function() {
            $(this).closest('.element-item').remove();
            updatePreview();
        });
        
        // Handle save template button
        $('.save-template').on('click', function() {
            saveTemplate();
        });
        
        // Generate initial preview
        updatePreview();
    }
    
    /**
     * Add a new text element
     */
    function addTextElement() {
        // Get current number of text elements
        const index = $('#text_elements .element-item').length;
        
        // Create new element
        const $element = $('<div>', {
            class: 'element-item text-element',
            'data-index': index
        });
        
        $element.append($('<h4>', {
            text: 'Text Element #' + (index + 1)
        }));
        
        // Text input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Text'
        })).append($('<textarea>', {
            name: 'text_elements[' + index + '][text]',
            rows: 3
        })));
        
        // Font size input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Font Size'
        })).append($('<input>', {
            type: 'number',
            name: 'text_elements[' + index + '][font_size]',
            value: '24',
            min: '8',
            max: '100'
        })).append($('<span>', {
            text: 'px'
        })));
        
        // Font color input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Font Color'
        })).append($('<input>', {
            type: 'text',
            name: 'text_elements[' + index + '][font_color]',
            class: 'color-picker',
            value: '#000000'
        })));
        
        // Position X input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Position X'
        })).append($('<input>', {
            type: 'range',
            name: 'text_elements[' + index + '][position_x]',
            value: '50',
            min: '0',
            max: '100'
        })).append($('<span>', {
            class: 'range-value',
            text: '50%'
        })));
        
        // Position Y input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Position Y'
        })).append($('<input>', {
            type: 'range',
            name: 'text_elements[' + index + '][position_y]',
            value: '50',
            min: '0',
            max: '100'
        })).append($('<span>', {
            class: 'range-value',
            text: '50%'
        })));
        
        // Width input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Width'
        })).append($('<input>', {
            type: 'range',
            name: 'text_elements[' + index + '][width]',
            value: '80',
            min: '10',
            max: '100'
        })).append($('<span>', {
            class: 'range-value',
            text: '80%'
        })));
        
        // Alignment input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Alignment'
        })).append($('<select>', {
            name: 'text_elements[' + index + '][alignment]'
        }).append($('<option>', {
            value: 'left',
            text: 'Left'
        })).append($('<option>', {
            value: 'center',
            text: 'Center',
            selected: true
        })).append($('<option>', {
            value: 'right',
            text: 'Right'
        }))));
        
        // Remove button
        $element.append($('<button>', {
            type: 'button',
            class: 'button remove-element',
            text: 'Remove'
        }));
        
        // Add to container
        $('#text_elements').append($element);
        
        // Initialize color picker
        if ($.fn.wpColorPicker) {
            $element.find('.color-picker').wpColorPicker({
                change: function() {
                    // Trigger preview update when color changes
                    setTimeout(function() {
                        updatePreview();
                    }, 100);
                }
            });
        }
        
        // Update preview
        updatePreview();
    }
    
    /**
     * Add a new image element
     */
    function addImageElement() {
        // Get current number of image elements
        const index = $('#image_elements .element-item').length;
        
        // Create new element
        const $element = $('<div>', {
            class: 'element-item image-element',
            'data-index': index
        });
        
        $element.append($('<h4>', {
            text: 'Image Placeholder #' + (index + 1)
        }));
        
        // Placeholder image input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Placeholder Image'
        })).append($('<div>', {
            class: 'media-field'
        }).append($('<input>', {
            type: 'hidden',
            name: 'image_elements[' + index + '][placeholder_image]',
            value: ''
        })).append($('<button>', {
            type: 'button',
            class: 'button upload-image-button',
            text: 'Select Image'
        })).append($('<div>', {
            class: 'image-preview'
        }))));
        
        // Position X input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Position X'
        })).append($('<input>', {
            type: 'range',
            name: 'image_elements[' + index + '][position_x]',
            value: '50',
            min: '0',
            max: '100'
        })).append($('<span>', {
            class: 'range-value',
            text: '50%'
        })));
        
        // Position Y input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Position Y'
        })).append($('<input>', {
            type: 'range',
            name: 'image_elements[' + index + '][position_y]',
            value: '50',
            min: '0',
            max: '100'
        })).append($('<span>', {
            class: 'range-value',
            text: '50%'
        })));
        
        // Width input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Width'
        })).append($('<input>', {
            type: 'range',
            name: 'image_elements[' + index + '][width]',
            value: '80',
            min: '10',
            max: '100'
        })).append($('<span>', {
            class: 'range-value',
            text: '80%'
        })));
        
        // Height input
        $element.append($('<div>', {
            class: 'form-field'
        }).append($('<label>', {
            text: 'Height'
        })).append($('<input>', {
            type: 'range',
            name: 'image_elements[' + index + '][height]',
            value: '40',
            min: '10',
            max: '100'
        })).append($('<span>', {
            class: 'range-value',
            text: '40%'
        })));
        
        // Remove button
        $element.append($('<button>', {
            type: 'button',
            class: 'button remove-element',
            text: 'Remove'
        }));
        
        // Add to container
        $('#image_elements').append($element);
        
        // Update preview
        updatePreview();
    }
    
    /**
     * Update the template preview
     */
    function updatePreview() {
        // Get template data
        const templateData = getTemplateData();
        
        // Show loading indicator
        $('.preview-container').html('<div class="loading">Generating preview...</div>');
        
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
                    $('.preview-container').html('<img src="' + response.data.image_url + '" alt="Preview">');
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
     * Get template data from form inputs
     */
    function getTemplateData() {
        // Get basic template data
        const templateData = {
            width: parseInt($('#canvas_width').val()) || 1000,
            height: parseInt($('#canvas_height').val()) || 1500,
            background_color: $('#background_color').val() || '#ffffff',
            background_image: $('#background_image').val() || '',
            text_elements: [],
            image_elements: []
        };
        
        // Get text elements
        $('#text_elements .text-element').each(function() {
            const index = $(this).data('index');
            
            templateData.text_elements.push({
                text: $('textarea[name="text_elements[' + index + '][text]"]').val() || '',
                font_size: parseInt($('input[name="text_elements[' + index + '][font_size]"]').val()) || 24,
                font_color: $('input[name="text_elements[' + index + '][font_color]"]').val() || '#000000',
                position_x: parseInt($('input[name="text_elements[' + index + '][position_x]"]').val()) || 50,
                position_y: parseInt($('input[name="text_elements[' + index + '][position_y]"]').val()) || 50,
                width: parseInt($('input[name="text_elements[' + index + '][width]"]').val()) || 80,
                alignment: $('select[name="text_elements[' + index + '][alignment]"]').val() || 'center'
            });
        });
        
        // Get image elements
        $('#image_elements .image-element').each(function() {
            const index = $(this).data('index');
            
            templateData.image_elements.push({
                placeholder_image: $('input[name="image_elements[' + index + '][placeholder_image]"]').val() || '',
                position_x: parseInt($('input[name="image_elements[' + index + '][position_x]"]').val()) || 50,
                position_y: parseInt($('input[name="image_elements[' + index + '][position_y]"]').val()) || 50,
                width: parseInt($('input[name="image_elements[' + index + '][width]"]').val()) || 80,
                height: parseInt($('input[name="image_elements[' + index + '][height]"]').val()) || 40
            });
        });
        
        return templateData;
    }
    
    /**
     * Save the template
     */
    function saveTemplate() {
        // Get template data
        const templateData = getTemplateData();
        
        // Get template name and ID
        const templateName = $('#template_name').val();
        const templateId = $('#template_id').val();
        
        // Validate template name
        if (!templateName) {
            showError('Please enter a template name.');
            return;
        }
        
        // Show loading indicator
        $('.save-template').prop('disabled', true).text('Saving...');
        
        // Send template data to server
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'social_image_save_template',
                nonce: socialImageData.nonce,
                template_id: templateId,
                template_name: templateName,
                template_data: JSON.stringify(templateData)
            },
            success: function(response) {
                $('.save-template').prop('disabled', false).text('Save Template');
                
                if (response.success) {
                    // Update template ID if this is a new template
                    if (!templateId) {
                        $('#template_id').val(response.data.template_id);
                    }
                    
                    showSuccess(response.data.message);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                $('.save-template').prop('disabled', false).text('Save Template');
                showError('Error saving template.');
            }
        });
    }
    
    /**
     * Show an error message
     */
    function showError(message) {
        alert(message);
    }
    
    /**
     * Show a success message
     */
    function showSuccess(message) {
        alert(message);
    }
    
})(jQuery);
