jQuery(function ($) {
    var $input;
    $('body').on('click', '.aw_upload_file_button', function (e) {
        e.preventDefault();
        $input = this;

        var aw_uploader = wp.media({
            title: 'Datei verknüpfen',
            button: {
                text: 'Diese Datei wählen'
            },
            multiple: false,
            frame: 'select'
        }).on('select', function () {
            var attachment = aw_uploader.state().get('selection').first().toJSON();
            $($input).prev().val(attachment.url);
        }).open();
    });
});