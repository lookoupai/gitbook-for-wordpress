<?php
// AJAX Login Handler
function ajax_login_init() {
    add_action('wp_ajax_nopriv_ajax_login', 'ajax_login');
    add_action('wp_ajax_nopriv_ajax_lostpassword', 'ajax_lostpassword');
    add_action('wp_ajax_nopriv_ajax_register', 'ajax_register');
}
add_action('init', 'ajax_login_init');

// 阻止所有到wp-login.php的访问
function prevent_wp_login() {
    global $pagenow;
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // 如果是wp-login.php页面
    if ($pagenow === 'wp-login.php') {
        // 允许的操作列表
        $allowed_actions = ['rp', 'resetpass', 'lostpassword', 'logout'];
        
        // 如果是注销操作，执行注销并重定向
        if ($action === 'logout') {
            check_admin_referer('log-out');
            wp_logout();
            wp_safe_redirect(home_url());
            exit();
        }
        
        // 如果不是允许的操作，重定向到自定义页面
        if (!in_array($action, $allowed_actions)) {
            $redirect_to = '';
            
            switch ($action) {
                case 'register':
                    $redirect_to = get_permalink(get_page_by_path('register'));
                    break;
                case 'lostpassword':
                    $redirect_to = get_permalink(get_page_by_path('lost-password'));
                    break;
                default:
                    $redirect_to = get_permalink(get_page_by_path('login'));
            }
            
            wp_redirect($redirect_to);
            exit();
        }
    }
}
add_action('init', 'prevent_wp_login', 1);

// 修改WordPress默认URL
function custom_login_url($login_url, $redirect = '', $force_reauth = false) {
    $login_page = get_permalink(get_page_by_path('login'));
    if (!empty($redirect)) {
        $login_page = add_query_arg('redirect_to', urlencode($redirect), $login_page);
    }
    return $login_page;
}
add_filter('login_url', 'custom_login_url', 10, 3);

function custom_register_url($register_url) {
    return get_permalink(get_page_by_path('register'));
}
add_filter('register_url', 'custom_register_url', 10, 1);

function custom_lostpassword_url($lostpassword_url) {
    return get_permalink(get_page_by_path('lost-password'));
}
add_filter('lostpassword_url', 'custom_lostpassword_url', 10, 1);

// 自定义注销URL
function custom_logout_url($logout_url, $redirect = '') {
    $args = array('action' => 'logout');
    if (!empty($redirect)) {
        $args['redirect_to'] = urlencode($redirect);
    }
    $args['_wpnonce'] = wp_create_nonce('log-out');
    return add_query_arg($args, site_url('wp-login.php', 'login'));
}
add_filter('logout_url', 'custom_logout_url', 10, 2);

function ajax_login() {
    check_ajax_referer('ajax-login-nonce', 'security');
    
    $info = array();
    $info['user_login'] = $_POST['username'];
    $info['user_password'] = $_POST['password'];
    $info['remember'] = $_POST['rememberme'];

    // 首先检查用户是否存在
    $user = get_user_by('login', $info['user_login']);
    if (!$user && strpos($info['user_login'], '@')) {
        $user = get_user_by('email', $info['user_login']);
    }
    
    if (!$user) {
        error_log('Login attempt failed: User does not exist - ' . $info['user_login']);
        wp_send_json_error(array(
            'message' => '该用户不存在，请检查用户名或注册新账号。'
        ));
        die();
    }
    
    // 只移除登录重定向相关的过滤器
    remove_all_filters('login_redirect');
    remove_all_filters('login_url');
    remove_all_actions('wp_login_failed');
    
    // 执行登录
    $user_signon = wp_signon($info, false);
    
    if (is_wp_error($user_signon)) {
        $error_message = $user_signon->get_error_message();
        error_log('Login error: ' . $error_message);
        wp_send_json_error(array(
            'message' => !empty($error_message) ? $error_message : '密码错误，请重试。'
        ));
    } else {
        error_log('Login successful for user: ' . $info['user_login']);
        // 设置认证cookie
        wp_set_auth_cookie($user_signon->ID, $info['remember']);
        wp_send_json_success(array(
            'message' => '登录成功！',
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

// AJAX注册处理函数
function ajax_register() {
    check_ajax_referer('ajax-register-nonce', 'security');
    
    $user_login = sanitize_user($_POST['user_login']);
    $user_email = sanitize_email($_POST['user_email']);
    $user_pass = $_POST['user_pass'];
    
    // 验证用户名
    if (empty($user_login) || username_exists($user_login)) {
        wp_send_json_error(array(
            'message' => '用户名已存在或无效，请选择其他用户名。'
        ));
        die();
    }
    
    // 验证邮箱
    if (empty($user_email) || !is_email($user_email) || email_exists($user_email)) {
        wp_send_json_error(array(
            'message' => '邮箱地址已存在或无效，请使用其他邮箱。'
        ));
        die();
    }
    
    // 验证密码
    if (empty($user_pass) || strlen($user_pass) < 6) {
        wp_send_json_error(array(
            'message' => '密码不能少于6个字符。'
        ));
        die();
    }
    
    // 创建用户
    $user_id = wp_create_user($user_login, $user_pass, $user_email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array(
            'message' => $user_id->get_error_message()
        ));
        die();
    }
    
    // 设置用户角色
    $user = new WP_User($user_id);
    $user->set_role('subscriber');
    
    // 发送成功响应
    wp_send_json_success(array(
        'message' => '注册成功！正在跳转到登录页面...',
        'redirect' => get_permalink(get_page_by_path('login'))
    ));
    
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
