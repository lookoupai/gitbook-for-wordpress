jQuery(document).ready(function($) {
    // 登录表单处理
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $errorMessage = $('.error-message');
        
        // Clear previous error
        $errorMessage.empty().hide();
        
        // Disable submit button
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajax_login_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'ajax_login',
                username: $('#user_login').val(),
                password: $('#user_pass').val(),
                rememberme: $('#rememberme').is(':checked'),
                security: ajax_login_object.security
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    $errorMessage.html(response.data.message).show();
                    $submitButton.prop('disabled', false);
                }
            },
            error: function() {
                $errorMessage.html('登录请求失败，请稍后重试。').show();
                $submitButton.prop('disabled', false);
            }
        });
    });

    // 找回密码表单处理
    $('#lostpassword-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $errorMessage = $('.error-message');
        var $successMessage = $('.success-message');
        
        // Clear previous messages
        $errorMessage.empty().hide();
        $successMessage.empty().hide();
        
        // Disable submit button
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajax_login_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'ajax_lostpassword',
                user_login: $('#user_login').val(),
                security: ajax_login_object.lostpassword_security
            },
            success: function(response) {
                if (response.success) {
                    $successMessage.html(response.data.message).show();
                    $form.hide(); // 隐藏表单
                } else {
                    $errorMessage.html(response.data.message).show();
                }
                $submitButton.prop('disabled', false);
            },
            error: function() {
                $errorMessage.html('请求失败，请稍后重试。').show();
                $submitButton.prop('disabled', false);
            }
        });
    });
});
