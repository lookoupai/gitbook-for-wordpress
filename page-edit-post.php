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

<script type="text/javascript">
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    var wpApiSettings = {
        nonce: "<?php echo wp_create_nonce('wp_rest'); ?>"
    };
</script>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>

    <main id="main" class="site-main right-content">
        <div class="edit-post-container">
            <?php if ($post) : ?>
                <form id="edit-post-form" method="post">
                    <?php wp_nonce_field('edit_post', 'edit_post_nonce'); ?>
                    <input type="hidden" name="action" value="edit_post">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

                    <div class="form-group">
                        <label for="post_title">标题</label>
                        <input type="text" name="post_title" id="post_title" 
                               value="<?php echo esc_attr($post->post_title); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="post_content">内容</label>
                        <div id="vditor"></div>
                        <input type="hidden" name="post_content" id="post_content">
                    </div>

                    <div class="form-group">
                        <label for="post_category">分类</label>
                        <?php 
                        wp_dropdown_categories(array(
                            'hide_empty' => 0,
                            'show_option_none' => '选择分类',
                            'option_none_value' => '',
                            'selected' => get_the_category($post_id)[0]->term_id
                        )); 
                        ?>
                    </div>

                    <div class="form-group">
                        <label for="post_tags">标签</label>
                        <input type="text" name="post_tags" id="post_tags" 
                               value="<?php echo get_the_tag_list('', ', ', '', $post_id); ?>">
                    </div>

                    <button type="submit" class="submit-button">提交修改</button>
                </form>
            <?php else : ?>
                <p>文章不存在或您没有权限编辑。</p>
            <?php endif; ?>
        </div>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<script>
// 初始化编辑器的值和配置
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
var editPostNonce = "<?php echo wp_create_nonce('edit_post'); ?>";
var initValue = <?php echo json_encode($post->post_content); ?>;
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vditor/dist/index.css" />
<script src="https://cdn.jsdelivr.net/npm/vditor/dist/index.min.js"></script>

<?php get_footer(); ?> 