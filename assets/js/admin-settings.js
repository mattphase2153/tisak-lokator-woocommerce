jQuery(function ($) {
    $('#tisak-save-css').on('click', function () {
        let css = $('#tisak-custom-css').val();
        $.post(ajaxurl, {
            action: 'tisak_save_custom_css',
            css: css
        }, function (response) {
            if (response.success) {
                $('#tisak-css-message').text('CSS saved successfully.').css('color', 'green');
            } else {
                $('#tisak-css-message').text('Error saving CSS: ' + response.data).css('color', 'red');
            }
        });
    });
});