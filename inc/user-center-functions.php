<?php
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

// 检查用户登录状态
function require_login() {
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
}

// 获取用户通知
function get_user_notifications($user_id) {
    $notifications = get_user_meta($user_id, 'user_notifications', true);
    if (!is_array($notifications)) {
        return array();
    }
    return $notifications;
}

// 标记通知为已读
function mark_notification_as_read($user_id, $notification_index) {
    $notifications = get_user_notifications($user_id);
    if (isset($notifications[$notification_index])) {
        $notifications[$notification_index]['read'] = true;
        update_user_meta($user_id, 'user_notifications', $notifications);
        return true;
    }
    return false;
}

// 删除通知
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
 * 创建用户通知表
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

// 注册用户中心页面
function register_user_center_page() {
    if (!get_page_by_path('user-center')) {
        wp_insert_post([
            'post_title' => '用户中心',
            'post_name' => 'user-center',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
            'post_author' => 1,
            'page_template' => 'page-user-center.php'
        ]);
    }
}
add_action('after_switch_theme', 'register_user_center_page');

// 处理个人资料更新
function handle_profile_update() {
    if (!isset($_POST['profile_nonce']) || 
        !wp_verify_nonce($_POST['profile_nonce'], 'update_profile')) {
        return;
    }

    $user_id = get_current_user_id();
    
    // 更新用户信息
    $userdata = array(
        'ID' => $user_id,
        'display_name' => sanitize_text_field($_POST['display_name']),
        'user_email' => sanitize_email($_POST['user_email'])
    );
    
    $result = wp_update_user($userdata);
    
    if (!is_wp_error($result)) {
        update_user_meta($user_id, 'description', 
                        sanitize_textarea_field($_POST['description']));
        
        add_user_notification($user_id, 'profile_updated', 
                            '您的个人资料已更新成功。');
    }
}
add_action('init', 'handle_profile_update');

// 处理头像上传
function handle_avatar_upload() {
    if (!isset($_FILES['user_avatar']) || 
        !wp_verify_nonce($_POST['avatar_nonce'], 'update_avatar')) {
        return;
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $user_id = get_current_user_id();
    $avatar_id = media_handle_upload('user_avatar', 0);

    if (is_wp_error($avatar_id)) {
        add_user_notification($user_id, 'avatar_upload_failed', '头像上传失败：' . $avatar_id->get_error_message());
        return;
    }

    // 保存头像ID到用户meta
    update_user_meta($user_id, 'user_avatar', $avatar_id);
    add_user_notification($user_id, 'avatar_updated', '头像更新成功！');
}
add_action('init', 'handle_avatar_upload');

// 处理密码修改
function handle_password_change() {
    if (!isset($_POST['password_nonce']) || 
        !wp_verify_nonce($_POST['password_nonce'], 'change_password')) {
        return;
    }

    $user = wp_get_current_user();
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 验证当前密码
    if (!wp_check_password($current_password, $user->user_pass, $user->ID)) {
        add_user_notification($user->ID, 'password_change_failed', '当前密码不正确');
        return;
    }

    // 验证新密码
    if ($new_password !== $confirm_password) {
        add_user_notification($user->ID, 'password_change_failed', '两次输入的新密码不一致');
        return;
    }

    // 更新密码
    wp_set_password($new_password, $user->ID);
    
    // 重新登录用户
    wp_set_auth_cookie($user->ID);
    
    add_user_notification($user->ID, 'password_changed', '密码修改成功！');
}
add_action('init', 'handle_password_change');

// 自定义头像显示
function get_custom_avatar($avatar, $id_or_email, $size, $default, $alt) {
    $user = false;

    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
        $user = get_user_by('id', $user_id);
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $user = get_user_by('id', (int) $id_or_email->user_id);
        }
    } else {
        $user = get_user_by('email', $id_or_email);
    }

    if ($user && is_object($user)) {
        $avatar_id = get_user_meta($user->ID, 'user_avatar', true);
        if ($avatar_id) {
            $image = wp_get_attachment_image_src($avatar_id, array($size, $size));
            if ($image) {
                $avatar = "<img alt='{$alt}' src='{$image[0]}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
            }
        }
    }

    return $avatar;
}
add_filter('get_avatar', 'get_custom_avatar', 10, 5);

// 处理协作编辑提交
function handle_collaborative_edit() {
    if (!isset($_POST['edit_post_nonce']) || 
        !wp_verify_nonce($_POST['edit_post_nonce'], 'edit_post')) {
        return;
    }

    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    
    // 检查文章是否存在
    if (!$post) {
        return;
    }

    // 创建修订版本
    $post_data = array(
        'ID' => $post_id,
        'post_title' => sanitize_text_field($_POST['post_title']),
        'post_content' => wp_kses_post($_POST['post_content']),
        'post_status' => 'pending-revision', // 自定义状态
    );

    // 保存修订版本
    $revision_id = wp_save_post_revision($post_id);

    if ($revision_id) {
        // 保存修改说明
        update_post_meta($revision_id, '_edit_summary', 
            sanitize_textarea_field($_POST['edit_summary']));

        // 添加通知
        add_user_notification(
            get_current_user_id(),
            'edit_submitted',
            sprintf('您对文章《%s》的修改已提交，等待审核。', $post->post_title)
        );

        // 通知管理员
        $admin_email = get_option('admin_email');
        $subject = '有新的文章修改待审核';
        $message = sprintf(
            '文章《%s》有新的修改待审核。\n修改者：%s\n修改说明：%s',
            $post->post_title,
            wp_get_current_user()->display_name,
            $_POST['edit_summary']
        );
        wp_mail($admin_email, $subject, $message);

        wp_redirect(add_query_arg('edited', '1', get_permalink($post_id)));
        exit;
    }
}
add_action('init', 'handle_collaborative_edit');

// 添加自定义文章状态
function add_custom_post_status(){
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

// 处理评论编辑
function handle_comment_edit() {
    if (!isset($_POST['comment_edit_nonce']) || 
        !wp_verify_nonce($_POST['comment_edit_nonce'], 'edit_comment')) {
        return;
    }

    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $comment = get_comment($comment_id);

    // 检查权限
    if (!$comment || $comment->user_id != get_current_user_id()) {
        wp_die('您没有权限编辑此评论');
    }

    $comment_content = isset($_POST['comment_content']) ? 
                      wp_kses_post($_POST['comment_content']) : '';

    // 更新评论
    $data = array(
        'comment_ID' => $comment_id,
        'comment_content' => $comment_content,
        'comment_approved' => '0' // 编辑后需要重新审核
    );

    $result = wp_update_comment($data);

    if ($result) {
        // 添加通知
        add_user_notification(
            get_current_user_id(),
            '您的评论已更新，等待审核。',
            'comment_edited'
        );

        // 重定向回原文章
        wp_redirect(get_comment_link($comment_id));
        exit;
    }

    wp_die('更新评论失败');
}
add_action('admin_post_edit_comment', 'handle_comment_edit');

// 处理评论删除
function handle_comment_delete() {
    check_ajax_referer('user-center-nonce', 'nonce');

    $comment_id = intval($_POST['comment_id']);
    $comment = get_comment($comment_id);
    
    // 检查权限
    if (!$comment || $comment->user_id != get_current_user_id()) {
        wp_send_json_error('没有权限删除此评论');
        return;
    }

    if (wp_delete_comment($comment_id, true)) {
        wp_send_json_success('评论已删除');
    } else {
        wp_send_json_error('删除失败');
    }
}
add_action('wp_ajax_delete_comment', 'handle_comment_delete');

// 在主题激活时创建必要的数据库表
function create_user_center_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();

    // 用户通知表
    $table_name = $wpdb->prefix . 'user_notifications';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(50) NOT NULL,
        content text NOT NULL,
        is_read tinyint(1) DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'create_user_center_tables');