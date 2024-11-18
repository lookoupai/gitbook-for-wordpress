<?php
// 投稿相关功能
if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/notifications.php';

// 处理投稿的AJAX函数
function ajax_handle_post_submission() {
    global $wpdb;
    
    // 验证和检查
    if (!isset($_POST['submit_post_nonce']) || !wp_verify_nonce($_POST['submit_post_nonce'], 'submit_post')) {
        wp_send_json_error(array('message' => '安全验证失败'));
        exit;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '请先登录'));
        exit;
    }

    try {
        // 预处理数据
        $title = sanitize_text_field($_POST['post_title']);
        $content = wp_kses_post($_POST['post_content']);
        $category = !empty($_POST['cat']) ? intval($_POST['cat']) : 0;
        $tags = !empty($_POST['post_tags']) ? sanitize_text_field($_POST['post_tags']) : '';
        $current_user_id = get_current_user_id();

        // 使用事务处理文章提交
        $wpdb->query('START TRANSACTION');

        // 1. 快速插入文章
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'pending',
            'post_author'   => $current_user_id,
            'post_type'     => 'post'
        );

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        // 2. 设置分类和标签
        if ($category) {
            wp_set_post_categories($post_id, array($category));
        }
        if ($tags) {
            wp_set_post_tags($post_id, $tags);
        }

        // 3. 提交事务
        $wpdb->query('COMMIT');

        // 4. 添加通知
        add_user_notification(
            $current_user_id,
            sprintf('您的文章《%s》已提交，等待审核。', $title),
            'post_submitted'
        );

        // 通知管理员
        $admin_users = get_users(array('role' => 'administrator'));
        foreach ($admin_users as $admin) {
            add_user_notification(
                $admin->ID,
                sprintf('新文章《%s》待审核', $title),
                'new_post'
            );
        }

        // 5. 返回成功响应
        wp_send_json_success(array(
            'message' => '文章提交成功，等待审核',
            'redirect_url' => home_url('/user-center?submitted=1')
        ));

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array(
            'message' => '投稿失败：' . $e->getMessage()
        ));
    }
    exit;
}
add_action('wp_ajax_handle_post_submission', 'ajax_handle_post_submission');

// 添加投稿页面资源
function add_submission_assets() {
    if (is_page_template('page-submit-post.php')) {
        // 加载 Markdown 编辑器
        wp_enqueue_script('markdown-it', 'https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js', array(), null, true);
        wp_enqueue_script('markdown-editor', get_template_directory_uri() . '/js/markdown-editor.js', array('jquery', 'markdown-it'), '1.0', true);
        
        // 加载样式
        wp_enqueue_style('submit-post-style', get_template_directory_uri() . '/assets/css/submit-post.css');
        
        // 本地化脚本
        wp_localize_script('markdown-editor', 'submitPost', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('submit_post_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'add_submission_assets');

// 处理表单提交
function handle_post_submission() {
    // 验证nonce
    if (!isset($_POST['submit_post_nonce']) || !wp_verify_nonce($_POST['submit_post_nonce'], 'submit_post')) {
        wp_die('安全验证失败');
    }

    if (!is_user_logged_in()) {
        wp_die('请先登录');
    }

    // 获取表单数据
    $title = sanitize_text_field($_POST['post_title']);
    $content = wp_kses_post($_POST['post_content']);
    $category = !empty($_POST['cat']) ? intval($_POST['cat']) : 0;
    $tags = !empty($_POST['post_tags']) ? sanitize_text_field($_POST['post_tags']) : '';

    // 创建文章
    $post_data = array(
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'pending',
        'post_author'   => get_current_user_id(),
        'post_type'     => 'post'
    );

    // 插入文章
    $post_id = wp_insert_post($post_data);

    if (!is_wp_error($post_id)) {
        // 设置分类和标签
        if ($category) {
            wp_set_post_categories($post_id, array($category));
        }
        if ($tags) {
            wp_set_post_tags($post_id, $tags);
        }

        // 添加通知
        add_user_notification(
            get_current_user_id(),
            sprintf('您的文章《%s》已提交，等待审核。', $title),
            'post_submitted'
        );

        // 重定向到成功页面
        wp_redirect(add_query_arg('submitted', '1', get_permalink(get_page_by_path('user-center'))));
        exit;
    } else {
        wp_die('投稿失败：' . $post_id->get_error_message());
    }
}
add_action('admin_post_submit_post', 'handle_post_submission');