<?php
/*
Template Name: 注册页面
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
        <div class="register-container">
            <h2>新用户注册</h2>
            
            <?php if (isset($_GET['register']) && $_GET['register'] == 'failed'): ?>
                <div class="error-message">
                    注册失败，请重试。
                </div>
            <?php endif; ?>
            
            <form action="<?php echo esc_url(site_url('wp-login.php?action=register', 'login_post')); ?>" method="post">
                <div class="form-group">
                    <label for="user_login">用户名</label>
                    <input type="text" name="user_login" id="user_login" required>
                </div>
                
                <div class="form-group">
                    <label for="user_email">电子邮箱</label>
                    <input type="email" name="user_email" id="user_email" required>
                </div>
                
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>">
                <button type="submit">注册</button>
            </form>
            
            <div class="additional-links">
                <?php 
                $login_page = get_page_by_path('login');
                $login_url = $login_page ? get_permalink($login_page) : wp_login_url();
                ?>
                <a href="<?php echo esc_url($login_url); ?>">已有账号？立即登录</a>
            </div>
        </div>
        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php get_footer(); ?> 