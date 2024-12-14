<?php
// 投票功能
if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/notifications.php';

// 创建投票表
function create_voting_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql_votes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}post_votes (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        revision_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        vote_type tinyint(1) NOT NULL COMMENT '1为赞成,0为反对',
        vote_date datetime DEFAULT CURRENT_TIMESTAMP,
        is_active tinyint(1) NOT NULL DEFAULT 1 COMMENT '1为有效,0为无效',
        is_admin_decision tinyint(1) NOT NULL DEFAULT 0 COMMENT '1为管理员决定',
        PRIMARY KEY  (id),
        UNIQUE KEY vote_unique (post_id, revision_id, user_id, is_active),
        KEY post_id (post_id),
        KEY revision_id (revision_id),
        KEY user_id (user_id),
        KEY is_active (is_active)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_votes);
    
    // 检查字段是否存在
    $table_name = $wpdb->prefix . 'post_votes';
    $row = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'is_active'");
    if (empty($row)) {
        $wpdb->query("ALTER TABLE $table_name 
                     ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1 
                     COMMENT '1为有效,0为无效'");
    }
    
    $row = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'is_admin_decision'");
    if (empty($row)) {
        $wpdb->query("ALTER TABLE $table_name 
                     ADD COLUMN is_admin_decision tinyint(1) NOT NULL DEFAULT 0 
                     COMMENT '1为管理员决定'");
    }
}

// 检查用户投票权限
function check_user_voting_permission($user_id, $post_id, $revision_id = 0) {
    // 检查用户是否已投票
    global $wpdb;
    
    // 获取当前修改版本
    $revision = get_post($post_id);
    if (!$revision) {
        return new WP_Error('invalid_post', '无效的文章');
    }
    
    // 获取原文章ID
    $parent_id = wp_get_post_parent_id($post_id);
    $original_post_id = $parent_id ? $parent_id : $post_id;
    
    // 检查是否对当前版本投过有效票
    $voted = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}post_votes 
         WHERE user_id = %d 
         AND post_id = %d
         AND revision_id = %d
         AND is_active = 1",  // 只检查有效的投票
        $user_id,
        $original_post_id,
        $revision_id
    ));
    
    if ($voted > 0) {
        return new WP_Error('already_voted', '您已经对此版本投过票了');
    }
    
    // 如果是管理员，跳过注册时间检查
    if (!current_user_can('manage_options')) {
        // 检查注册时间
        $min_months = get_option('voting_min_register_months', 3);
        $user = get_userdata($user_id);
        $register_date = strtotime($user->user_registered);
        $months_diff = (time() - $register_date) / (30 * 24 * 60 * 60);
        
        if ($months_diff < $min_months) {
            return new WP_Error('insufficient_time', 
                sprintf('需要注册满%d个月才能参与投票', $min_months));
        }
    }
    
    return true;
}

// 添加投票
function add_vote($post_id, $vote, $reason_type = '', $reason_content = '') {
    global $wpdb;
    $user_id = get_current_user_id();
    
    // 获取当前文章的修改版本ID
    $revision_id = get_post_meta($post_id, '_revision_id', true) ?: 0;
    
    // 获取原文章ID
    $parent_id = wp_get_post_parent_id($post_id);
    $original_post_id = $parent_id ? $parent_id : $post_id;
    
    // 检查投票权限
    $permission = check_user_voting_permission($user_id, $post_id, $revision_id);
    if (is_wp_error($permission)) {
        return $permission;
    }
    
    // 开始事务
    $wpdb->query('START TRANSACTION');
    
    try {
        // 先删除该用户对该版本的所有投票记录
        $wpdb->delete(
            $wpdb->prefix . 'post_votes',
            array(
                'post_id' => $original_post_id,
                'revision_id' => $revision_id,
                'user_id' => $user_id
            ),
            array('%d', '%d', '%d')
        );
        
        // 添加新的投票记录
        $result = $wpdb->insert(
            $wpdb->prefix . 'post_votes',
            array(
                'post_id' => $original_post_id,
                'revision_id' => $revision_id,
                'user_id' => $user_id,
                'vote_type' => $vote,
                'vote_date' => current_time('mysql'),
                'is_active' => 1,
                'is_admin_decision' => current_user_can('manage_options') ? 1 : 0
            ),
            array('%d', '%d', '%d', '%d', '%s', '%d', '%d')
        );
        
        if ($result === false) {
            throw new Exception('投票失败');
        }
        
        // 如果提供了投票理由,则保存理由
        if ($reason_type && $reason_content) {
            $reason_result = $wpdb->insert(
                $wpdb->prefix . 'vote_reasons',
                array(
                    'post_id' => $original_post_id,
                    'revision_id' => $revision_id,
                    'user_id' => $user_id,
                    'reason_type' => $reason_type,
                    'reason_content' => $reason_content . (current_user_can('manage_options') ? ' (管理员决定)' : '')
                ),
                array('%d', '%d', '%d', '%s', '%s')
            );
            
            if ($reason_result === false) {
                throw new Exception('保存投票理由失败');
            }
        }
        
        $wpdb->query('COMMIT');
        
        // 如果是管理员投票，直接决定结果
        if (current_user_can('manage_options')) {
            if ($vote == 1) {
                // 通过
                if ($parent_id) {
                    // 如果是修改
                    $revision = get_post($post_id);
                    wp_update_post(array(
                        'ID' => $parent_id,
                        'post_title' => $revision->post_title,
                        'post_content' => $revision->post_content,
                        'post_status' => 'publish'
                    ));
                    
                    // 更新修订版本状态
                    update_post_meta($post_id, '_wp_revision_status', 'approved');
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_status' => 'inherit'
                    ));
                    
                    // 通知作者
                    add_user_notification(
                        $revision->post_author,
                        sprintf('您对文章《%s》的修改已被管理员通过', get_the_title($parent_id)),
                        'edit_approved'
                    );
                } else {
                    // 如果是新文章
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_status' => 'publish'
                    ));
                    
                    // 通知作者
                    $post = get_post($post_id);
                    add_user_notification(
                        $post->post_author,
                        sprintf('您的文章《%s》已被管理员通过', $post->post_title),
                        'post_approved'
                    );
                }
            } else {
                // 拒绝
                if ($parent_id) {
                    // 如果是修改
                    // 更新修订版本状态
                    update_post_meta($post_id, '_wp_revision_status', 'rejected');
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_status' => 'inherit'
                    ));
                    
                    // 通知作者
                    $revision = get_post($post_id);
                    add_user_notification(
                        $revision->post_author,
                        sprintf('您对文章《%s》的修改已被管理员拒绝', get_the_title($parent_id)),
                        'edit_rejected'
                    );
                } else {
                    // 如果是新文章
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_status' => 'draft'
                    ));
                    
                    // 通知作者
                    $post = get_post($post_id);
                    add_user_notification(
                        $post->post_author,
                        sprintf('您的文章《%s》已被管理员拒绝', $post->post_title),
                        'post_rejected'
                    );
                }
            }
        } else {
            // 普通用户投票，检查是否达到阈值
            check_voting_threshold($post_id);
        }
        
        return true;
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('vote_failed', $e->getMessage());
    }
}

// 获取文章的投票统计
function get_post_votes($post_id) {
    global $wpdb;
    
    // 获取当前文章的修改版本ID和提交时间
    $revision_id = get_post_meta($post_id, '_revision_id', true) ?: 0;
    $revision = get_post($post_id);
    $revision_date = $revision ? $revision->post_date : '';
    
    // 获取原文章ID
    $parent_id = wp_get_post_parent_id($post_id);
    $original_post_id = $parent_id ? $parent_id : $post_id;
    
    // 修改SQL查询,加入时间条件
    $votes = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            SUM(vote_type = 1) as approve,
            SUM(vote_type = 0) as reject,
            COUNT(*) as total,
            SUM(is_admin_decision) as admin_votes
        FROM {$wpdb->prefix}post_votes
        WHERE post_id = %d 
        AND revision_id = %d
        AND vote_date >= %s
        AND is_active = 1",
        $original_post_id,
        $revision_id,
        $revision_date
    ));
    
    // 添加错误处理和默认值
    if ($votes === null || $wpdb->last_error) {
        return array(
            'approve' => 0,
            'reject' => 0,
            'total' => 0,
            'admin_votes' => 0
        );
    }
    
    $result = array(
        'approve' => (int)$votes->approve,
        'reject' => (int)$votes->reject,
        'total' => (int)$votes->total,
        'admin_votes' => (int)$votes->admin_votes
    );
    
    // 添加额外信息
    $required_votes = get_option('voting_votes_required', 10);
    $result['remaining'] = max(0, $required_votes - $result['total']);
    $result['approve_ratio'] = $result['total'] > 0 ? 
        ($result['approve'] / $result['total']) : 0;
    
    return $result;
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
    // 如果已有管理员投票，不需要检查阈值
    $votes = get_post_votes($post_id);
    if ($votes['admin_votes'] > 0) {
        return;
    }
    
    $required_votes = get_option('voting_votes_required', 10);
    $approve_ratio = get_option('voting_approve_ratio', 0.6);
    
    $total_votes = $votes['total'];
    $approve_votes = $votes['approve'];
    
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
    $reason_type = isset($_POST['reason_type']) ? sanitize_text_field($_POST['reason_type']) : '';
    $reason_content = isset($_POST['reason_content']) ? sanitize_text_field($_POST['reason_content']) : '';
    
    // 添加投票记录
    $result = add_vote($post_id, $vote, $reason_type, $reason_content);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    wp_send_json_success('投票成功');
}
add_action('wp_ajax_handle_vote', 'handle_vote');

// 添加修改投票阈值检查
function check_edit_voting_threshold($post_id) {
    // 如果已有管理员投票，不需要检查阈值
    $votes = get_post_votes($post_id);
    if ($votes['admin_votes'] > 0) {
        return;
    }
    
    $required_votes = get_option('voting_votes_required', 10);
    $approve_ratio = get_option('voting_approve_ratio', 0.6);
    
    $total_votes = $votes['total'];
    $approve_votes = $votes['approve'];
    
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
            
            // 更新修订状态
            update_post_meta($post_id, '_wp_revision_status', 'approved');
            
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
            
            // 更新修订状态
            update_post_meta($post_id, '_wp_revision_status', 'rejected');
            
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

// 获取投票用户列表
function get_voting_users_list($post_id, $vote_type = null) {
    global $wpdb;
    
    // 获取当前文章的修改版本ID和提交时间
    $revision_id = get_post_meta($post_id, '_revision_id', true) ?: 0;
    $revision = get_post($post_id);
    $revision_date = $revision ? $revision->post_date : '';
    
    // 获取原文章ID
    $parent_id = wp_get_post_parent_id($post_id);
    $original_post_id = $parent_id ? $parent_id : $post_id;
    
    // 检查表是否存在
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vote_reasons'");
    
    // 基础查询
    $sql = "SELECT DISTINCT v.*, u.display_name, v.vote_date, v.is_admin_decision";
    
    // 只有表存在时才关联查询
    if ($table_exists) {
        $sql .= ", r.reason_type, r.reason_content";
    }
    
    $sql .= " FROM {$wpdb->prefix}post_votes v 
              JOIN {$wpdb->users} u ON v.user_id = u.ID";
    
    if ($table_exists) {
        $sql .= " LEFT JOIN {$wpdb->prefix}vote_reasons r ON (
            v.post_id = r.post_id AND 
            v.revision_id = r.revision_id AND 
            v.user_id = r.user_id
        )";
    }
    
    $sql .= " WHERE v.post_id = %d 
              AND v.revision_id = %d
              AND v.vote_date >= %s
              AND v.is_active = 1";
    
    $params = array(
        $original_post_id,
        $revision_id,
        $revision_date
    );
    
    if ($vote_type !== null) {
        $sql .= " AND v.vote_type = %d";
        $params[] = $vote_type;
    }
    
    $sql .= " ORDER BY v.is_admin_decision DESC, v.vote_date DESC";
    
    $votes = $wpdb->get_results($wpdb->prepare($sql, $params));
    
    if (empty($votes)) {
        if ($vote_type === 1) {
            return '暂无赞成投票';
        } elseif ($vote_type === 0) {
            return '暂无反对投票';
        }
        return '暂无投票记录';
    }
    
    $output = '';
    foreach ($votes as $vote) {
        $date = mysql2date('Y-m-d H:i:s', $vote->vote_date);
        $reason = ($table_exists && $vote->reason_content) ? 
            sprintf('(%s)', esc_html($vote->reason_content)) : '';
        $admin_label = $vote->is_admin_decision ? '(管理员)' : '';
        $output .= sprintf('%s%s %s (%s), ', 
            esc_html($vote->display_name),
            $admin_label,
            $reason,
            esc_html($date)
        );
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
        'post_status' => 'inherit',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'AND',
            array(
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
            ),
            array(
                'key' => '_edit_summary',
                'compare' => 'EXISTS'
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

// 检查用户是否已对该文章投票
function has_user_voted($post_id) {
    global $wpdb;
    $user_id = get_current_user_id();
    
    // 获取当前文章的修改版本ID和提交时间
    $revision_id = get_post_meta($post_id, '_revision_id', true) ?: 0;
    $revision = get_post($post_id);
    $revision_date = $revision ? $revision->post_date : '';
    
    // 获取原文章ID
    $parent_id = wp_get_post_parent_id($post_id);
    $original_post_id = $parent_id ? $parent_id : $post_id;
    
    // 检查是否对当前版本投过票,加入时间条件
    $voted = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}post_votes 
        WHERE user_id = %d 
        AND post_id = %d 
        AND revision_id = %d
        AND vote_date >= %s
        AND is_active = 1",
        $user_id,
        $original_post_id,
        $revision_id,
        $revision_date
    ));
    
    return $voted > 0;
}

// 在提交新修改版本时将旧投票标记为无效
function deactivate_old_votes($post_id) {
    global $wpdb;
    
    // 获取原文章ID
    $parent_id = wp_get_post_parent_id($post_id);
    $original_post_id = $parent_id ? $parent_id : $post_id;
    
    // 将该文章的所有投票标
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}post_votes 
         SET is_active = 0 
         WHERE post_id = %d",
        $original_post_id
    ));
}

// 在保存修改版本时调用
add_action('save_post_revision', 'deactivate_old_votes');