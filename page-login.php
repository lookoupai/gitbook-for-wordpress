<?php
/*
Template Name: 登录页面
*/

if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

get_header();
?>

<style>
.error-message {
    display: none;
    color: #dc3232;
    background-color: #fdf2f2;
    border: 1px solid #fde8e8;
    padding: 10px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.success-message {
    display: none;
    color: #0f5132;
    background-color: #d1e7dd;
    border: 1px solid #badbcc;
    padding: 10px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}
</style>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>
    <main id="main" class="site-main right-content">
        <div class="login-container">
            <h2>用户登录</h2>
            
            <div class="error-message" style="display: none;"></div>
            <div class="success-message" style="display: none;"></div>
            
            <form id="login-form" method="post" novalidate="novalidate">
                <div class="form-group">
                    <label for="user_login">用户名或邮箱</label>
                    <input type="text" name="username" id="user_login" required>
                </div>
                
                <div class="form-group">
                    <label for="user_pass">密码</label>
                    <input type="password" name="password" id="user_pass" required>
                </div>
                
                <div class="form-group">
                    <input type="checkbox" name="rememberme" id="rememberme">
                    <label for="rememberme">记住我</label>
                </div>

                <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
                
                <button type="submit">登录</button>
            </form>
            
            <div class="additional-links">
                <?php 
                $register_page = get_page_by_path('register');
                $register_url = $register_page ? get_permalink($register_page) : wp_registration_url();
                
                $lostpassword_page = get_page_by_path('lost-password');
                $lostpassword_url = $lostpassword_page ? get_permalink($lostpassword_page) : wp_lostpassword_url();
                ?>
                <a href="<?php echo esc_url($register_url); ?>">注册新账号</a> | 
                <a href="<?php echo esc_url($lostpassword_url); ?>">忘记密码？</a>
            </div>
        </div>
        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php
wp_enqueue_script('jquery');
wp_enqueue_script('login-js', get_template_directory_uri() . '/assets/js/login.js', array('jquery'), '1.0', true);
wp_localize_script('login-js', 'ajax_login_object', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'security' => wp_create_nonce('ajax-login-nonce')
));

get_footer(); 
?>
