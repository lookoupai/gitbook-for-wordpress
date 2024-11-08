<?php
/**
 * Template Name: Submit Post
 */

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}
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
                <?php wp_nonce_field('submit_post', 'submit_post_nonce'); ?>
                
                <div class="form-group">
                    <label for="post_title">文章标题</label>
                    <input type="text" id="post_title" name="post_title" required 
                           placeholder="请输入文章标题">
                </div>

                <div class="form-group">
                    <label for="post_content">文章内容（支持 Markdown 格式）</label>
                    <div class="editor-container">
                        <div class="editor-section">
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

<?php get_footer(); ?> 