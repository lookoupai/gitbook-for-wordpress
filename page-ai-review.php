<?php
/**
 * Template Name: AI审核页面
 */

// 仅在开启调试模式时添加调试信息
$debug_mode = isset($_GET['debug_ai']) && current_user_can('administrator');
if ($debug_mode && current_user_can('administrator')) {
    error_log('正在加载AI审核页面模板');
    error_log('当前主题目录: ' . get_template_directory());
    error_log('当前主题URL: ' . get_template_directory_uri());
}

get_header();

// 获取AI审核设置
$settings = get_ai_review_settings();
$ai_enabled = $settings['enabled'] && is_ai_services_active();
$min_score = $settings['min_score'];
$auto_approve = $settings['auto_approve'];

// 获取待处理的修改和文章
$query = get_pending_content_query();

// 仅在调试模式下记录查询结果
if ($debug_mode && current_user_can('administrator')) {
    error_log('AI审核页面 - 查询结果: 找到 ' . $query->found_posts . ' 篇文章');
    
    // 输出查询SQL
    error_log('查询SQL: ' . $query->request);
    
    // 记录每篇文章的ID和状态
    if ($query->have_posts()) {
        $debug_info = array();
        foreach ($query->posts as $post) {
            $debug_info[] = sprintf(
                'ID: %d, 类型: %s, 状态: %s, 标题: %s', 
                $post->ID, 
                $post->post_type, 
                $post->post_status,
                $post->post_title
            );
        }
        error_log('查询到的文章: ' . implode(' | ', $debug_info));
    } else {
        error_log('没有找到待审核的文章');
    }
}

?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>

    <main id="main" class="site-main right-content">
        <div class="ai-review-page">
            <div class="page-header mb-4">
                <h1 class="page-title">AI内容审核</h1>
                
                <?php if (current_user_can('administrator')): ?>
                <div class="admin-tools mb-2">
                    <a href="<?php echo admin_url('tools.php?page=ai-services-diagnostics'); ?>" class="btn btn-sm btn-outline-secondary">AI服务诊断</a>
                    <a href="<?php echo admin_url('options-general.php?page=ai-review-settings'); ?>" class="btn btn-sm btn-outline-secondary">审核设置</a>
                    <a href="<?php echo add_query_arg('debug_ai', '1'); ?>" class="btn btn-sm btn-outline-secondary">开启调试模式</a>
                </div>
                <?php endif; ?>
                
                <?php if (!$ai_enabled): ?>
                <div class="alert alert-warning">
                    <strong>注意：</strong> AI审核功能尚未启用或配置。请联系管理员进行设置。
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <strong>AI审核已启用</strong>
                    <p>当前设置：最低通过分数 <?php echo $min_score * 10; ?>/10，<?php echo $auto_approve ? '自动批准修改' : '手动批准修改'; ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!is_user_logged_in()): ?>
            <div class="alert alert-warning">
                请先<a href="<?php echo wp_login_url(get_permalink()); ?>">登录</a>后查看。
            </div>
            <?php else: ?>
                <?php if ($query->have_posts()): ?>
                    <div class="review-tabs">
                        <ul class="nav nav-tabs mb-4" id="reviewTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">待审核内容</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="reviewed-tab" data-bs-toggle="tab" data-bs-target="#reviewed" type="button" role="tab" aria-controls="reviewed" aria-selected="false">已审核内容</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="reviewTabsContent">
                            <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                                <div class="row">
                                    <?php while ($query->have_posts()): $query->the_post();
                                        // 获取文章信息
                                        $post_id = get_the_ID();
                                        $post_type = get_post_type();
                                        $is_revision = $post_type === 'revision';
                                        $parent_id = $is_revision ? wp_get_post_parent_id($post_id) : 0;
                                        $parent_post = $parent_id ? get_post($parent_id) : null;
                                        
                                        // 获取AI审核结果
                                        $score = get_post_meta($post_id, '_ai_review_score', true);
                                        $score_display = ($score !== '') ? round($score * 10, 1) . '/10' : '未审核';
                                        $feedback = get_post_meta($post_id, '_ai_review_feedback', true);
                                        
                                        // 检查是否满足自动批准条件
                                        $auto_approved = ($score !== '' && $score >= $min_score && $auto_approve) ? true : false;
                                        
                                        // 设置显示的标题和链接
                                        $title = $is_revision ? ($parent_post ? $parent_post->post_title : '未知文章的修改') : get_the_title();
                                        $title = empty($title) ? '(无标题)' : $title;
                                        $view_link = $is_revision ? admin_url('revision.php?revision=' . $post_id) : get_permalink();
                                    ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card review-item">
                                            <div class="card-header">
                                                <h5 class="card-title"><?php echo esc_html($title); ?></h5>
                                                <div class="post-meta">
                                                    <span class="post-type badge bg-<?php echo $is_revision ? 'info' : 'primary'; ?>">
                                                        <?php echo $is_revision ? '修改版本' : '新文章'; ?>
                                                    </span>
                                                    <span class="post-author">
                                                        作者: <?php echo get_the_author(); ?>
                                                    </span>
                                                    <span class="post-date">
                                                        提交于: <?php echo get_the_date('Y-m-d H:i'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="content-preview mb-3">
                                                    <h6>内容预览:</h6>
                                                    <div class="preview-text">
                                                        <?php echo wp_trim_words(get_the_content(), 50, '...'); ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($score !== ''): ?>
                                                <div class="ai-review-result mb-3">
                                                    <h6>AI审核结果:</h6>
                                                    <div class="score-badge badge bg-<?php echo $score >= $min_score ? 'success' : 'danger'; ?>">
                                                        得分: <?php echo $score_display; ?>
                                                    </div>
                                                    <?php if (!empty($feedback)): ?>
                                                    <div class="feedback-text mt-2">
                                                        <?php echo nl2br(esc_html($feedback)); ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="actions">
                                                    <a href="<?php echo esc_url($view_link); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">查看详情</a>
                                                    
                                                    <?php if (current_user_can('administrator')): ?>
                                                    <button class="btn btn-sm btn-primary trigger-ai-review-btn" data-post-id="<?php echo $post_id; ?>" data-type="<?php echo $is_revision ? 'revision' : 'post'; ?>">
                                                        手动触发审核
                                                    </button>
                                                    
                                                    <?php if ($score !== '' || $auto_approved): ?>
                                                    <button class="btn btn-sm btn-success admin-approve-btn" data-post-id="<?php echo $post_id; ?>" data-parent-id="<?php echo $parent_id; ?>" data-type="<?php echo $is_revision ? '修改' : '文章'; ?>">
                                                        批准
                                                    </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-sm btn-danger admin-reject-btn" data-post-id="<?php echo $post_id; ?>" data-parent-id="<?php echo $parent_id; ?>" data-type="<?php echo $is_revision ? '修改' : '文章'; ?>">
                                                        拒绝
                                                    </button>
                                                    
                                                    <div class="reject-reason-form mt-3" style="display: none;">
                                                        <textarea class="form-control mb-2" placeholder="请输入拒绝理由"></textarea>
                                                        <button class="btn btn-sm btn-danger confirm-reject-btn">确认拒绝</button>
                                                        <button class="btn btn-sm btn-outline-secondary cancel-reject-btn">取消</button>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; wp_reset_postdata(); ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="reviewed" role="tabpanel" aria-labelledby="reviewed-tab">
                                <!-- 已审核内容显示在这里 -->
                                <div class="alert alert-info">
                                    已审核内容页面正在开发中...
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        目前没有需要审核的内容。
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php if (is_user_logged_in() && current_user_can('administrator')): ?>
<script>
(function($) {
    // 手动触发AI审核
    $('.trigger-ai-review-btn').click(function() {
        const postId = $(this).data('post-id');
        const type = $(this).data('type');
        const btn = $(this);
        
        // 输出调试信息
        console.log('触发AI审核: 文章ID=' + postId + ', 类型=' + type);
        
        if (!confirm('确定要为这' + type + '触发AI审核吗？')) {
            return;
        }
        
        btn.prop('disabled', true).text('审核中...');
        
        // 确保使用正确的ajaxurl
        const ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
        console.log('AJAX URL: ' + ajax_url);
        
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'trigger_manual_ai_review',
                post_id: postId,
                security: '<?php echo wp_create_nonce("ai-review-nonce"); ?>'
            },
            success: function(response) {
                console.log('AJAX响应:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('审核失败: ' + (response.data ? response.data.message : '未知错误'));
                    btn.prop('disabled', false).text('手动触发审核');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX错误:', status, error);
                console.log('响应文本:', xhr.responseText);
                alert('请求失败，请重试');
                btn.prop('disabled', false).text('手动触发审核');
            }
        });
    });
    
    // 管理员批准操作
    $('.admin-approve-btn').click(function() {
        const postId = $(this).data('post-id');
        const parentId = $(this).data('parent-id');
        const contentType = $(this).data('type');
        const btn = $(this);
        
        if (!confirm('确定要批准这' + contentType + '吗？')) {
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'admin_approve_content',
                post_id: postId,
                parent_id: parentId,
                security: '<?php echo wp_create_nonce("admin-action-nonce"); ?>'
            },
            success: function(response) {
                console.log('批准操作响应:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('批准失败: ' + (response.data ? response.data.message : '未知错误'));
                    btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('批准操作错误:', status, error);
                alert('请求失败，请重试');
                btn.prop('disabled', false);
            }
        });
    });
    
    // 管理员拒绝操作
    $('.admin-reject-btn').click(function() {
        const item = $(this).closest('.review-item');
        item.find('.reject-reason-form').show();
        $(this).prop('disabled', true);
    });
    
    // 取消拒绝
    $('.cancel-reject-btn').click(function() {
        const item = $(this).closest('.review-item');
        item.find('.reject-reason-form').hide();
        item.find('.admin-reject-btn').prop('disabled', false);
    });
    
    // 确认拒绝
    $('.confirm-reject-btn').click(function() {
        const item = $(this).closest('.review-item');
        const reasonText = item.find('textarea').val();
        const postId = item.find('.admin-reject-btn').data('post-id');
        const parentId = item.find('.admin-reject-btn').data('parent-id');
        const btn = $(this);
        
        if (!reasonText.trim()) {
            alert('请输入拒绝理由');
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'admin_reject_content',
                post_id: postId,
                parent_id: parentId,
                reason: reasonText,
                security: '<?php echo wp_create_nonce("admin-action-nonce"); ?>'
            },
            success: function(response) {
                console.log('拒绝操作响应:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('拒绝失败: ' + (response.data ? response.data.message : '未知错误'));
                    btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('拒绝操作错误:', status, error);
                alert('请求失败，请重试');
                btn.prop('disabled', false);
            }
        });
    });
})(jQuery);
</script>
<?php endif; ?>

<?php get_footer(); ?> 