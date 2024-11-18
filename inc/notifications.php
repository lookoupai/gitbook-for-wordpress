<?php
// 通知系统功能
if (!defined('ABSPATH')) exit;

/**
 * 添加用户通知
 */
function add_user_notification($user_id, $message, $type = 'info') {
    $notifications = get_user_meta($user_id, 'user_notifications', true);
    if (!is_array($notifications)) {
        $notifications = array();
    }
    
    $notifications[] = array(
        'message' => $message,
        'type' => $type,
        'time' => current_time('mysql'),
        'read' => false
    );
    
    update_user_meta($user_id, 'user_notifications', $notifications);
}

/**
 * 获取用户通知
 */
function get_user_notifications($user_id) {
    $notifications = get_user_meta($user_id, 'user_notifications', true);
    if (!is_array($notifications)) {
        return array();
    }
    return $notifications;
}

/**
 * 标记通知为已读
 */
function mark_notification_as_read($user_id, $notification_index) {
    $notifications = get_user_notifications($user_id);
    if (isset($notifications[$notification_index])) {
        $notifications[$notification_index]['read'] = true;
        update_user_meta($user_id, 'user_notifications', $notifications);
        return true;
    }
    return false;
}

/**
 * 删除通知
 */
function delete_user_notification($user_id, $notification_index) {
    $notifications = get_user_notifications($user_id);
    if (isset($notifications[$notification_index])) {
        array_splice($notifications, $notification_index, 1);
        update_user_meta($user_id, 'user_notifications', $notifications);
        return true;
    }
    return false;
}

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

// AJAX处理函数
function ajax_mark_notification_read() {
    check_ajax_referer('user-notification-nonce', 'nonce');
    
    $index = intval($_POST['index']);
    $user_id = get_current_user_id();
    
    if (mark_notification_as_read($user_id, $index)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('标记已读失败');
    }
}
add_action('wp_ajax_mark_notification_read', 'ajax_mark_notification_read');

function ajax_delete_notification() {
    check_ajax_referer('user-notification-nonce', 'nonce');
    
    $index = intval($_POST['index']);
    $user_id = get_current_user_id();
    
    if (delete_user_notification($user_id, $index)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('删除失败');
    }
}
add_action('wp_ajax_delete_notification', 'ajax_delete_notification');