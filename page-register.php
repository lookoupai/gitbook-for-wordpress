<?php
/*
Template Name: 注册页面
*/

// 阻止直接访问wp-login.php?action=register
add_action('init', function() {
    global $pagenow;
    if ($pagenow === 'wp-login.php' && isset($_GET['action']) && $_GET['action'] === 'register') {
        wp_redirect(get_permalink(get_page_by_path('register')));
        exit;
    }
});

if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// 移除默认的注册处理
remove_action('login_form_register', 'do_register_form');

get_header();
?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>
    
    <main id="main" class="site-main right-content">
        <div class="login-register-container">
            <h1>新用户注册</h1>
            
            <div class="error-message" style="display: none;"></div>
            <div class="success-message" style="display: none;"></div>
            
            <form name="registerform" id="registerform" method="post" novalidate="novalidate">
                <p>
                    <label for="user_login">用户名</label>
                    <input type="text" name="user_login" id="user_login" class="input" required />
                </p>
                
                <p>
                    <label for="user_email">电子邮箱</label>
                    <input type="email" name="user_email" id="user_email" class="input" required />
                </p>

                <p>
                    <label for="user_pass">密码</label>
                    <input type="password" name="user_pass" id="user_pass" class="input" required />
                </p>

                <p>
                    <label for="user_pass_confirm">确认密码</label>
                    <input type="password" name="user_pass_confirm" id="user_pass_confirm" class="input" required />
                </p>

                <?php wp_nonce_field('ajax-register-nonce', 'security'); ?>

                <p class="submit">
                    <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="注册" />
                </p>
            </form>

            <p class="nav">
                已有账号? <a href="<?php echo esc_url(get_permalink(get_page_by_path('login'))); ?>">立即登录</a>
            </p>
        </div>
        
        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php 
wp_enqueue_script('register-js', get_template_directory_uri() . '/assets/js/register.js', array('jquery'), '1.0', true);
wp_localize_script('register-js', 'ajax_register_object', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'security' => wp_create_nonce('ajax-register-nonce'),
    'home_url' => home_url()
));

get_footer(); 
?> 