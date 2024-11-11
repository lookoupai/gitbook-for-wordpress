<?php
/*
Template Name: 编辑文章
*/

// 检查用户是否登录
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$post = get_post($post_id);

// 检查文章是否存在
if (!$post) {
    wp_die('文章不存在');
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
            <h1>编辑文章</h1>
            <p>您正在编辑：<?php echo esc_html($post->post_title); ?></p>

            <?php
            // 显示待审核的修订
            $pending_revisions = get_posts(array(
                'post_type' => 'revision',
                'post_status' => 'pending',
                'post_parent' => $post_id
            ));

            if ($pending_revisions) {
                echo '<div class="notice notice-warning">';
                echo '<p>此文章当前有待审核的修改。在这些修改被审核之前，新的修改可能会被拒绝。</p>';
                echo '</div>';
            }
            ?>

            <?php
            if (isset($_GET['edited']) && $_GET['edited'] == '1') {
                echo '<div class="notice notice-success"><p>文章修改已提交，等待审核。</p></div>';
            }
            ?>

            <form id="edit-post-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('edit_post', 'edit_post_nonce'); ?>
                <input type="hidden" name="action" value="edit_post">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

                <div class="form-group">
                    <label for="post_title">标题</label>
                    <input type="text" name="post_title" id="post_title" 
                           value="<?php echo esc_attr($post->post_title); ?>" required>
                </div>

                <div class="form-group">
                    <label for="post_content">内容（支持 Markdown 格式）</label>
                    <div class="editor-container">
                        <div class="editor-section">
                            <div class="markdown-toolbar">
                                <button type="button" onclick="insertMarkdown('**', '**', '粗体文本')" title="粗体">B</button>
                                <button type="button" onclick="insertMarkdown('*', '*', '斜体文本')" title="斜体">I</button>
                                <button type="button" onclick="insertMarkdown('`', '`', '代码')" title="代码">Code</button>
                                <button type="button" onclick="insertMarkdown('\n```\n', '\n```\n', '代码块')" title="代码块">CodeBlock</button>
                                <button type="button" onclick="insertMarkdown('[', '](url)', '链接文本')" title="链接">Link</button>
                                <button type="button" onclick="insertMarkdown('> ', '', '引用文本')" title="引用">Quote</button>
                            </div>
                            <textarea name="post_content" id="post_content" required><?php echo esc_textarea($post->post_content); ?></textarea>
                        </div>
                        <div class="preview-section">
                            <div class="preview-header">预览</div>
                            <div id="markdown-preview" class="markdown-preview"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_summary">修改说明</label>
                    <textarea name="edit_summary" id="edit_summary" required
                              placeholder="请简要说明您的修改内容（例如：修正错别字、补充新内容等）"></textarea>
                </div>

                <div class="form-submit">
                    <button type="submit" class="button button-primary">提交修改</button>
                    <a href="<?php echo get_permalink($post_id); ?>" class="button">取消</a>
                </div>
            </form>
        </div>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<style>
.edit-post-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input[type="text"],
.form-group textarea:not(#post_content) {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.editor-container {
    display: flex;
    gap: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}

.editor-section, .preview-section {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.editor-section {
    border-right: 1px solid #ddd;
}

.markdown-toolbar {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    background: #f5f5f5;
}

.markdown-toolbar button {
    margin-right: 5px;
    padding: 5px 10px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: pointer;
}

.markdown-toolbar button:hover {
    background: #e9ecef;
}

#post_content {
    flex: 1;
    min-height: 500px;
    padding: 10px;
    border: none;
    resize: none;
    font-family: monospace;
}

.preview-header {
    padding: 10px;
    background: #f5f5f5;
    border-bottom: 1px solid #ddd;
    font-weight: bold;
}

.markdown-preview {
    flex: 1;
    padding: 10px;
    overflow-y: auto;
    min-height: 500px;
}

.notice {
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.notice-warning {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    color: #856404;
}

.form-submit {
    margin-top: 20px;
}

.form-submit .button {
    margin-right: 10px;
}

@media (max-width: 768px) {
    .editor-container {
        flex-direction: column;
    }
    
    .editor-section {
        border-right: none;
        border-bottom: 1px solid #ddd;
    }
}
</style>

<script>
function insertMarkdown(prefix, suffix, placeholder) {
    const textarea = document.getElementById('post_content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const before = text.substring(0, start);
    const selected = text.substring(start, end);
    const after = text.substring(end);

    const insertion = selected || placeholder;
    textarea.value = before + prefix + insertion + suffix + after;
    
    const newPosition = start + prefix.length + insertion.length;
    textarea.setSelectionRange(newPosition, newPosition);
    textarea.focus();
    
    // 触发预览更新
    updatePreview();
}

function updatePreview() {
    const content = document.getElementById('post_content').value;
    const preview = document.getElementById('markdown-preview');
    
    if (typeof markdownit === 'function') {
        const md = window.markdownit({
            html: true,
            linkify: true,
            typographer: true,
            breaks: true
        });
        preview.innerHTML = md.render(content);
    } else {
        preview.textContent = '加载 Markdown 渲染器中...';
    }
}

// 添加实时预览功能
document.getElementById('post_content').addEventListener('input', updatePreview);

// 页面加载时初始化预览
document.addEventListener('DOMContentLoaded', function() {
    // 等待 markdown-it 加载完成
    function checkMarkdownIt() {
        if (typeof markdownit === 'function') {
            updatePreview();
        } else {
            setTimeout(checkMarkdownIt, 100);
        }
    }
    checkMarkdownIt();
});
</script>

<?php get_footer(); ?> 