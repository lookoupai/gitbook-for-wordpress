<?php
// 获取当前页码
$page = isset($_GET['notification_page']) ? max(1, intval($_GET['notification_page'])) : 1;
$notifications_data = get_user_notifications(get_current_user_id(), $page);

if (empty($notifications_data['notifications'])) {
    echo '<div class="no-notifications">暂无通知</div>';
} else {
    echo '<div class="notifications-list">';
    foreach ($notifications_data['notifications'] as $notification) {
        $class = 'notification-item';
        if (!$notification->is_read) {
            $class .= ' unread';
        }
        ?>
        <div class="<?php echo $class; ?>">
            <div class="notification-content">
                <?php echo esc_html($notification->message); ?>
            </div>
            <div class="notification-meta">
                <span class="notification-time">
                    <?php echo human_time_diff(strtotime($notification->created_at), current_time('timestamp')); ?>前
                </span>
                <?php if (!$notification->is_read): ?>
                    <button class="mark-read-btn" data-id="<?php echo $notification->id; ?>">标记为已读</button>
                <?php endif; ?>
                <button class="delete-notification-btn" data-id="<?php echo $notification->id; ?>">删除</button>
            </div>
        </div>
        <?php
    }
    echo '</div>';

    // 显示分页
    if ($notifications_data['total_pages'] > 1) {
        echo '<div class="pagination">';
        
        // 显示上一页
        if ($page > 1) {
            printf(
                '<a href="%s" class="prev-page">&laquo; 上一页</a>',
                add_query_arg('notification_page', $page - 1)
            );
        }
        
        // 显示页码
        $start_page = max(1, $page - 2);
        $end_page = min($notifications_data['total_pages'], $page + 2);
        
        if ($start_page > 1) {
            echo '<a href="' . add_query_arg('notification_page', 1) . '">1</a>';
            if ($start_page > 2) {
                echo '<span class="pagination-dots">...</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $class = $i === $page ? 'current' : '';
            printf(
                '<a href="%s" class="%s">%d</a>',
                add_query_arg('notification_page', $i),
                $class,
                $i
            );
        }
        
        if ($end_page < $notifications_data['total_pages']) {
            if ($end_page < $notifications_data['total_pages'] - 1) {
                echo '<span class="pagination-dots">...</span>';
            }
            printf(
                '<a href="%s">%d</a>',
                add_query_arg('notification_page', $notifications_data['total_pages']),
                $notifications_data['total_pages']
            );
        }
        
        // 显示下一页
        if ($page < $notifications_data['total_pages']) {
            printf(
                '<a href="%s" class="next-page">下一页 &raquo;</a>',
                add_query_arg('notification_page', $page + 1)
            );
        }
        
        echo '</div>';
    }

    // 显示全部标记为已读按钮
    if ($notifications_data['total'] > 0) {
        echo '<div class="notifications-actions">';
        echo '<button id="mark-all-read-btn">全部标记为已读</button>';
        echo '</div>';
    }
}
?>

<style>
.notifications-list {
    max-width: 800px;
    margin: 0 auto;
}

.notification-item {
    padding: 15px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}

.notification-item.unread {
    background: #f8f9fa;
    border-left: 3px solid #007bff;
}

.notification-content {
    margin-bottom: 10px;
}

.notification-meta {
    font-size: 0.9em;
    color: #666;
    display: flex;
    align-items: center;
    gap: 10px;
}

.notification-time {
    margin-right: auto;
}

.mark-read-btn,
.delete-notification-btn {
    padding: 5px 10px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.mark-read-btn {
    background: #28a745;
    color: white;
}

.delete-notification-btn {
    background: #dc3545;
    color: white;
}

.no-notifications {
    text-align: center;
    padding: 20px;
    color: #666;
}

.pagination {
    margin: 20px 0;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}

.pagination a {
    display: inline-block;
    padding: 8px 12px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 4px;
    min-width: 40px;
    text-align: center;
}

.pagination a:hover {
    background-color: #f5f5f5;
}

.pagination a.current {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.pagination .prev-page,
.pagination .next-page {
    min-width: auto;
    padding: 8px 15px;
}

.pagination-dots {
    color: #666;
    padding: 0 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 标记通知为已读
    $('.mark-read-btn').click(function() {
        var btn = $(this);
        var id = btn.data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mark_notification_read',
                id: id,
                nonce: '<?php echo wp_create_nonce("user-notification-nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    btn.closest('.notification-item').removeClass('unread');
                    btn.remove();
                }
            }
        });
    });

    // 删除通知
    $('.delete-notification-btn').click(function() {
        var btn = $(this);
        var id = btn.data('id');
        
        if (confirm('确定要删除这条通知吗？')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_notification',
                    id: id,
                    nonce: '<?php echo wp_create_nonce("user-notification-nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        btn.closest('.notification-item').fadeOut(function() {
                            $(this).remove();
                            if ($('.notification-item').length === 0) {
                                $('.notifications-list').html('<div class="no-notifications">暂无通知</div>');
                            }
                        });
                    }
                }
            });
        }
    });
});
</script> 