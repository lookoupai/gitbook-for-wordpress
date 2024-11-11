<?php
/**
 * Template Name: Submit Post
 */

// 检查登录状态
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// 处理表单提交
if (isset($_POST['tougao_form']) && $_POST['tougao_form'] == 'send') {
    // 防止重复提交
    if (isset($_COOKIE["tougao"]) && (time() - $_COOKIE["tougao"]) < 120) {
        wp_die('您投稿太频繁了，请稍后再试！');
    }

    // 获取表单数据
    $title = isset($_POST['post_title']) ? sanitize_text_field($_POST['post_title']) : '';
    $content = isset($_POST['post_content']) ? wp_kses_post($_POST['post_content']) : '';
    $category = isset($_POST['cat']) ? intval($_POST['cat']) : 0;
    $tags = isset($_POST['post_tags']) ? sanitize_text_field($_POST['post_tags']) : '';

    // 验证数据
    if (empty($title)) {
        wp_die('标题不能为空');
    }
    if (empty($content)) {
        wp_die('内容不能为空');
    }
    if (empty($category)) {
        wp_die('请选择分类');
    }

    // 创建文章
    $post_data = array(
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'pending',
        'post_author'   => get_current_user_id(),
        'post_type'     => 'post',
        'post_category' => array($category)
    );

    // 插入文章
    $post_id = wp_insert_post($post_data);

    if ($post_id) {
        // 设置标签
        if (!empty($tags)) {
            wp_set_post_tags($post_id, $tags);
        }

        // 设置Cookie防止重复提交
        setcookie("tougao", time(), time()+120);

        // 直接重定向到"我的文章"页面
        wp_redirect(home_url('/user-center?tab=posts&submitted=1'));
        exit;
    } else {
        wp_die('投稿失败，请稍后重试');
    }
}

get_header();
?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>

    <main id="main" class="site-main right-content">
        <div class="submit-post-container">
            <div class="submission-guide">
                <h3>投稿指南</h3>
                <p>请按照以下步骤完成投稿：</p>
                <ol>
                    <li>填写文章标题</li>
                    <li>使用 Markdown 编辑器编写内容（支持实时预览）</li>
                    <li>选择适当的分类和标签</li>
                    <li>提交后等待管理员审核</li>
                </ol>
            </div>

            <form id="post-submission-form" method="post">
                <input type="hidden" name="tougao_form" value="send">
                
                <div class="form-group">
                    <label for="post_title">文章标题</label>
                    <input type="text" id="post_title" name="post_title" required 
                           placeholder="请输入文章标题">
                </div>

                <div class="form-group">
                    <label for="post_content">文章内容（支持 Markdown 格式）</label>
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
                            <textarea id="post_content" name="post_content" required
                                    placeholder="在这里输入文章内容，支持 Markdown 格式"></textarea>
                        </div>
                        <div class="preview-section">
                            <div class="preview-header">预览</div>
                            <div id="markdown-preview" class="markdown-preview"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="post_category">分类</label>
                    <?php wp_dropdown_categories(array(
                        'hide_empty' => 0,
                        'show_option_none' => '选择分类',
                        'option_none_value' => '',
                        'required' => true
                    )); ?>
                </div>

                <div class="form-group">
                    <label for="post_tags">标签</label>
                    <input type="text" id="post_tags" name="post_tags" 
                           placeholder="用逗号分隔多个标签，如：标签1, 标签2">
                </div>

                <button type="submit" class="submit-button">提交文章</button>
            </form>
        </div>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<style>
.submit-post-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
}

.notice {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.notice-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
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
// 保持原有的Markdown编辑器相关函数不变
</script>

<?php get_footer(); ?> 