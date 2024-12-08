(function($) {
    'use strict';

    // Handle media upload for PDF logo
    $('.media-upload').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const input = button.prev('input');
        
        const mediaUploader = wp.media({
            title: 'Select Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            input.val(attachment.url);
        });

        mediaUploader.open();
    });

    // Handle form filter
    $('select[name="form_filter"]').on('change', function() {
        const form = $(this).val();
        if (form) {
            window.location.href = window.location.href + '&form=' + form;
        }
    });

})(jQuery); 