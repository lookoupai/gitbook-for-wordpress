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

            // 评论编辑和删除功能
            function editComment(commentId, content) {
                var $content = document.getElementById('comment-content-' + commentId);
                var $form = document.getElementById('comment-edit-form-' + commentId);
                var $textarea = document.getElementById('comment-edit-textarea-' + commentId);
                
                $content.style.display = 'none';
                $form.style.display = 'block';
                $textarea.value = content;
            }

            function saveComment(commentId) {
                var content = document.getElementById('comment-edit-textarea-' + commentId).value;
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edit_comment',
                        comment_id: commentId,
                        content: content,
                        nonce: '<?php echo wp_create_nonce("comment-action-nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var $content = document.getElementById('comment-content-' + commentId);
                            var $form = document.getElementById('comment-edit-form-' + commentId);
                            
                            $content.innerHTML = response.data.content;
                            $content.style.display = 'block';
                            $form.style.display = 'none';
                            
                            if (response.data.pending) {
                                alert('评论已更新，等待审核');
                            }
                        } else {
                            alert(response.data || '更新失败');
                        }
                    }
                });
            }

            function cancelEdit(commentId) {
                var $content = document.getElementById('comment-content-' + commentId);
                var $form = document.getElementById('comment-edit-form-' + commentId);
                
                $content.style.display = 'block';
                $form.style.display = 'none';
            }

            function deleteComment(commentId) {
                if (confirm('确定要删除这条评论吗？')) {
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'delete_comment',
                            comment_id: commentId,
                            nonce: '<?php echo wp_create_nonce("comment-action-nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                var $comment = document.getElementById('comment-' + commentId);
                                $comment.style.display = 'none';
                            } else {
                                alert(response.data || '删除失败');
                            }
                        }
                    });
                }
            }

            window.editComment = editComment;
            window.saveComment = saveComment;
            window.cancelEdit = cancelEdit;
            window.deleteComment = deleteComment;
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

// AJAX处理评论编辑
function ajax_edit_comment() {
    check_ajax_referer('comment-action-nonce', 'nonce');
    
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
    $user_id = get_current_user_id();
    
    $comment = get_comment($comment_id);
    
    // 检查权限
    if (!$comment || ($comment->user_id != $user_id && !current_user_can('moderate_comments'))) {
        wp_send_json_error('没有权限编辑此评论');
        return;
    }
    
    // 更新评论
    $result = wp_update_comment(array(
        'comment_ID' => $comment_id,
        'comment_content' => $content,
        'comment_approved' => current_user_can('moderate_comments') ? 1 : 0
    ));
    
    if ($result) {
        wp_send_json_success(array(
            'message' => '评论已更新',
            'content' => apply_filters('comment_text', $content),
            'pending' => !current_user_can('moderate_comments')
        ));
    } else {
        wp_send_json_error('更新评论失败');
    }
}
add_action('wp_ajax_edit_comment', 'ajax_edit_comment');

// AJAX处理评论删除
function ajax_delete_comment() {
    check_ajax_referer('comment-action-nonce', 'nonce');
    
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $user_id = get_current_user_id();
    
    $comment = get_comment($comment_id);
    
    // 检查权限
    if (!$comment || ($comment->user_id != $user_id && !current_user_can('moderate_comments'))) {
        wp_send_json_error('没有权限删除此评论');
        return;
    }
    
    // 删除评论
    $result = wp_delete_comment($comment_id, true);
    
    if ($result) {
        wp_send_json_success('评论已删除');
    } else {
        wp_send_json_error('删除评论失败');
    }
}
add_action('wp_ajax_delete_comment', 'ajax_delete_comment');

// 添加评论页面资源
function add_comment_assets() {
    if (is_singular() && comments_open()) {
        wp_enqueue_script('comment-actions', get_template_directory_uri() . '/assets/js/comment-actions.js', array('jquery'), '1.0', true);
        wp_localize_script('comment-actions', 'commentVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('comment-action-nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'add_comment_assets');
?> 