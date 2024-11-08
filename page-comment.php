<?php
/**
 * Template Name: Edit Comment
 */

require_once get_template_directory() . '/inc/user-center-functions.php';
require_login();

$comment_id = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : 0;
$comment = get_comment($comment_id);

// 检查评论是否存在
if (!$comment) {
    wp_redirect(home_url());
    exit;
}

// 检查用户权限
if (!is_user_logged_in() || $comment->user_id != get_current_user_id()) {
    wp_die('您没有权限编辑此评论');
}

get_header();
?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>

    <main id="main" class="site-main right-content">
        <div class="edit-comment-container">
            <h3>编辑评论</h3>
            <p>您正在编辑对《<?php echo get_the_title($comment->comment_post_ID); ?>》的评论</p>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="edit_comment">
                <?php wp_nonce_field('edit_comment', 'comment_edit_nonce'); ?>
                <input type="hidden" name="comment_id" value="<?php echo $comment_id; ?>">

                <div class="form-group">
                    <label for="comment_content">评论内容</label>
                    <textarea id="comment_content" name="comment_content" required><?php 
                        echo esc_textarea($comment->comment_content); 
                    ?></textarea>
                </div>

                <div class="form-group">
                    <p class="description">
                        注意：编辑后的评论需要重新审核才能显示。
                    </p>
                </div>

                <button type="submit" class="submit-button">提交修改</button>
            </form>
        </div>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php get_footer(); ?> 