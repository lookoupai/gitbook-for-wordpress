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
                            // 显示提交成功的提示
                            if (isset($_GET['submitted']) && $_GET['submitted'] == '1') {
                                echo '<div class="notice notice-success"><p>文章提交成功，等待审核。</p></div>';
                            }

                            // 获取用户的文章
                            $args = array(
                                'author' => get_current_user_id(),
                                'post_type' => 'post',
                                'post_status' => array('publish', 'pending', 'draft'),
                                'posts_per_page' => 10,
                                'paged' => get_query_var('paged') ? get_query_var('paged') : 1
                            );
                            
                            $user_posts = new WP_Query($args);
                            
                            if ($user_posts->have_posts()) :
                                echo '<div class="user-posts-list">';
                                while ($user_posts->have_posts()) : $user_posts->the_post();
                                    ?>
                                    <div class="post-item">
                                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                        <div class="post-meta">
                                            <span class="post-date"><?php echo get_the_date(); ?></span>
                                            <span class="post-status">状态：<?php echo get_post_status_label(get_post_status()); ?></span>
                                        </div>
                                        <div class="post-actions">
                                            <a href="<?php echo esc_url(add_query_arg('post_id', get_the_ID(), home_url('/edit-post'))); ?>" class="edit-link">编辑</a>
                                            <?php if (current_user_can('delete_post', get_the_ID())): ?>
                                                <a href="<?php echo get_delete_post_link(); ?>" class="delete-link" onclick="return confirm('确定要删除这篇文章吗？');">删除</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php
                                endwhile;
                                echo '</div>';
                                
                                // 添加分页
                                echo '<div class="pagination">';
                                echo paginate_links(array(
                                    'total' => $user_posts->max_num_pages,
                                    'current' => max(1, get_query_var('paged')),
                                    'prev_text' => '&laquo; 上一页',
                                    'next_text' => '下一页 &raquo;'
                                ));
                                echo '</div>';
                                
                                wp_reset_postdata();
                            else:
                                echo '<p>暂无文章</p>';
                            endif;
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