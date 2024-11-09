<?php
/**
 * Template Name: User Center
 */

// 确保使用 UTF-8 编码
header('Content-Type: text/html; charset=utf-8');

// 在输出任何内容之前检查登录状态
require_once get_template_directory() . '/inc/user-center-functions.php';
require_login();

get_header();

$current_user = wp_get_current_user();
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'profile';
?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>

    <main id="main" class="site-main right-content">
        <?php if (!is_user_logged_in()): ?>
            <div class="login-notice">
                <?php 
                // 使用 esc_html 确保输出正确的编码
                echo '<h2>' . esc_html('想访问请先登录账号') . '</h2>';
                ?>
                <p>请登录后继续访问</p>
                <?php 
                $login_page = get_page_by_path('login');
                $register_page = get_page_by_path('register');
                $login_url = $login_page ? get_permalink($login_page) : wp_login_url();
                $register_url = $register_page ? get_permalink($register_page) : wp_registration_url();
                ?>
                <a href="<?php echo esc_url($login_url); ?>" class="login-button">立即登录</a>
                <a href="<?php echo esc_url($register_url); ?>" class="register-link">还没有账号？立即注册</a>
            </div>
        <?php else: ?>
            <!-- 现有的用户中心内容 -->
            <div class="user-center-container">
                <!-- 用户基本信息区 -->
                <div class="user-info-section">
                    <?php echo get_avatar($current_user->ID, 96); ?>
                    <h2><?php echo esc_html($current_user->display_name); ?></h2>
                    <p>注册时间：<?php echo date('Y-m-d', strtotime($current_user->user_registered)); ?></p>
                </div>

                <!-- Tab导航 -->
                <div class="user-center-tabs">
                    <a href="?tab=profile" class="<?php echo $active_tab === 'profile' ? 'active' : ''; ?>">个人资料</a>
                    <a href="?tab=posts" class="<?php echo $active_tab === 'posts' ? 'active' : ''; ?>">我的文章</a>
                    <a href="?tab=comments" class="<?php echo $active_tab === 'comments' ? 'active' : ''; ?>">我的评论</a>
                    <a href="?tab=favorites" class="<?php echo $active_tab === 'favorites' ? 'active' : ''; ?>">我的收藏</a>
                    <a href="?tab=notifications" class="<?php echo $active_tab === 'notifications' ? 'active' : ''; ?>">消息通知</a>
                </div>

                <!-- Tab内容区 -->
                <div class="tab-content">
                    <?php
                    switch ($active_tab) {
                        case 'profile':
                            get_template_part('template-parts/user/profile');
                            break;
                        case 'posts':
                            get_template_part('template-parts/user/posts');
                            break;
                        case 'comments':
                            get_template_part('template-parts/user/comments');
                            break;
                        case 'favorites':
                            get_template_part('template-parts/user/favorites');
                            break;
                        case 'notifications':
                            get_template_part('template-parts/user/notifications');
                            break;
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php get_footer(); ?> 