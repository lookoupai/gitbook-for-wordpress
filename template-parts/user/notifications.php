<div class="user-notifications-section">
    <h3>消息通知</h3>
    <?php
    global $wpdb;
    $notifications = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_notifications 
        WHERE user_id = %d 
        ORDER BY created_at DESC 
        LIMIT 10",
        get_current_user_id()
    ));
    
    if ($notifications) :
        foreach ($notifications as $notification) :
    ?>
        <div class="notification-item <?php echo $notification->is_read ? 'read' : 'unread'; ?>">
            <div class="notification-content">
                <?php echo esc_html($notification->content); ?>
            </div>
            <div class="notification-meta">
                <span>时间：<?php echo date('Y-m-d H:i', strtotime($notification->created_at)); ?></span>
            </div>
        </div>
    <?php
        endforeach;
    else:
    ?>
        <p>暂无通知</p>
    <?php endif; ?>
</div> 