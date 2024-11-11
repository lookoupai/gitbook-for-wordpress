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
        <div class="login-register-container">
            <h1>新用户注册</h1>
            
            <form name="registerform" id="registerform" action="<?php echo esc_url(site_url('wp-login.php?action=register', 'login_post')); ?>" method="post">
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

                <p class="submit">
                    <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="注册" />
                </p>
            </form>

            <p class="nav">
                已有账号? <a href="<?php echo wp_login_url(); ?>">立即登录</a>
            </p>
        </div>
        
        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php get_footer(); ?> 