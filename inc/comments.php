<?php
// 评论相关功能
if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/notifications.php';

// 修改评论模板函数
function custom_comment_template($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    ?>
    <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
        <article class="comment-body">
            <footer class="comment-meta">
                <div class="comment-author vcard">
                    <?php echo get_avatar($comment, 60); ?>
                    <?php printf('<b class="fn">%s</b>', get_comment_author_link()); ?>
                </div>

                <div class="comment-metadata">
                    <time datetime="<?php comment_time('c'); ?>">
                        <?php printf('%1$s %2$s', get_comment_date(), get_comment_time()); ?>
                    </time>
                    <?php
                    if (is_user_logged_in() && 
                        ($comment->user_id === get_current_user_id() || current_user_can('manage_options'))) {
                        echo ' | <a href="javascript:void(0);" onclick="editComment(' . $comment->comment_ID . ', \'' . 
                             esc_attr(str_replace(array("\r\n", "\r", "\n"), "\\n", $comment->comment_content)) . 
                             '\')" class="comment-edit-link">编辑</a>';
                        
                        if (current_user_can('manage_options')) {
                            echo ' | <a href="javascript:void(0);" onclick="deleteComment(' . 
                                 $comment->comment_ID . ')" class="comment-delete-link">删除</a>';
                        }
                    }
                    ?>
                </div>

                <?php if ('0' == $comment->comment_approved) : ?>
                    <p class="comment-awaiting-moderation">您的评论正在等待审核。</p>
                <?php endif; ?>
            </footer>

            <div class="comment-content markdown-body" id="comment-content-<?php echo $comment->comment_ID; ?>">
                <?php comment_text(); ?>
            </div>

            <div class="comment-edit-form" id="comment-edit-form-<?php echo $comment->comment_ID; ?>" style="display: none;">
                <textarea id="comment-edit-textarea-<?php echo $comment->comment_ID; ?>" class="comment-edit-textarea"></textarea>
                <div class="comment-edit-buttons">
                    <button onclick="saveComment(<?php echo $comment->comment_ID; ?>)" class="save-comment">保存</button>
                    <button onclick="cancelEdit(<?php echo $comment->comment_ID; ?>)" class="cancel-edit">取消</button>
                </div>
            </div>

            <div class="reply">
                <?php
                comment_reply_link(array_merge($args, array(
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth']
                )));
                ?>
            </div>
        </article>
    </li>
    <?php
}

// 修改评论表单
function custom_comment_form_defaults($defaults) {
    // 加载 Markdown 编辑器脚本
    wp_enqueue_script('markdown-editor', get_template_directory_uri() . '/js/markdown-editor.js', array('jquery'), time(), true);

    // 修改评论框
    $defaults['comment_field'] = '
        <p class="comment-form-comment">
            <label for="comment">评论内容</label>
            <div class="markdown-toolbar">
                <button type="button" onclick="insertMarkdown(\'**\', \'**\', \'粗体文本\')" title="粗体">B</button>
                <button type="button" onclick="insertMarkdown(\'*\', \'*\', \'斜体文本\')" title="斜体">I</button>
                <button type="button" onclick="insertMarkdown(\'`\', \'`\', \'代码\')" title="代码">Code</button>
                <button type="button" onclick="insertMarkdown(\'\\n```\\n\', \'\\n```\\n\', \'代码块\')" title="代码块">CodeBlock</button>
                <button type="button" onclick="insertMarkdown(\'[\', \'](url)\', \'链接文本\')" title="链接">Link</button>
                <button type="button" onclick="insertMarkdown(\'> \', \'\', \'引用文本\')" title="引用">Quote</button>
            </div>
            <textarea id="comment" name="comment" cols="45" rows="8" required></textarea>
            <div class="markdown-preview" id="comment-preview"></div>
            <button type="button" onclick="previewComment()" class="preview-button">预览</button>
        </p>
    ';

    return $defaults;
}
add_filter('comment_form_defaults', 'custom_comment_form_defaults');

// 处理评论编辑
function handle_comment_edit() {
    if (!isset($_POST['comment_edit_nonce']) || 
        !wp_verify_nonce($_POST['comment_edit_nonce'], 'edit_comment')) {
        return;
    }

    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $comment = get_comment($comment_id);

    // 检查权限
    if (!$comment || $comment->user_id != get_current_user_id()) {
        wp_die('您没有权限编辑此评论');
    }

    $comment_content = isset($_POST['comment_content']) ? 
                      wp_kses_post($_POST['comment_content']) : '';

    // 更新评论
    $data = array(
        'comment_ID' => $comment_id,
        'comment_content' => $comment_content,
        'comment_approved' => '0' // 编辑后需要重新审核
    );

    $result = wp_update_comment($data);

    if ($result) {
        // 添加通知
        add_user_notification(
            get_current_user_id(),
            '您的评论已更新，等待审核。',
            'comment_edited'
        );

        // 重定向回原文章
        wp_redirect(get_comment_link($comment_id));
        exit;
    }

    wp_die('更新评论失败');
}
add_action('admin_post_edit_comment', 'handle_comment_edit');

// 处理评论删除
function handle_comment_delete() {
    check_ajax_referer('user-center-nonce', 'nonce');

    $comment_id = intval($_POST['comment_id']);
    $comment = get_comment($comment_id);
    
    // 检查权限
    if (!$comment || $comment->user_id != get_current_user_id()) {
        wp_send_json_error('没有权限删除此评论');
        return;
    }

    if (wp_delete_comment($comment_id, true)) {
        wp_send_json_success('评论已删除');
    } else {
        wp_send_json_error('删除失败');
    }
}
add_action('wp_ajax_delete_comment', 'handle_comment_delete');

// 添加评论样式
function add_comment_styles() {
    if (is_singular() && comments_open()) {
        ?>
        <style>
        .markdown-toolbar {
            padding: 5px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-bottom: 0;
        }
        .markdown-toolbar button {
            padding: 5px 10px;
            margin-right: 5px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 3px;
            cursor: pointer;
        }
        .markdown-toolbar button:hover {
            background: #635753;
            color: #fff;
        }
        .markdown-preview {
            display: none;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }
        .preview-button {
            margin-top: 10px;
            padding: 5px 15px;
            background: #635753;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        #comment {
            border-radius: 0 0 4px 4px;
            margin-top: 0;
        }
        </style>
        <?php
    }
}
add_action('wp_head', 'add_comment_styles');

// 添加评论 Markdown 支持
function render_comment_markdown($content) {
    // 加载 markdown-it
    wp_enqueue_script('markdown-it', 'https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js', array(), null, true);
    
    // 添加渲染脚本
    static $script_added = false;
    if (!$script_added) {
        add_action('wp_footer', function() {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                function initMarkdown() {
                    if (typeof markdownit === 'function') {
                        const md = window.markdownit({
                            html: true,
                            linkify: true,
                            typographer: true,
                            breaks: true
                        });

                        // 渲染所有评论内容
                        document.querySelectorAll('.comment-content').forEach(function(element) {
                            const originalContent = element.textContent.trim();
                            if (originalContent) {
                                try {
                                    element.innerHTML = md.render(originalContent);
                                } catch (e) {
                                    console.error('Markdown rendering error:', e);
                                }
                            }
                        });
                    } else {
                        setTimeout(initMarkdown, 100);
                    }
                }
                initMarkdown();
            });
            </script>
            <?php
        }, 99);
        $script_added = true;
    }

    return '<div class="comment-content markdown-body">' . $content . '</div>';
}

// 移除默认的评论内容过滤器
remove_filter('comment_text', 'wpautop', 30);
remove_filter('comment_text', 'wptexturize', 10);

// 添加 Markdown 渲染过滤器
add_filter('comment_text', 'render_comment_markdown', 20);
?> 