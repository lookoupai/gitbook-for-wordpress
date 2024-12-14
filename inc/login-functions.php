<?php
// AJAX Login Handler
function ajax_login_init() {
    add_action('wp_ajax_nopriv_ajax_login', 'ajax_login');
    add_action('wp_ajax_nopriv_ajax_lostpassword', 'ajax_lostpassword');
}
add_action('init', 'ajax_login_init');

function ajax_login() {
    check_ajax_referer('ajax-login-nonce', 'security');
    
    $info = array();
    $info['user_login'] = $_POST['username'];
    $info['user_password'] = $_POST['password'];
    $info['remember'] = $_POST['rememberme'];
    
    $user_signon = wp_signon($info, false);
    
    if (is_wp_error($user_signon)) {
        wp_send_json_error(array(
            'message' => '用户名或密码错误，请重试。'
        ));
    } else {
        wp_send_json_success(array(
            'redirect' => home_url()
        ));
    }
    
    die();
}

function ajax_lostpassword() {
    check_ajax_referer('ajax-lostpassword-nonce', 'security');
    
    $user_login = $_POST['user_login'];
    
    if (empty($user_login)) {
        wp_send_json_error(array(
            'message' => '请输入用户名或邮箱地址。'
        ));
        die();
    }

    if (strpos($user_login, '@')) {
        $user_data = get_user_by('email', trim($user_login));
    } else {
        $user_data = get_user_by('login', trim($user_login));
    }

    if (!$user_data) {
        wp_send_json_error(array(
            'message' => '没有使用该用户名或邮箱地址的账户。'
        ));
        die();
    }

    // 获取用户邮箱
    $user_email = $user_data->user_email;
    
    // 生成密码重置密钥
    $key = get_password_reset_key($user_data);
    
    if (is_wp_error($key)) {
        wp_send_json_error(array(
            'message' => '生成重置密钥时出错，请稍后重试。'
        ));
        die();
    }
    
    // 构建重置链接
    $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_data->user_login), 'login');
    
    // 发送邮件
    $message = __('有人请求重置以下账号的密码：') . "\r\n\r\n";
    $message .= network_home_url('/') . "\r\n\r\n";
    $message .= sprintf(__('用户名：%s'), $user_data->user_login) . "\r\n\r\n";
    $message .= __('如果这不是您本人的操作，请忽略此邮件。') . "\r\n\r\n";
    $message .= __('要重置密码，请访问以下地址：') . "\r\n\r\n";
    $message .= $reset_url . "\r\n";

    $title = sprintf(__('[%s] 密码重置请求'), get_bloginfo('name'));
    
    if (wp_mail($user_email, $title, $message)) {
        wp_send_json_success(array(
            'message' => '密码重置邮件已发送，请检查您的邮箱。'
        ));
    } else {
        wp_send_json_error(array(
            'message' => '发送邮件时出错，请稍后重试。'
        ));
    }
    
    die();
}

// Enqueue login scripts
function enqueue_login_scripts() {
    if (is_page_template('page-login.php') || is_page_template('page-lost-password.php')) {
        wp_enqueue_script('login-js', get_template_directory_uri() . '/assets/js/login.js', array('jquery'), '1.0', true);
        wp_localize_script('login-js', 'ajax_login_object', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('ajax-login-nonce'),
            'lostpassword_security' => wp_create_nonce('ajax-lostpassword-nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_login_scripts');
