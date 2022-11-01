jQuery(document).ready(function($){
    var custom_uploader;
    $('#qpp_upload_background_image').click(function(e) {
        e.preventDefault();
        if (custom_uploader) {custom_uploader.open();return;}
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Background Image',button: {text: 'Insert Image'},multiple: false});
        custom_uploader.on('select', function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#qpp_background_image').val(attachment.url);
        });
        custom_uploader.open();
    });
    var button_uploader;
    $('#qpp_upload_submit_button').click(function(e) {
        e.preventDefault();
        if (button_uploader) {button_uploader.open();return;}
        button_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Submit Button Image',button: {text: 'Insert Image'},multiple: false});
        button_uploader.on('select', function() {
            attachment = button_uploader.state().get('selection').first().toJSON();
            $('#qpp_submit_button').val(attachment.url);
        });
        button_uploader.open();
    });
    $('#qpp_upload_media_button').click(function (e) {
        e.preventDefault();
        if (custom_uploader) {custom_uploader.open(); return; }
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Select Image', button: {text: 'Insert Image'}, multiple: false});
        custom_uploader.on('select', function () {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#qpp_upload_image').val(attachment.url);
        });
        custom_uploader.open();
    });
    $('.qpp-color').wpColorPicker();
});