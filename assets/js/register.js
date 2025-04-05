jQuery(document).ready(function($) {
    $('#registerform').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        var $errorMessage = $('.error-message');
        var $successMessage = $('.success-message');
        
        // 清除之前的消息
        $errorMessage.empty().hide();
        $successMessage.empty().hide();
        
        // 验证密码匹配
        var password = $('#user_pass').val();
        var confirmPassword = $('#user_pass_confirm').val();
        
        if (password !== confirmPassword) {
            $errorMessage.html('两次输入的密码不一致').show();
            return;
        }
        
        // 禁用提交按钮
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajax_register_object.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ajax_register',
                user_login: $('#user_login').val(),
                user_email: $('#user_email').val(),
                user_pass: password,
                security: ajax_register_object.security
            },
            success: function(response) {
                if (response.success) {
                    $successMessage.html(response.data.message).show();
                    $form.hide();
                    // 延迟后跳转到登录页面
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 2000);
                } else {
                    $errorMessage.html(response.data.message).show();
                    $submitButton.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error);
                $errorMessage.html('注册请求失败，请稍后重试。').show();
                $submitButton.prop('disabled', false);
            }
        });
    });
}); 