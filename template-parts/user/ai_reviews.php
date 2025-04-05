<?php
/**
 * 用户中心 - AI审核历史
 */

// 检查是否登录
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$user_id = get_current_user_id();

// 获取用户的所有文章
$user_posts = get_posts(array(
    'author' => $user_id,
    'post_type' => 'post',
    'post_status' => array('publish', 'pending', 'draft'),
    'posts_per_page' => -1
));

// 获取所有有AI审核记录的文章
$ai_reviewed_posts = array();
foreach ($user_posts as $post) {
    $ai_score = get_post_meta($post->ID, '_ai_review_score', true);
    $ai_feedback = get_post_meta($post->ID, '_ai_review_feedback', true);
    $ai_date = get_post_meta($post->ID, '_ai_review_date', true);
    
    if ($ai_score !== '') {
        $ai_reviewed_posts[] = array(
            'post' => $post,
            'score' => floatval($ai_score),
            'feedback' => $ai_feedback,
            'date' => $ai_date,
            'type' => 'post'
        );
    }
    
    // 获取该文章的修订版本
    $revisions = wp_get_post_revisions($post->ID);
    foreach ($revisions as $revision) {
        if ($revision->post_author == $user_id) {
            $rev_ai_score = get_post_meta($revision->ID, '_ai_review_score', true);
            $rev_ai_feedback = get_post_meta($revision->ID, '_ai_review_feedback', true);
            $rev_ai_date = get_post_meta($revision->ID, '_ai_review_date', true);
            
            if ($rev_ai_score !== '') {
                $ai_reviewed_posts[] = array(
                    'post' => $revision,
                    'parent' => $post,
                    'score' => floatval($rev_ai_score),
                    'feedback' => $rev_ai_feedback,
                    'date' => $rev_ai_date,
                    'type' => 'revision'
                );
            }
        }
    }
}

// 按日期排序
usort($ai_reviewed_posts, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$settings = get_ai_review_settings();
$min_score = $settings['min_score'];

?>

<div class="section-title">
    <h3>AI审核历史</h3>
</div>

<?php if (empty($ai_reviewed_posts)): ?>
<div class="alert alert-info">
    您还没有AI审核记录。
</div>
<?php else: ?>
<div class="ai-review-history">
    <?php foreach ($ai_reviewed_posts as $item): 
        $post = $item['post'];
        $is_revision = $item['type'] === 'revision';
        $parent = isset($item['parent']) ? $item['parent'] : null;
        $title = $is_revision ? $parent->post_title : $post->post_title;
        $score = $item['score'];
        $feedback = $item['feedback'];
        $date = $item['date'];
        $passed = $score >= $min_score;
        
        $post_url = $is_revision ? get_permalink($parent->ID) : get_permalink($post->ID);
        $edit_summary = $is_revision ? get_post_meta($post->ID, '_edit_summary', true) : '';
    ?>
    <div class="ai-review-item card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php if ($is_revision): ?>
                    对《<?php echo esc_html($title); ?>》的修改
                <?php else: ?>
                    文章：<?php echo esc_html($title); ?>
                <?php endif; ?>
            </h5>
            <div class="ai-score">
                <span class="badge <?php echo $passed ? 'bg-success' : 'bg-danger'; ?>">
                    AI评分: <?php echo round($score * 10, 1); ?>/10
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (!empty($edit_summary)): ?>
            <div class="edit-summary mb-3">
                <strong>修改说明：</strong> <?php echo esc_html($edit_summary); ?>
            </div>
            <?php endif; ?>
            
            <div class="ai-feedback mb-3">
                <h6>AI反馈：</h6>
                <div class="feedback-content p-3 bg-light rounded">
                    <?php echo nl2br(esc_html($feedback)); ?>
                </div>
            </div>
            
            <div class="meta-info text-muted">
                <small>审核时间: <?php echo date_i18n('Y-m-d H:i:s', strtotime($date)); ?></small>
                <small class="ms-3">状态: <?php echo get_post_status_object(get_post_status($is_revision ? $parent->ID : $post->ID))->label; ?></small>
            </div>
            
            <div class="actions mt-3">
                <a href="<?php echo esc_url($post_url); ?>" class="btn btn-sm btn-primary">查看文章</a>
                <?php if (!$passed && !$is_revision && $post->post_status !== 'publish'): ?>
                <a href="<?php echo esc_url(home_url('/edit-post?post_id=' . ($is_revision ? $parent->ID : $post->ID))); ?>" class="btn btn-sm btn-secondary">编辑文章</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?> 