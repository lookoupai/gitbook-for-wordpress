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
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php
                    switch ($_GET['error']) {
                        case 'invalidkey':
                            echo '密码重置链接无效或已过期。';
                            break;
                        case 'expiredkey':
                            echo '密码重置链接已过期。';
                            break;
                        default:
                            echo '发生错误，请重试。';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['checkemail']) && $_GET['checkemail'] == 'confirm'): ?>
                <div class="success-message">
                    密码重置邮件已发送，请检查您的邮箱。
                </div>
            <?php else: ?>
                <form action="<?php echo esc_url(site_url('wp-login.php?action=lostpassword', 'login_post')); ?>" method="post" class="login-form">
                    <div class="form-group">
                        <label for="user_login">用户名或邮箱</label>
                        <input type="text" name="user_login" id="user_login" class="input" required>
                    </div>
                    
                    <?php do_action('lostpassword_form'); ?>
                    
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url(add_query_arg('checkemail', 'confirm', get_permalink())); ?>">
                    <button type="submit" class="button">获取重置密码链接</button>
                </form>
            <?php endif; ?>
            
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