<?php
// AI审核AJAX处理
if (!defined('ABSPATH')) exit;

/**
 * 手动触发AI审核
 */
function ajax_trigger_manual_ai_review() {
    // 添加调试日志
    if (current_user_can('administrator')) {
        error_log('尝试手动触发AI审核');
    }
    
    // 检查权限
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => '没有权限执行此操作'));
    }
    
    // 验证nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'ai-review-nonce')) {
        if (current_user_can('administrator')) {
            error_log('手动审核安全验证失败');
        }
        wp_send_json_error(array('message' => '安全验证失败'));
    }
    
    // 获取文章ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        if (current_user_can('administrator')) {
            error_log('手动审核无效的文章ID');
        }
        wp_send_json_error(array('message' => '无效的文章ID'));
    }
    
    $post = get_post($post_id);
    if (!$post) {
        if (current_user_can('administrator')) {
            error_log('手动审核文章不存在: ' . $post_id);
        }
        wp_send_json_error(array('message' => '文章不存在'));
    }
    
    // 检查AI Services插件状态
    if (!function_exists('ai_services')) {
        if (current_user_can('administrator')) {
            error_log('手动审核失败: AI Services插件未安装');
        }
        wp_send_json_error(array('message' => 'AI Services插件未安装'));
    }
    
    // 检查AI服务可用性
    if (!ai_services()->has_available_services()) {
        if (current_user_can('administrator')) {
            error_log('手动审核失败: 无可用的AI服务');
        }
        wp_send_json_error(array('message' => '未配置可用的AI服务，请先在AI Services插件中配置API密钥'));
    }
    
    // 根据文章类型决定使用哪个审核函数
    $result = false;
    if ($post->post_type === 'revision') {
        if (current_user_can('administrator')) {
            error_log('手动审核修改版本: ' . $post_id);
        }
        $result = ai_review_revision($post_id);
    } else {
        if (current_user_can('administrator')) {
            error_log('手动审核文章: ' . $post_id);
        }
        $result = ai_review_post($post_id);
    }
    
    if ($result) {
        if (current_user_can('administrator')) {
            error_log('手动审核完成: 分数=' . $result['score'] . ', 通过=' . ($result['passed'] ? 'true' : 'false'));
        }
        wp_send_json_success(array(
            'message' => 'AI审核完成',
            'score' => $result['score'],
            'feedback' => $result['feedback'],
            'passed' => $result['passed']
        ));
    } else {
        if (current_user_can('administrator')) {
            error_log('手动审核失败，未返回有效结果');
        }
        wp_send_json_error(array('message' => 'AI审核失败，请检查AI服务配置和日志'));
    }
}
add_action('wp_ajax_trigger_manual_ai_review', 'ajax_trigger_manual_ai_review');

/**
 * 管理员批准内容
 */
function ajax_admin_approve_content() {
    // 检查权限
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => '没有权限执行此操作'));
    }
    
    // 验证nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'admin-action-nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
    }
    
    // 获取文章ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => '无效的文章ID'));
    }
    
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(array('message' => '文章不存在'));
    }
    
    // 处理不同类型的内容
    if ($post->post_type === 'revision') {
        // 这是一个修订版本，需要合并到原文章
        $parent_post = get_post($parent_id);
        if (!$parent_post) {
            wp_send_json_error(array('message' => '原文章不存在'));
        }
        
        // 合并修改
        $merged_post_data = array(
            'ID' => $parent_id,
            'post_title' => $post->post_title,
            'post_content' => $post->post_content,
            'post_status' => 'publish'
        );
        
        $update_result = wp_update_post($merged_post_data);
        if (is_wp_error($update_result)) {
            wp_send_json_error(array('message' => '更新文章失败：' . $update_result->get_error_message()));
        }
        
        // 记录管理员批准
        update_post_meta($post_id, '_admin_approved', '1');
        update_post_meta($post_id, '_admin_approved_date', current_time('mysql'));
        update_post_meta($parent_id, '_last_admin_approved_revision', $post_id);
        
        // 发送通知
        $author_id = $post->post_author;
        add_user_notification(
            $author_id,
            sprintf('恭喜！您对文章《%s》的修改已被管理员批准。', $parent_post->post_title),
            'revision_approved_by_admin'
        );
        
        wp_send_json_success(array('message' => '修改已批准并合并到原文章'));
    } else {
        // 这是一个新文章，直接发布
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($update_result)) {
            wp_send_json_error(array('message' => '发布文章失败：' . $update_result->get_error_message()));
        }
        
        // 记录管理员批准
        update_post_meta($post_id, '_admin_approved', '1');
        update_post_meta($post_id, '_admin_approved_date', current_time('mysql'));
        
        // 发送通知
        $author_id = $post->post_author;
        add_user_notification(
            $author_id,
            sprintf('恭喜！您的文章《%s》已被管理员批准并发布。', $post->post_title),
            'post_approved_by_admin'
        );
        
        wp_send_json_success(array('message' => '文章已批准并发布'));
    }
}
add_action('wp_ajax_admin_approve_content', 'ajax_admin_approve_content');

/**
 * 管理员拒绝内容
 */
function ajax_admin_reject_content() {
    // 检查权限
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => '没有权限执行此操作'));
    }
    
    // 验证nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'admin-action-nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
    }
    
    // 获取文章ID和拒绝理由
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
    
    if (!$post_id) {
        wp_send_json_error(array('message' => '无效的文章ID'));
    }
    
    if (empty($reason)) {
        wp_send_json_error(array('message' => '请提供拒绝理由'));
    }
    
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(array('message' => '文章不存在'));
    }
    
    // 处理不同类型的内容
    if ($post->post_type === 'revision') {
        // 这是一个修订版本
        $parent_post = get_post($parent_id);
        if (!$parent_post) {
            wp_send_json_error(array('message' => '原文章不存在'));
        }
        
        // 标记为拒绝
        update_post_meta($post_id, '_admin_rejected', '1');
        update_post_meta($post_id, '_admin_rejected_date', current_time('mysql'));
        update_post_meta($post_id, '_rejection_reason', $reason);
        
        // 发送通知
        $author_id = $post->post_author;
        add_user_notification(
            $author_id,
            sprintf('您对文章《%s》的修改未被批准。理由：%s', $parent_post->post_title, $reason),
            'revision_rejected_by_admin'
        );
        
        wp_send_json_success(array('message' => '已拒绝该修改，并通知了作者'));
    } else {
        // 这是一个新文章，标记为拒绝
        update_post_meta($post_id, '_admin_rejected', '1');
        update_post_meta($post_id, '_admin_rejected_date', current_time('mysql'));
        update_post_meta($post_id, '_rejection_reason', $reason);
        
        // 更新状态为草稿
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));
        
        if (is_wp_error($update_result)) {
            wp_send_json_error(array('message' => '更新文章状态失败：' . $update_result->get_error_message()));
        }
        
        // 发送通知
        $author_id = $post->post_author;
        add_user_notification(
            $author_id,
            sprintf('您的文章《%s》未被批准。理由：%s', $post->post_title, $reason),
            'post_rejected_by_admin'
        );
        
        wp_send_json_success(array('message' => '已拒绝该文章，并通知了作者'));
    }
}
add_action('wp_ajax_admin_reject_content', 'ajax_admin_reject_content');

/**
 * 获取待处理的内容查询
 */
function get_pending_content_query() {
    // 添加调试日志
    if (current_user_can('administrator')) {
        error_log('执行get_pending_content_query查询待处理内容');
    }
    
    return new WP_Query(array(
        'post_type' => array('revision', 'post'),  // 同时查询修订版本和文章
        'post_status' => array('inherit', 'pending', 'draft'),  // 包含继承、待处理和草稿状态
        'posts_per_page' => 20,
        'orderby' => 'modified',  // 按修改时间排序
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'AND',
            // 移除了对_wp_revision_status和_edit_summary元数据的依赖
            // 简化条件，查询未被拒绝和未被批准的内容
            array(
                'relation' => 'OR',
                array(
                    'key' => '_admin_rejected',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_admin_rejected',
                    'value' => '0',
                    'compare' => '='
                )
            ),
            array(
                'relation' => 'OR',
                array(
                    'key' => '_admin_approved',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_admin_approved',
                    'value' => '0',
                    'compare' => '='
                )
            )
        )
    ));
}

/**
 * 获取已审核的内容查询
 */
function get_reviewed_content_query() {
    return new WP_Query(array(
        'post_type' => array('post'),
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => 20,
        'orderby' => 'modified',
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_ai_review_score',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => '_admin_approved',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => '_admin_rejected',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => '_ai_auto_approved',
                'value' => '1',
                'compare' => '='
            )
        )
    ));
} 