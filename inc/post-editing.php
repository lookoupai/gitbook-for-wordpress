<?php
// 编辑相关功能
if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/notifications.php';

// 添加自定义文章状态
function add_custom_post_status() {
    register_post_status('pending-revision', array(
        'label'                     => '等待审核的修改',
        'public'                    => false,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('等待审核的修改 <span class="count">(%s)</span>',
                                             '等待审核的修改 <span class="count">(%s)</span>')
    ));
}
add_action('init', 'add_custom_post_status');

// 处理文章编辑提交
function handle_post_edit() {
    if (!isset($_POST['edit_post_nonce']) || !wp_verify_nonce($_POST['edit_post_nonce'], 'edit_post')) {
        wp_die('安全验证失败');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_title = isset($_POST['post_title']) ? sanitize_text_field($_POST['post_title']) : '';
    $post_content = isset($_POST['post_content']) ? wp_kses_post($_POST['post_content']) : '';
    $edit_summary = isset($_POST['edit_summary']) ? sanitize_text_field($_POST['edit_summary']) : '';

    // 获取原文章
    $post = get_post($post_id);
    if (!$post) {
        wp_die('文章不存在');
    }

    // 创建新的修订版本
    $revision_data = array(
        'post_type'    => 'revision',
        'post_title'   => $post_title,
        'post_content' => $post_content,
        'post_parent'  => $post_id,
        'post_author'  => get_current_user_id(),
        'post_status'  => 'pending'  // 修订版本状态为待审核
    );

    // 插入修订版本
    $revision_id = wp_insert_post($revision_data);

    if ($revision_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'post_edit_summaries';
        
        // 保存修改说明到自定义表
        $wpdb->insert(
            $table_name,
            array(
                'revision_id' => $revision_id,
                'post_id' => $post_id,
                'user_id' => get_current_user_id(),
                'summary' => $edit_summary
            ),
            array('%d', '%d', '%d', '%s')
        );
        
        // 通知管理员
        $admin_users = get_users(array('role' => 'administrator'));
        foreach ($admin_users as $admin) {
            add_user_notification(
                $admin->ID,
                sprintf('文章《%s》有新的编辑待审核', $post->post_title),
                'edit_post'
            );
        }

        // 通知编辑者
        add_user_notification(
            get_current_user_id(),
            sprintf('您对文章《%s》的修改已提交，等待管理员审核。', $post->post_title),
            'edit_submitted'
        );

        wp_redirect(add_query_arg('edited', '1', get_permalink($post_id)));
        exit;
    }

    wp_die('保存修订版本失败');
}
add_action('admin_post_edit_post', 'handle_post_edit');
add_action('admin_post_nopriv_edit_post', 'handle_post_edit');

// 添加编辑页面资源
function add_edit_post_assets() {
    if (is_page_template('page-edit-post.php')) {
        wp_enqueue_script('markdown-it', 'https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js', array(), null, true);
        wp_enqueue_style('edit-post-style', get_template_directory_uri() . '/assets/css/edit-post.css');
        wp_enqueue_script('edit-post-script', get_template_directory_uri() . '/assets/js/edit-post.js', array('jquery', 'markdown-it'), null, true);
        
        wp_localize_script('edit-post-script', 'editPost', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('edit-post-nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'add_edit_post_assets');

// 获取文章的最后更新时间
function get_last_update_time($post = null) {
    if (!$post) {
        global $post;
    }
    
    // 获取最新的已批准修订版本
    $revisions = wp_get_post_revisions($post->ID, array(
        'posts_per_page' => 1,
        'orderby' => 'post_modified',
        'order' => 'DESC',
        'post_status' => 'inherit'
    ));

    if (!empty($revisions)) {
        $revision = array_shift($revisions);
        return get_the_modified_time('Y-m-d H:i:s', $revision);
    }
    
    // 如果没有修订版本，返回文章的修改时间
    return get_the_modified_time('Y-m-d H:i:s', $post);
}

// 显示文章元信息
function display_post_meta($post) {
    $update_time = get_last_update_time($post);
    echo sprintf(
        '最后更新于：%s 作者：%s 分类：%s',
        $update_time,
        get_the_author_meta('display_name', $post->post_author),
        get_the_category_list(', ', '', $post->ID)
    );
}