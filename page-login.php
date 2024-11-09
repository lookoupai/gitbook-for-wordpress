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

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>
    <main id="main" class="site-main right-content">
        <div class="login-container">
            <h2>用户登录</h2>
            
            <?php if (isset($_GET['login']) && $_GET['login'] == 'failed'): ?>
                <div class="error-message">
                    用户名或密码错误，请重试。
                </div>
            <?php endif; ?>
            
            <form action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
                <div class="form-group">
                    <label for="user_login">用户名或邮箱</label>
                    <input type="text" name="log" id="user_login" required>
                </div>
                
                <div class="form-group">
                    <label for="user_pass">密码</label>
                    <input type="password" name="pwd" id="user_pass" required>
                </div>
                
                <div class="form-group">
                    <input type="checkbox" name="rememberme" id="rememberme">
                    <label for="rememberme">记住我</label>
                </div>
                
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>">
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

<?php get_footer(); ?> 