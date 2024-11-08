<?php
/**
 * Template Name: Edit Post
 */

require_once get_template_directory() . '/inc/user-center-functions.php';
require_login();

$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$post = get_post($post_id);

// 检查文章是否存在
if (!$post) {
    wp_redirect(home_url());
    exit;
}

// 检查用户权限（允许所有登录用户编辑）
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink($post_id)));
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
        <div class="edit-post-container">
            <div class="edit-guide">
                <h3>编辑文章</h3>
                <p>您正在编辑《<?php echo esc_html($post->post_title); ?>》</p>
                
                <?php
                // 显示待审核的修订
                $pending_revisions = wp_get_post_revisions($post->ID, array(
                    'post_status' => 'pending'
                ));
                if ($pending_revisions) {
                    echo '<div class="pending-revisions-notice">';
                    echo '<p>此文章当前有待审核的修改：</p>';
                    echo '<ul>';
                    foreach ($pending_revisions as $revision) {
                        $author = get_userdata($revision->post_author);
                        $edit_summary = get_post_meta($revision->ID, '_edit_summary', true);
                        echo sprintf(
                            '<li>%s 由 %s 提交的修改%s</li>',
                            get_the_modified_time('Y-m-d H:i', $revision),
                            $author->display_name,
                            $edit_summary ? '：' . esc_html($edit_summary) : ''
                        );
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                ?>

                <div class="edit-guidelines">
                    <h4>编辑指南：</h4>
                    <ul>
                        <li>请确保您的修改是对文章内容的改进或补充</li>
                        <li>修改后需要等待管理员审核</li>
                        <li>请在修改说明中简要说明您的修改内容</li>
                        <li>恶意修改将被拒绝并可能被禁止编辑</li>
                    </ul>
                </div>
            </div>

            <form id="post-edit-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="edit_post">
                <?php wp_nonce_field('edit_post', 'edit_post_nonce'); ?>
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                
                <div class="form-group">
                    <label for="post_title">文章标题</label>
                    <input type="text" id="post_title" name="post_title" required 
                           value="<?php echo esc_attr($post->post_title); ?>">
                </div>

                <div class="form-group">
                    <label for="post_content">文章内容</label>
                    <div class="editor-container">
                        <div class="editor-section">
                            <textarea id="post_content" name="post_content" required><?php echo esc_textarea($post->post_content); ?></textarea>
                        </div>
                        <div class="preview-section">
                            <div class="preview-header">预览</div>
                            <div id="markdown-preview" class="markdown-preview"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_summary">修改说明</label>
                    <textarea id="edit_summary" name="edit_summary" required
                              placeholder="请简要说明您的修改内容，例如：'修正错别字'、'补充新内容'等"></textarea>
                </div>

                <button type="submit" class="submit-button">提交修改</button>
            </form>

            <!-- 显示修订历史 -->
            <div class="revision-history-section">
                <h3>修订历史</h3>
                <?php
                $revisions = wp_get_post_revisions($post_id);
                if ($revisions) :
                ?>
                    <ul class="revision-list">
                        <?php foreach ($revisions as $revision) : ?>
                            <li class="revision-item">
                                <div class="revision-meta">
                                    <?php
                                    $author = get_user_by('id', $revision->post_author);
                                    echo sprintf(
                                        '由 %s 于 %s 修改',
                                        $author->display_name,
                                        get_the_modified_time('Y-m-d H:i', $revision)
                                    );
                                    ?>
                                </div>
                                <div class="revision-summary">
                                    <?php echo get_post_meta($revision->ID, '_edit_summary', true); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>暂无修订记录</p>
                <?php endif; ?>
            </div>
        </div>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php get_footer(); ?> 