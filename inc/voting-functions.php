<?php
// 创建投票相关的数据表
function create_voting_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // 投票记录表
    $table_votes = $wpdb->prefix . 'community_votes';
    $sql_votes = "CREATE TABLE IF NOT EXISTS $table_votes (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        vote tinyint(1) NOT NULL,
        vote_time datetime DEFAULT CURRENT_TIMESTAMP,
        ip_address varchar(45) NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY user_id (user_id),
        UNIQUE KEY unique_vote (post_id,user_id)
    ) $charset_collate;";

    // IP限制表
    $table_ip = $wpdb->prefix . 'community_vote_ip_limits';
    $sql_ip = "CREATE TABLE IF NOT EXISTS $table_ip (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip_address varchar(45) NOT NULL,
        vote_count int(11) NOT NULL DEFAULT 0,
        last_vote datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY ip_address (ip_address)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_votes);
    dbDelta($sql_ip);
}

// 添加激活主题时创建表的钩子
register_activation_hook(__FILE__, 'create_voting_tables');

// 添加投票处理的AJAX函数
function handle_vote() {
    check_ajax_referer('voting-nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('请先登录');
        return;
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $vote = isset($_POST['vote']) ? intval($_POST['vote']) : 0;
    $user_id = get_current_user_id();
    
    $result = add_vote($post_id, $user_id, $vote);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    wp_send_json_success('投票成功');
}
add_action('wp_ajax_handle_vote', 'handle_vote');

// 检查用户投票权限
function check_user_voting_permission($user_id, $post_id) {
    // 检查用户是否已投票
    if (get_user_vote($user_id, $post_id)) {
        return new WP_Error('already_voted', '您已经对此文章投过票了');
    }
    
    // 检查注册时间
    $user = get_userdata($user_id);
    $register_time = strtotime($user->user_registered);
    $min_months = intval(get_option('voting_min_register_months', 3));
    $min_seconds = $min_months * 30 * 24 * 60 * 60;
    
    if ((time() - $register_time) < $min_seconds) {
        return new WP_Error('insufficient_time', 
            sprintf('需要注册满%d个月才能参与投票', $min_months)
        );
    }
    
    return true;
}

// 获取用户投票记录
function get_user_vote($user_id, $post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'community_votes';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND post_id = %d",
        $user_id,
        $post_id
    ));
}

// 获取文章投票统计
function get_post_votes($post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'community_votes';
    
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END) as approve,
            SUM(CASE WHEN vote = 0 THEN 1 ELSE 0 END) as reject
        FROM $table 
        WHERE post_id = %d",
        $post_id
    ));
    
    return array(
        'approve' => intval($stats->approve),
        'reject' => intval($stats->reject)
    );
}

// 获取投票用户列表
function get_voting_users_list($post_id, $vote_type) {
    global $wpdb;
    $table = $wpdb->prefix . 'community_votes';
    
    $users = $wpdb->get_col($wpdb->prepare(
        "SELECT user_id FROM $table WHERE post_id = %d AND vote = %d",
        $post_id,
        $vote_type
    ));
    
    $user_list = array();
    foreach ($users as $user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $user_list[] = $user->display_name;
        }
    }
    
    return implode(', ', $user_list);
}

// 添加投票
function add_vote($post_id, $user_id, $vote) {
    global $wpdb;
    $table = $wpdb->prefix . 'community_votes';
    
    // 检查权限
    $permission_check = check_user_voting_permission($user_id, $post_id);
    if (is_wp_error($permission_check)) {
        return $permission_check;
    }
    
    // 插入投票记录
    $result = $wpdb->insert(
        $table,
        array(
            'post_id' => $post_id,
            'user_id' => $user_id,
            'vote' => $vote,
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ),
        array('%d', '%d', '%d', '%s')
    );

    if ($result === false) {
        return new WP_Error('db_error', '投票失败，请稍后重试');
    }

    // 检查是否达到投票条件
    check_voting_completion($post_id);

    return true;
}

// 检查投票是否完成
function check_voting_completion($post_id) {
    $votes = get_post_votes($post_id);
    $total_votes = $votes['approve'] + $votes['reject'];
    $required_votes = intval(get_option('voting_votes_required', 10));
    $approve_ratio = floatval(get_option('voting_approve_ratio', 0.6));
    
    if ($total_votes >= $required_votes) {
        $actual_ratio = $votes['approve'] / $total_votes;
        $new_status = ($actual_ratio >= $approve_ratio) ? 'publish' : 'draft';
        
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => $new_status
        ));
        
        update_post_meta($post_id, '_voting_status', 'completed');
        update_post_meta($post_id, '_voting_result', $new_status === 'publish' ? 'approved' : 'rejected');
    }
}

// 管理员一票决定
function admin_vote_decision($post_id, $decision) {
    if (!current_user_can('manage_options')) {
        return new WP_Error('permission_denied', '只有管理员可以行使一票决定权');
    }
    
    // 更新文章状态
    $new_status = $decision ? 'publish' : 'draft';
    wp_update_post(array(
        'ID' => $post_id,
        'post_status' => $new_status
    ));
    
    // 更新投票状态
    update_post_meta($post_id, '_voting_status', 'completed');
    update_post_meta($post_id, '_admin_decision', $decision);
    
    return true;
}