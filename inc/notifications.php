<?php
// 通知系统功能
if (!defined('ABSPATH')) exit;

/**
 * 获取分页的用户通知
 */
function get_user_notifications($user_id, $page = 1, $per_page = 10) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_notifications';
    
    // 确保表存在
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        create_notifications_table();
    }
    
    // 获取总数
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    // 计算偏移量
    $offset = ($page - 1) * $per_page;
    
    // 获取通知，按时间倒序排列
    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE user_id = %d 
         ORDER BY created_at DESC 
         LIMIT %d OFFSET %d",
        $user_id, $per_page, $offset
    ));
    
    return array(
        'notifications' => $notifications,
        'total' => (int)$total,
        'total_pages' => ceil($total / $per_page),
        'current_page' => $page
    );
}

/**
 * 标记所有通知为已读
 */
function mark_all_notifications_as_read($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_notifications';
    
    return $wpdb->update(
        $table_name,
        array('is_read' => 1),
        array('user_id' => $user_id),
        array('%d'),
        array('%d')
    );
}

/**
 * 标记单个通知为已读
 */
function mark_notification_as_read($user_id, $notification_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_notifications';
    
    return $wpdb->update(
        $table_name,
        array('is_read' => 1),
        array(
            'id' => $notification_id,
            'user_id' => $user_id
        ),
        array('%d'),
        array('%d', '%d')
    );
}

/**
 * 删除通知
 */
function delete_user_notification($user_id, $notification_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_notifications';
    
    return $wpdb->delete(
        $table_name,
        array(
            'id' => $notification_id,
            'user_id' => $user_id
        ),
        array('%d', '%d')
    );
}

/**
 * 添加用户通知
 */
function add_user_notification($user_id, $message, $type = 'info') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_notifications';
    
    // 确保表存在
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        create_notifications_table();
    }
    
    return $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'message' => $message,
            'type' => $type,
            'is_read' => 0,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%d', '%s')
    );
}

/**
 * 获取未读通知数量
 */
function get_unread_notifications_count($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_notifications';
    
    return (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE user_id = %d AND is_read = 0",
        $user_id
    ));
}

// AJAX处理函数
function ajax_mark_notification_read() {
    check_ajax_referer('user-notification-nonce', 'nonce');
    
    $notification_id = intval($_POST['notification_id']);
    $user_id = get_current_user_id();
    
    if (mark_notification_as_read($user_id, $notification_id)) {
        wp_send_json_success(array(
            'unread_count' => get_unread_notifications_count($user_id)
        ));
    } else {
        wp_send_json_error('标记已读失败');
    }
}
add_action('wp_ajax_mark_notification_read', 'ajax_mark_notification_read');

function ajax_mark_all_notifications_read() {
    check_ajax_referer('user-notification-nonce', 'nonce');
    
    $user_id = get_current_user_id();
    
    if (mark_all_notifications_as_read($user_id)) {
        wp_send_json_success(array(
            'unread_count' => 0
        ));
    } else {
        wp_send_json_error('标记全部已读失败');
    }
}
add_action('wp_ajax_mark_all_notifications_read', 'ajax_mark_all_notifications_read');

function ajax_delete_notification() {
    check_ajax_referer('user-notification-nonce', 'nonce');
    
    $notification_id = intval($_POST['notification_id']);
    $user_id = get_current_user_id();
    
    if (delete_user_notification($user_id, $notification_id)) {
        wp_send_json_success(array(
            'unread_count' => get_unread_notifications_count($user_id)
        ));
    } else {
        wp_send_json_error('删除失败');
    }
}
add_action('wp_ajax_delete_notification', 'ajax_delete_notification');

/**
 * 创建通知表
 */
function create_notifications_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'user_notifications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        message text NOT NULL,
        type varchar(50) NOT NULL DEFAULT 'info',
        is_read tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'create_notifications_table');