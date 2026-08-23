jQuery(document).ready(function ($) {
    /*
     * One handler for every image field. Each picker is described by data
     * attributes on its button, so adding a field is a markup change rather than
     * another near identical copy of this function. It used to be three.
     */
    var frames = {};

    $(document).on('click', '.qpp-media-pick', function (e) {
        e.preventDefault();

        var button = $(this);
        var key = button.data('target');

        if (!frames[key]) {
            frames[key] = wp.media({
                title: button.data('title') || 'Select image',
                button: {text: 'Use this image'},
                library: {type: 'image'},
                multiple: false
            });

            frames[key].on('select', function () {
                var attachment = frames[key].state().get('selection').first().toJSON();
                $(key).val(attachment.url).trigger('change');
            });
        }

        frames[key].open();
    });

    /*
     * The buttons that ship with the plugin. Each carries the URL it stands for,
     * so choosing one does the same thing as typing that URL into the field.
     */
    $(document).on('click', '.qpp-button-choice', function (e) {
        e.preventDefault();

        var button = $(this);

        $(button.data('target')).val(button.data('value')).trigger('change');
    });

    // Keep the preview and the highlighted choice in step however the value
    // arrived, including a URL typed or pasted straight into the field.
    $(document).on('change input', '.qpp-media-url', function () {
        var url = $(this).val();
        var id = $(this).attr('id');
        var preview = $('#' + id + '_preview');

        $('.qpp-button-choice[data-target="#' + id + '"]').each(function () {
            $(this).toggleClass('is-selected', String($(this).data('value')) === url);
        });

        if (!preview.length) {
            return;
        }

        preview.attr('src', url).toggle('' !== url);
    });

    $('.qpp-media-url').trigger('change');

    /*
     * The PayPal account, checked as it is typed. A mistyped account is the most
     * common support question this plugin gets, and it is invisible until a
     * customer reaches PayPal and is turned away by a page that says nothing
     * useful. Checking on change catches it while the person who typed it is
     * still looking at the field. The button beside it does the same thing
     * without javascript.
     */
    var accountTimer;
    var lastChecked = null;

    function showAccountStatus(text, state) {
        $('#qpp_account_status')
            .removeClass('is-good is-bad is-working')
            .addClass(state)
            .text(text);
    }

    function checkAccount() {
        if (typeof qpp_account_check === 'undefined') {
            return;
        }

        var email = $.trim($('#qpp_account_email').val());

        if ('' === email || email === lastChecked) {
            return;
        }

        lastChecked = email;
        showAccountStatus(qpp_account_check.working, 'is-working');

        $.post(qpp_account_check.url, {
            action: 'qpp_check_paypal_account',
            _ajax_nonce: qpp_account_check.nonce,
            email: email
        }).done(function (response) {
            if (!response || !response.data) {
                showAccountStatus('', '');
                return;
            }
            showAccountStatus(response.data.message, response.data.ok ? 'is-good' : 'is-bad');
        }).fail(function () {
            // Silent. The button is still there, and a failed check is not a
            // reason to tell somebody their account is wrong.
            showAccountStatus('', '');
        });
    }

    $(document).on('change blur', '#qpp_account_email', function () {
        clearTimeout(accountTimer);
        accountTimer = setTimeout(checkAccount, 400);
    });

    $('.qpp-color').wpColorPicker();
});
