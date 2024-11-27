<?php
// 投票功能
if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/notifications.php';

// 创建投票表
function create_voting_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // 先删除旧表
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}post_votes");
    
    // 创建新的投票表，修改唯一键约束
    $sql_votes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}post_votes (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        vote_type tinyint(1) NOT NULL,
        vote_for varchar(20) DEFAULT 'post',
        is_admin_decision tinyint(1) DEFAULT 0,
        vote_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY vote_unique (post_id, user_id, vote_for),
        KEY post_id (post_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_votes);
}

// 检查用户投票权限
function check_user_voting_permission($user_id, $post_id) {
    // 检查用户是否已投票
    if (get_user_vote($user_id, $post_id)) {
        return new WP_Error('already_voted', '您已经对此文章投过票了');
    }
    
    // 检查注册时间
    $min_months = get_option('voting_min_register_months', 3);
    $user = get_userdata($user_id);
    $register_date = strtotime($user->user_registered);
    $months_diff = (time() - $register_date) / (30 * 24 * 60 * 60);
    
    if ($months_diff < $min_months) {
        return new WP_Error('insufficient_time', 
            sprintf('需要注册满%d个月才能参与投票', $min_months));
    }
    
    return true;
}

// 添加投票
function add_vote($post_id, $user_id, $vote_type) {
    global $wpdb;
    
    $permission = check_user_voting_permission($user_id, $post_id);
    if (is_wp_error($permission)) {
        return $permission;
    }
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'post_votes',
        array(
            'post_id' => $post_id,
            'user_id' => $user_id,
            'vote_type' => $vote_type
        ),
        array('%d', '%d', '%d')
    );
    
    if ($result === false) {
        return new WP_Error('vote_failed', '投票失败');
    }
    
    // 检查是否达到投票阈值
    check_voting_threshold($post_id);
    
    return true;
}

// 获取用户投票
function get_user_vote($user_id, $post_id) {
    global $wpdb;
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT vote_type FROM {$wpdb->prefix}post_votes 
         WHERE user_id = %d AND post_id = %d",
        $user_id, $post_id
    ));
}

// 检查投票阈值
function check_voting_threshold($post_id) {
    global $wpdb;
    
    $required_votes = get_option('voting_votes_required', 10);
    $approve_ratio = get_option('voting_approve_ratio', 0.6);
    
    $votes = $wpdb->get_results($wpdb->prepare(
        "SELECT vote_type, COUNT(*) as count 
         FROM {$wpdb->prefix}post_votes 
         WHERE post_id = %d 
         GROUP BY vote_type",
        $post_id
    ));
    
    $total_votes = 0;
    $approve_votes = 0;
    
    foreach ($votes as $vote) {
        $total_votes += $vote->count;
        if ($vote->vote_type == 1) {
            $approve_votes = $vote->count;
        }
    }
    
    // 如果达到所需票数
    if ($total_votes >= $required_votes) {
        $ratio = $approve_votes / $total_votes;
        
        if ($ratio >= $approve_ratio) {
            // 通过投票
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish'
            ));
            
            // 通知作者
            $post = get_post($post_id);
            add_user_notification(
                $post->post_author,
                sprintf('您的文章《%s》已通过社区投票', $post->post_title),
                'post_approved'
            );
        } else {
            // 拒绝发布
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'draft'
            ));
            
            // 通知作者
            $post = get_post($post_id);
            add_user_notification(
                $post->post_author,
                sprintf('您的文章《%s》未通过社区投票', $post->post_title),
                'post_rejected'
            );
        }
    }
}

// AJAX处理投票
function handle_vote() {
    check_ajax_referer('voting-nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('请先登录');
        return;
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $vote = isset($_POST['vote']) ? intval($_POST['vote']) : 0;
    $vote_type = isset($_POST['vote_type']) ? sanitize_text_field($_POST['vote_type']) : '';
    $user_id = get_current_user_id();
    
    // 根据投票类型进行不同处理
    if ($vote_type === 'edit') {
        // 处理修改投票
        $result = add_edit_vote($post_id, $user_id, $vote);
    } else {
        // 处理新文章投票
        $result = add_vote($post_id, $user_id, $vote);
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    wp_send_json_success('投票成功');
}
add_action('wp_ajax_handle_vote', 'handle_vote');

// 添加修改投票函数
function add_edit_vote($post_id, $user_id, $vote_type) {
    global $wpdb;
    
    $permission = check_user_voting_permission($user_id, $post_id);
    if (is_wp_error($permission)) {
        return $permission;
    }
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'post_votes',
        array(
            'post_id' => $post_id,
            'user_id' => $user_id,
            'vote_type' => $vote_type,
            'vote_for' => 'edit'
        ),
        array('%d', '%d', '%d', '%s')
    );
    
    if ($result === false) {
        return new WP_Error('vote_failed', '投票失败');
    }
    
    // 检查是否达到投票阈值
    check_edit_voting_threshold($post_id);
    
    return true;
}

// 添加修改投票阈值检查
function check_edit_voting_threshold($post_id) {
    global $wpdb;
    
    $required_votes = get_option('voting_votes_required', 10);
    $approve_ratio = get_option('voting_approve_ratio', 0.6);
    
    $votes = $wpdb->get_results($wpdb->prepare(
        "SELECT vote_type, COUNT(*) as count 
         FROM {$wpdb->prefix}post_votes 
         WHERE post_id = %d AND vote_for = 'edit'
         GROUP BY vote_type",
        $post_id
    ));
    
    $total_votes = 0;
    $approve_votes = 0;
    
    foreach ($votes as $vote) {
        $total_votes += $vote->count;
        if ($vote->vote_type == 1) {
            $approve_votes = $vote->count;
        }
    }
    
    // 如果达到所需票数
    if ($total_votes >= $required_votes) {
        $ratio = $approve_votes / $total_votes;
        
        if ($ratio >= $approve_ratio) {
            // 通过修改
            $revision = get_post($post_id);
            $parent_post = get_post($revision->post_parent);
            
            wp_update_post(array(
                'ID' => $parent_post->ID,
                'post_title' => $revision->post_title,
                'post_content' => $revision->post_content,
                'post_status' => 'publish'
            ));
            
            // 通知作者
            add_user_notification(
                $revision->post_author,
                sprintf('您对文章《%s》的修改已通过社区投票', $parent_post->post_title),
                'edit_approved'
            );
        } else {
            // 拒绝修改
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'draft'
            ));
            
            // 通知作者
            $revision = get_post($post_id);
            add_user_notification(
                $revision->post_author,
                sprintf('您对文章《%s》的修改未通过社区投票', get_the_title($revision->post_parent)),
                'edit_rejected'
            );
        }
    }
}

// 获取文章的投票信息
function get_post_votes($post_id) {
    global $wpdb;
    
    $votes = $wpdb->get_results($wpdb->prepare(
        "SELECT vote_type, COUNT(*) as count 
         FROM {$wpdb->prefix}post_votes 
         WHERE post_id = %d 
         GROUP BY vote_type",
        $post_id
    ));
    
    $result = array(
        'approve' => 0,
        'reject' => 0,
        'total' => 0
    );
    
    foreach ($votes as $vote) {
        if ($vote->vote_type == 1) {
            $result['approve'] = (int)$vote->count;
        } else {
            $result['reject'] = (int)$vote->count;
        }
        $result['total'] += (int)$vote->count;
    }
    
    // 计算还需要多少票
    $required_votes = get_option('voting_votes_required', 10);
    $result['remaining'] = max(0, $required_votes - $result['total']);
    
    // 计算当前成比例
    $result['approve_ratio'] = $result['total'] > 0 ? 
        ($result['approve'] / $result['total']) : 0;
    
    return $result;
}

// 获取投票用户列表
function get_voting_users_list($post_id, $vote_type = null) {
    global $wpdb;
    
    $sql = "SELECT v.*, u.display_name, v.vote_date 
            FROM {$wpdb->prefix}post_votes v 
            JOIN {$wpdb->users} u ON v.user_id = u.ID 
            WHERE v.post_id = %d";
    
    $params = array($post_id);
    
    if ($vote_type !== null) {
        $sql .= " AND v.vote_type = %d";
        $params[] = $vote_type;
    }
    
    $sql .= " ORDER BY v.vote_date DESC";
    
    $votes = $wpdb->get_results($wpdb->prepare($sql, $params));
    
    if (empty($votes)) {
        return '暂无投票记录';
    }
    
    $output = '';
    foreach ($votes as $vote) {
        $date = mysql2date('Y-m-d H:i:s', $vote->vote_date);
        $output .= sprintf('%s (%s), ', esc_html($vote->display_name), esc_html($date));
    }
    
    return rtrim($output, ', ');
}

// 添加修订对比功能
function get_revision_diff($revision_id) {
    $revision = wp_get_post_revision($revision_id);
    if (!$revision) {
        return '';
    }

    $parent_id = $revision->post_parent;
    $parent = get_post($parent_id);
    
    // 获取文本差异
    $title_diff = wp_text_diff(
        $parent->post_title,
        $revision->post_title,
        array('show_split_view' => true)
    );
    
    $content_diff = wp_text_diff(
        $parent->post_content,
        $revision->post_content,
        array('show_split_view' => true)
    );

    $output = '<div class="revision-diff">';
    if ($title_diff) {
        $output .= '<h4>标题修改</h4>' . $title_diff;
    }
    if ($content_diff) {
        $output .= '<h4>内容修改</h4>' . $content_diff;
    }
    $output .= '</div>';

    return $output;
}

// AJAX处理差异对比显示
function ajax_get_revision_diff() {
    check_ajax_referer('voting-nonce', 'nonce');
    
    $revision_id = isset($_POST['revision_id']) ? intval($_POST['revision_id']) : 0;
    if (!$revision_id) {
        wp_send_json_error('无效的修订ID');
        return;
    }
    
    $diff = get_revision_diff($revision_id);
    wp_send_json_success($diff);
}
add_action('wp_ajax_get_revision_diff', 'ajax_get_revision_diff');

// AJAX处理管理员差异对比
function ajax_get_revision_diff_admin() {
    check_ajax_referer('revision-diff-nonce', 'nonce');
    
    $revision_id = intval($_POST['revision_id']);
    $parent_id = intval($_POST['parent_id']);
    
    $revision = wp_get_post_revision($revision_id);
    $parent = get_post($parent_id);
    
    if (!$revision || !$parent) {
        wp_send_json_error('无法加载修订版本');
        return;
    }
    
    $title_diff = wp_text_diff(
        $parent->post_title,
        $revision->post_title,
        array('show_split_view' => true)
    );
    
    $content_diff = wp_text_diff(
        $parent->post_content,
        $revision->post_content,
        array('show_split_view' => true)
    );
    
    $output = '<h3>修订差异对比</h3>';
    
    if ($title_diff) {
        $output .= '<h4>标题修改</h4>' . $title_diff;
    }
    
    if ($content_diff) {
        $output .= '<h4>内容修改</h4>' . $content_diff;
    }
    
    $edit_summary = get_post_meta($revision->ID, '_edit_summary', true);
    if ($edit_summary) {
        $output .= '<h4>修改说明</h4><p>' . esc_html($edit_summary) . '</p>';
    }
    
    wp_send_json_success($output);
}
add_action('wp_ajax_get_revision_diff_admin', 'ajax_get_revision_diff_admin');

// 添加投票页面资源
function add_voting_assets() {
    if (is_page_template('page-community-voting.php')) {
        wp_enqueue_style('voting-style', get_template_directory_uri() . '/assets/css/voting.css');
        wp_enqueue_script('voting-script', get_template_directory_uri() . '/assets/js/voting.js', array('jquery'), '1.0', true);
        wp_localize_script('voting-script', 'votingVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('voting-nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'add_voting_assets');

// 修改投票页面查询，只显示待处理的修改
function get_pending_revisions_query() {
    return new WP_Query(array(
        'post_type' => 'revision',
        'post_status' => 'inherit',  // 修改为 inherit
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_wp_revision_status',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_wp_revision_status',
                'value' => array('approved', 'rejected'),
                'compare' => 'NOT IN'
            )
        )
    ));
}

// 获取修改历史记录
function get_revision_history($post_id) {
    global $wpdb;
    
    $revisions = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, 
                GROUP_CONCAT(DISTINCT IF(v.vote_type = 1, u.display_name, NULL)) as approve_users,
                GROUP_CONCAT(DISTINCT IF(v.vote_type = 0, u.display_name, NULL)) as reject_users,
                GROUP_CONCAT(DISTINCT IF(v.is_admin_decision = 1, CONCAT(u.display_name, '(管理员)'), NULL)) as admin_decision
         FROM {$wpdb->posts} r
         LEFT JOIN {$wpdb->prefix}post_votes v ON r.ID = v.post_id
         LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID
         WHERE r.post_parent = %d 
         AND r.post_type = 'revision'
         GROUP BY r.ID
         ORDER BY r.post_date DESC",
        $post_id
    ));
    
    return $revisions;
}

// 处理管理员投票决定
function handle_admin_vote_decision() {
    check_ajax_referer('voting-nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('没有权限执行此操作');
        return;
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $decision = isset($_POST['decision']) ? intval($_POST['decision']) : 0;
    $vote_type = isset($_POST['vote_type']) ? sanitize_text_field($_POST['vote_type']) : '';
    
    if ($vote_type === 'edit') {
        // 处理修改投票
        $revision = get_post($post_id);
        $parent_post = get_post($revision->post_parent);
        
        if ($decision) {
            // 通过修改
            wp_update_post(array(
                'ID' => $parent_post->ID,
                'post_title' => $revision->post_title,
                'post_content' => $revision->post_content,
                'post_status' => 'publish'
            ));
            
            // 更新修订版本状态为已通过
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'inherit'  // 改为 inherit 状态
            ));
            
            // 标记修订为已处理
            update_post_meta($post_id, '_wp_revision_status', 'approved');
            
            // 记录管理员决定
            global $wpdb;
            $table_name = $wpdb->prefix . 'post_votes';
            
            // 检查表是否存在
            if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                create_voting_tables();
            }
            
            // 检查是否已存在投票记录
            $existing_vote = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE post_id = %d AND user_id = %d",
                $post_id,
                get_current_user_id()
            ));

            if ($existing_vote) {
                // 更新现有记录
                $wpdb->update(
                    $table_name,
                    array(
                        'vote_type' => 1,
                        'vote_for' => 'edit',
                        'is_admin_decision' => 1,
                        'vote_date' => current_time('mysql')
                    ),
                    array(
                        'post_id' => $post_id,
                        'user_id' => get_current_user_id()
                    ),
                    array('%d', '%s', '%d', '%s'),
                    array('%d', '%d')
                );
            } else {
                // 插入新记录
                $wpdb->insert(
                    $table_name,
                    array(
                        'post_id' => $post_id,
                        'user_id' => get_current_user_id(),
                        'vote_type' => 1,
                        'vote_for' => 'edit',
                        'is_admin_decision' => 1,
                        'vote_date' => current_time('mysql')
                    ),
                    array('%d', '%d', '%d', '%s', '%d', '%s')
                );
            }
            
            add_user_notification(
                $revision->post_author,
                sprintf('您对文章《%s》的修改已被管理员通过', $parent_post->post_title),
                'edit_approved'
            );
        } else {
            // 拒绝修改
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'inherit'  // 改为 inherit 状态
            ));
            
            // 标记修订为已处理
            update_post_meta($post_id, '_wp_revision_status', 'rejected');
            
            // 记录管理员决定
            global $wpdb;
            $table_name = $wpdb->prefix . 'post_votes';
            
            // 检查表是否存在和结构
            if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                create_voting_tables();
            }
            
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'user_id' => get_current_user_id(),
                    'vote_type' => 0,
                    'vote_for' => 'edit',
                    'is_admin_decision' => 1,
                    'vote_date' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s', '%d', '%s')
            );
            
            add_user_notification(
                $revision->post_author,
                sprintf('您对文章《%s》的修改已被管理员拒绝', $parent_post->post_title),
                'edit_rejected'
            );
        }
    } else {
        // 处理新文章投票
        $post = get_post($post_id);
        if ($decision) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish'
            ));
            
            add_user_notification(
                $post->post_author,
                sprintf('您的文章《%s》已被管理员通过', $post->post_title),
                'post_approved'
            );
        } else {
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'draft'
            ));
            
            add_user_notification(
                $post->post_author,
                sprintf('您的文章《%s》已被管理员拒绝', $post->post_title),
                'post_rejected'
            );
        }
    }
    
    wp_send_json_success('操作成功');
}
add_action('wp_ajax_admin_vote_decision', 'handle_admin_vote_decision');

// 添加自定义修订状态
function add_revision_statuses() {
    register_post_status('approved-revision', array(
        'label' => '已通过的修改',
        'public' => false,
        'exclude_from_search' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true
    ));
    
    register_post_status('rejected-revision', array(
        'label' => '已拒绝的修改',
        'public' => false,
        'exclude_from_search' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true
    ));
}
add_action('init', 'add_revision_statuses');

// 获取修改说明
function get_revision_summary($revision_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_edit_summaries';
    
    $summary = $wpdb->get_var($wpdb->prepare(
        "SELECT summary FROM $table_name WHERE revision_id = %d",
        $revision_id
    ));
    
    return $summary ? $summary : '';
}