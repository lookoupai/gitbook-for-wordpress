<?php
if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/notifications.php';

// 检查用户登录状态
function require_login() {
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
}

// 确保用户中心页面使用正确的模板
function ensure_user_center_template($template) {
    if (is_page('user-center') || strpos($_SERVER['REQUEST_URI'], '/user-center') !== false) {
        $new_template = locate_template(array('page-user-center.php'));
        if (!empty($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'ensure_user_center_template', 99);

// 修改用户中心URL处理
function fix_user_center_url($url, $path) {
    if ($path === 'user-center') {
        return home_url('/user-center/');
    }
    return $url;
}
add_filter('page_link', 'fix_user_center_url', 10, 2);

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
        
        add_user_notification($user_id, '您的个人资料已更新成功。', 
                            'profile_updated');
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
        add_user_notification($user_id, '头像上传失败：' . $avatar_id->get_error_message(), 
                            'avatar_upload_failed');
        return;
    }

    // 保存头像ID到用户meta
    update_user_meta($user_id, 'user_avatar', $avatar_id);
    add_user_notification($user_id, '头像更新成功！', 'avatar_updated');
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
        add_user_notification($user->ID, '当前密码不正确', 'password_change_failed');
        return;
    }

    // 验证新密码
    if ($new_password !== $confirm_password) {
        add_user_notification($user->ID, '两次输入的新密码不一致', 'password_change_failed');
        return;
    }

    // 更新密码
    wp_set_password($new_password, $user->ID);
    
    // 重新登录用户
    wp_set_auth_cookie($user->ID);
    
    add_user_notification($user->ID, '密码修改成功！', 'password_changed');
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

/**
 * 获取文章状态的中文标签
 */
function get_post_status_label($status) {
    $status_labels = array(
        'publish'    => '已发布',
        'pending'    => '待审核',
        'draft'      => '草稿',
        'private'    => '私密',
        'trash'      => '已删除',
        'auto-draft' => '自动草稿',
        'inherit'    => '修订版本',
        'future'     => '定时发布',
        'pending-revision' => '等待审核的修改'
    );
    
    return isset($status_labels[$status]) ? $status_labels[$status] : $status;
}