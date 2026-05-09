/**
 * Neve Shop Setup - Main JS
 */
(function ($) {
    'use strict';

    // ---- Auth Modal ----

    const $overlay = $('#nss-auth-overlay');
    const $modal = $overlay.find('.nss-auth-modal');

    // Open modal
    $(document).on('click', '.nss-open-auth-modal', function (e) {
        e.preventDefault();
        $overlay.addClass('active');
        $('body').css('overflow', 'hidden');
        // Focus first input
        setTimeout(function () {
            $overlay.find('.nss-tab-content.active input:first').focus();
        }, 400);
    });

    // Close modal
    function closeModal() {
        $overlay.removeClass('active');
        $('body').css('overflow', '');
    }

    $overlay.on('click', '.nss-modal-close', closeModal);
    $overlay.on('click', function (e) {
        if ($(e.target).is('.nss-auth-overlay')) {
            closeModal();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $overlay.hasClass('active')) {
            closeModal();
        }
    });

    // Tab switching
    $(document).on('click', '.nss-tab-btn', function () {
        var tab = $(this).data('tab');
        $('.nss-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.nss-tab-content').removeClass('active');
        $('#nss-' + tab + '-tab').addClass('active');
        // Clear messages
        $('.nss-form-message').removeClass('show success error').text('');
    });

    // Show message helper
    function showMessage($el, type, msg) {
        $el.removeClass('show success error')
            .addClass('show ' + type)
            .text(msg);
    }

    // Login form
    $(document).on('submit', '#nss-login-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('.nss-submit-btn');
        var $msg = $('#nss-login-message');

        $btn.find('.nss-btn-text').hide();
        $btn.find('.nss-btn-loader').show();
        $btn.prop('disabled', true);
        $msg.removeClass('show');

        $.ajax({
            url: nssAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'neve_shop_login',
                nonce: nssAjax.nonce,
                username: $form.find('[name="username"]').val(),
                password: $form.find('[name="password"]').val(),
                remember: $form.find('[name="remember"]').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                if (res.success) {
                    showMessage($msg, 'success', res.data.message);
                    setTimeout(function () {
                        window.location.href = res.data.redirect || window.location.href;
                    }, 800);
                } else {
                    showMessage($msg, 'error', res.data.message);
                    $btn.find('.nss-btn-text').show();
                    $btn.find('.nss-btn-loader').hide();
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showMessage($msg, 'error', 'Something went wrong. Please try again.');
                $btn.find('.nss-btn-text').show();
                $btn.find('.nss-btn-loader').hide();
                $btn.prop('disabled', false);
            },
        });
    });

    // Register form
    $(document).on('submit', '#nss-register-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('.nss-submit-btn');
        var $msg = $('#nss-register-message');

        $btn.find('.nss-btn-text').hide();
        $btn.find('.nss-btn-loader').show();
        $btn.prop('disabled', true);
        $msg.removeClass('show');

        $.ajax({
            url: nssAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'neve_shop_register',
                nonce: nssAjax.nonce,
                username: $form.find('[name="username"]').val(),
                email: $form.find('[name="email"]').val(),
                password: $form.find('[name="password"]').val(),
            },
            success: function (res) {
                if (res.success) {
                    showMessage($msg, 'success', res.data.message);
                    setTimeout(function () {
                        window.location.href = res.data.redirect || window.location.href;
                    }, 800);
                } else {
                    showMessage($msg, 'error', res.data.message);
                    $btn.find('.nss-btn-text').show();
                    $btn.find('.nss-btn-loader').hide();
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showMessage($msg, 'error', 'Something went wrong. Please try again.');
                $btn.find('.nss-btn-text').show();
                $btn.find('.nss-btn-loader').hide();
                $btn.prop('disabled', false);
            },
        });
    });

    // ---- Cart count live update via AJAX (WooCommerce fragment refresh) ----

    $(document.body).on('added_to_cart removed_from_cart updated_cart_totals', function () {
        // WooCommerce updates fragments; we also update our badge
        $.ajax({
            url: nssAjax.ajaxurl,
            type: 'POST',
            data: { action: 'nss_get_cart_count' },
            success: function (res) {
                if (res && res.count !== undefined) {
                    var $badge = $('.nss-cart-badge');
                    if (res.count > 0) {
                        if ($badge.length) {
                            $badge.text(res.count);
                        } else {
                            $('.nss-cart-icon > a').append(
                                '<span class="nss-cart-badge">' + res.count + '</span>'
                            );
                        }
                    } else {
                        $badge.remove();
                    }
                }
            },
        });
    });

})(jQuery);
