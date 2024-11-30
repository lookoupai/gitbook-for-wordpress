<?php
if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area">
    <?php if (have_comments()) : ?>
        <h2 class="comments-title">
            <?php
            $comments_number = get_comments_number();
            if ($comments_number === '1') {
                printf('《%s》上有 1 条评论', get_the_title());
            } else {
                printf('《%s》上有 %s 条评论', get_the_title(), number_format_i18n($comments_number));
            }
            ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments(array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 60,
                'callback'    => 'custom_comment_template'
            ));
            ?>
        </ol>

        <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
            <nav class="comment-navigation">
                <div class="nav-previous"><?php previous_comments_link('← 较早的评论'); ?></div>
                <div class="nav-next"><?php next_comments_link('较新的评论 →'); ?></div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) : ?>
        <p class="no-comments">评论已关闭。</p>
    <?php endif; ?>

    <?php
    comment_form(array(
        'title_reply'          => '发表评论',
        'title_reply_to'       => '回复给 %s',
        'cancel_reply_link'    => '取消回复',
        'label_submit'         => '提交评论',
        'comment_field'        => '<p class="comment-form-comment"><label for="comment">评论内容</label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>',
        'must_log_in'          => '<p class="must-log-in">' . sprintf('您必须<a href="%s">登录</a>才能发表评论。', wp_login_url(apply_filters('the_permalink', get_permalink()))) . '</p>',
        'logged_in_as'         => '<p class="logged-in-as">' . sprintf('已登录为 <a href="%1$s">%2$s</a>。<a href="%3$s">登出？</a>', get_edit_user_link(), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink()))) . '</p>',
        'comment_notes_before' => '<p class="comment-notes">您的邮箱地址不会被公开。</p>',
        'class_submit'         => 'submit-button',
        'action'               => admin_url('admin-ajax.php')
    ));
    ?>
</div> 