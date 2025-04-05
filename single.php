<?php
get_header();
?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>

    <main id="main" class="site-main right-content">
        <?php
        while (have_posts()) :
            the_post();
        ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    <div class="entry-meta">
                        <span class="post-meta">
                            最后更新于：<?php echo get_the_modified_time('Y-m-d H:i'); ?> 
                            作者：<?php the_author(); ?> 
                            分类：<?php the_category(', '); ?>
                        </span>
                        <?php if (is_user_logged_in()) : ?>
                            <span class="edit-link">
                                <a href="<?php echo esc_url(home_url('/edit-post/?post_id=' . get_the_ID())); ?>">编辑此文章</a>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="entry-content">
                    <?php
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . '分页：',
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <?php 
                // 评论部分
                comments_template(); 
                ?>

                <?php 
                // 显示AI审核信息（如果有）
                $ai_review_score = get_post_meta(get_the_ID(), '_ai_review_score', true);
                if (!empty($ai_review_score) && current_user_can('administrator')) {
                    echo display_ai_review_results(get_the_ID());
                }
                ?>
            </article>

        <?php endwhile; ?>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php
get_footer();