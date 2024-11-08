<?php
/**
 * Template Name: User Center
 */

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>

    <main id="main" class="site-main right-content">
        <div class="user-center-container">
            <!-- 用户基本信息区 -->
            <div class="user-info-section">
                <?php echo get_avatar($current_user->ID, 96); ?>
                <h2><?php echo esc_html($current_user->display_name); ?></h2>
                <p>注册时间：<?php echo date('Y-m-d', strtotime($current_user->user_registered)); ?></p>
            </div>

            <!-- 用户文章列表 -->
            <div class="user-posts-section">
                <h3>我的文章</h3>
                <?php
                $args = array(
                    'author' => $current_user->ID,
                    'post_type' => 'post',
                    'posts_per_page' => 10
                );
                $user_posts = new WP_Query($args);
                
                if ($user_posts->have_posts()) :
                    while ($user_posts->have_posts()) : $user_posts->the_post();
                ?>
                    <div class="post-item">
                        <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                        <div class="post-meta">
                            <span>状态：<?php echo get_post_status(); ?></span>
                            <a href="<?php echo get_edit_post_link(); ?>" class="edit-link">编辑</a>
                        </div>
                    </div>
                <?php
                    endwhile;
                else:
                ?>
                    <p>暂无文章</p>
                <?php
                endif;
                wp_reset_postdata();
                ?>
            </div>
        </div>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php get_footer(); ?> 