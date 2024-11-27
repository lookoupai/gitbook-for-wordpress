<?php
/**
 * Template Name: Community Voting
 */

// 检查用户登录状态
if (!is_user_logged_in()) {
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
        <header class="page-header">
            <h1 class="page-title">社区投票</h1>
            <div class="vote-type-tabs">
                <a href="?type=new" class="<?php echo (!isset($_GET['type']) || $_GET['type'] === 'new') ? 'active' : ''; ?>">新投稿</a>
                <a href="?type=edit" class="<?php echo (isset($_GET['type']) && $_GET['type'] === 'edit') ? 'active' : ''; ?>">文章修改</a>
            </div>
        </header>
        
        <?php
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $posts_per_page = 10;
        $vote_type = isset($_GET['type']) ? $_GET['type'] : 'new';
        
        if ($vote_type === 'edit') {
            // 获取待审核的修订版本
            $pending_posts = new WP_Query(array(
                'post_type' => 'revision',
                'post_status' => 'pending',
                'posts_per_page' => $posts_per_page,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
        } else {
            // 获取待审核的新投稿
            $pending_posts = new WP_Query(array(
                'post_status' => 'pending',
                'post_type' => 'post',
                'posts_per_page' => $posts_per_page,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_post_type',
                        'value' => 'new_submission',
                        'compare' => '='
                    ),
                    array(
                        'key' => '_post_type',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ));
        }

        if ($pending_posts->have_posts()) : ?>
            <div class="pending-posts">
                <?php while ($pending_posts->have_posts()) : $pending_posts->the_post(); 
                    $post_id = $vote_type === 'edit' ? get_the_ID() : get_the_ID();
                    $votes = get_post_votes($post_id);
                    $voting_status = get_post_meta($post_id, '_voting_status', true);
                ?>
                    <article class="voting-card">
                        <header class="entry-header">
                            <h2><?php echo esc_html(get_the_title()); ?></h2>
                            <div class="post-meta">
                                <span>作者：<?php echo get_the_author_meta('display_name'); ?></span>
                                <span>提交时间：<?php echo get_the_date('Y-m-d H:i'); ?></span>
                                <?php if ($vote_type === 'edit') : ?>
                                    <button class="view-diff-btn" data-revision-id="<?php echo get_the_ID(); ?>">
                                        查看修改内容
                                    </button>
                                <?php endif; ?>
                            </div>
                        </header>

                        <div class="entry-content">
                            <?php if ($vote_type === 'edit') : ?>
                                <?php 
                                // 获取并显示修改说明
                                $revision_id = get_the_ID();
                                $edit_summary = get_revision_summary($revision_id);
                                if ($edit_summary) : ?>
                                    <div class="edit-summary">
                                        <h4>修改说明：</h4>
                                        <p><?php echo esc_html($edit_summary); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="diff-content" id="diff-<?php echo get_the_ID(); ?>" style="display:none;">
                                    <!-- 差异内容将通过AJAX加载 -->
                                </div>
                            <?php else : ?>
                                <?php the_excerpt(); ?>
                            <?php endif; ?>
                            <a href="<?php echo get_permalink($post_id); ?>" class="read-more">查看原文</a>
                        </div>
                        
                        <div class="voting-stats">
                            <div class="vote-count">
                                <span>赞成：<?php echo $votes['approve']; ?></span>
                                <span>反对：<?php echo $votes['reject']; ?></span>
                            </div>
                            
                            <?php if ($voting_status !== 'completed') : ?>
                                <?php if (current_user_can('manage_options')) : ?>
                                    <div class="admin-actions">
                                        <button class="admin-approve" data-post-id="<?php echo get_the_ID(); ?>" data-type="<?php echo $vote_type; ?>">通过</button>
                                        <button class="admin-reject" data-post-id="<?php echo get_the_ID(); ?>" data-type="<?php echo $vote_type; ?>">拒绝</button>
                                    </div>
                                <?php else : ?>
                                    <?php 
                                    $user_id = get_current_user_id();
                                    $permission_check = check_user_voting_permission($user_id, $post_id);
                                    if (!is_wp_error($permission_check)) : 
                                    ?>
                                        <div class="user-voting">
                                            <button class="vote-approve" data-post-id="<?php echo $post_id; ?>" data-type="<?php echo $vote_type; ?>">赞成</button>
                                            <button class="vote-reject" data-post-id="<?php echo $post_id; ?>" data-type="<?php echo $vote_type; ?>">反对</button>
                                        </div>
                                    <?php else : ?>
                                        <div class="voting-error">
                                            <?php echo esc_html($permission_check->get_error_message()); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else : ?>
                                <div class="voting-completed">
                                    投票已结束
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="voting-users">
                            <h3>投票记录</h3>
                            <div class="approve-users">
                                <h4>赞成用户</h4>
                                <p><?php echo get_voting_users_list($post_id, 1); ?></p>
                            </div>
                            <div class="reject-users">
                                <h4>反对用户</h4>
                                <p><?php echo get_voting_users_list($post_id, 0); ?></p>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>

                <?php
                // 分页
                echo '<div class="pagination">';
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'total' => $pending_posts->max_num_pages,
                    'current' => $paged,
                    'prev_text' => __('&laquo; 上一页'),
                    'next_text' => __('下一页 &raquo;')
                ));
                echo '</div>';

                // 显示统计信息
                echo '<div class="voting-stats-summary">';
                printf('共有 %d 篇%s待投票，当前显示第 %d 页，共 %d 页',
                    $pending_posts->found_posts,
                    $vote_type === 'edit' ? '修改' : '新文章',
                    $paged,
                    $pending_posts->max_num_pages
                );
                echo '</div>';

                wp_reset_postdata();
                ?>
            </div>
        <?php else : ?>
            <p class="no-posts">当前没有待投票的<?php echo $vote_type === 'edit' ? '修改' : '新文章'; ?></p>
        <?php endif; ?>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<!-- 添加差异对比弹窗 -->
<div id="diff-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="diff-modal-content"></div>
    </div>
</div>

<?php get_footer(); ?> 