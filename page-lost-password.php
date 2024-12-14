<?php
/*
Template Name: 忘记密码
*/

if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

get_header();
?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>
    <main id="main" class="site-main right-content">
        <div class="login-container">
            <h2>重置密码</h2>
            
            <div class="error-message" style="display: none;"></div>
            <div class="success-message" style="display: none;"></div>
            
            <form id="lostpassword-form" class="login-form">
                <div class="form-group">
                    <label for="user_login">用户名或邮箱</label>
                    <input type="text" name="user_login" id="user_login" class="input" required>
                </div>
                
                <?php wp_nonce_field('ajax-lostpassword-nonce', 'security'); ?>
                
                <button type="submit" class="button">获取重置密码链接</button>
            </form>
            
            <div class="additional-links">
                <?php 
                $login_page = get_page_by_path('login');
                $login_url = $login_page ? get_permalink($login_page) : wp_login_url();
                ?>
                <a href="<?php echo esc_url($login_url); ?>">返回登录</a>
            </div>
        </div>
        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php get_footer(); ?>
