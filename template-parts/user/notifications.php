<?php
$current_user = wp_get_current_user();
$notifications = get_user_notifications($current_user->ID);

if (empty($notifications)) {
    echo '<div class="no-notifications">暂无通知</div>';
} else {
    echo '<div class="notifications-list">';
    foreach ($notifications as $index => $notification) {
        $time = isset($notification['time']) ? $notification['time'] : '';
        $message = isset($notification['message']) ? $notification['message'] : '';
        $type = isset($notification['type']) ? $notification['type'] : 'info';
        $is_read = isset($notification['read']) ? $notification['read'] : false;
        
        $class = 'notification-item';
        if (!$is_read) {
            $class .= ' unread';
        }
        ?>
        <div class="<?php echo $class; ?>">
            <div class="notification-content">
                <?php echo esc_html($message); ?>
            </div>
            <div class="notification-meta">
                <span class="notification-time"><?php echo esc_html($time); ?></span>
                <span class="notification-type"><?php echo esc_html($type); ?></span>
                <?php if (!$is_read): ?>
                    <button class="mark-read-btn" data-index="<?php echo $index; ?>">标记为已读</button>
                <?php endif; ?>
                <button class="delete-notification-btn" data-index="<?php echo $index; ?>">删除</button>
            </div>
        </div>
        <?php
    }
    echo '</div>';
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
</style>

<script>
jQuery(document).ready(function($) {
    // 标记通知为已读
    $('.mark-read-btn').click(function() {
        var btn = $(this);
        var index = btn.data('index');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mark_notification_read',
                index: index,
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
        var index = btn.data('index');
        
        if (confirm('确定要删除这条通知吗？')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_notification',
                    index: index,
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