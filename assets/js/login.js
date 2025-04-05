jQuery(document).ready(function($) {
    // 登录表单处理
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $errorMessage = $('.error-message');
        var $successMessage = $('.success-message');
        
        // 清除之前的消息
        $errorMessage.empty().hide();
        $successMessage.empty().hide();
        
        // 禁用提交按钮并显示加载状态
        $submitButton.prop('disabled', true).text('登录中...');
        
        $.ajax({
            url: ajax_login_object.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ajax_login',
                username: $('#user_login').val(),
                password: $('#user_pass').val(),
                rememberme: $('#rememberme').is(':checked'),
                security: ajax_login_object.security
            },
            success: function(response) {
                if (response.success) {
                    // 登录成功
                    $successMessage.html('登录成功，正在跳转...').show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    // 登录失败
                    $errorMessage.html(response.data.message).show();
                    $submitButton.prop('disabled', false).text('登录');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error);
                var errorMsg = '登录请求失败，请稍后重试。';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                $errorMessage.html(errorMsg).show();
                $submitButton.prop('disabled', false).text('登录');
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
        
        // 清除之前的消息
        $errorMessage.empty().hide();
        $successMessage.empty().hide();
        
        // 禁用提交按钮并显示加载状态
        $submitButton.prop('disabled', true).text('提交中...');
        
        $.ajax({
            url: ajax_login_object.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ajax_lostpassword',
                user_login: $('#user_login').val(),
                security: ajax_login_object.lostpassword_security
            },
            success: function(response) {
                if (response.success) {
                    $successMessage.html(response.data.message).show();
                    $form.hide();
                } else {
                    $errorMessage.html(response.data.message).show();
                }
                $submitButton.prop('disabled', false).text('获取重置链接');
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error);
                var errorMsg = '请求失败，请稍后重试。';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                $errorMessage.html(errorMsg).show();
                $submitButton.prop('disabled', false).text('获取重置链接');
            }
        });
    });
});
