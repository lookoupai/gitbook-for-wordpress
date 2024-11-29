<?php
if (!defined('ABSPATH')) exit;

// 注册AJAX处理函数
function register_article_tabs_ajax() {
    add_action('wp_ajax_get_tab_content', 'handle_get_tab_content');
    add_action('wp_ajax_nopriv_get_tab_content', 'handle_get_tab_content');
}
add_action('init', 'register_article_tabs_ajax');

// 处理获取标签内容的AJAX请求
function handle_get_tab_content() {
    $tab = sanitize_text_field($_POST['tab']);
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    
    // 生成缓存键
    $cache_key = 'article_tabs_' . $tab . '_page_' . $page;
    
    // 尝试从缓存获取内容
    $cached_content = get_transient($cache_key);
    if ($cached_content !== false) {
        wp_send_json_success($cached_content);
        return;
    }
    
    $settings = get_option('article_tabs_settings', array(
        'posts_per_page' => 10,
        'excerpt_length' => 200,
        'cache_time_latest' => 24,     // 最新文章缓存时间(小时)
        'cache_time_updated' => 24,    // 最近修改缓存时间(小时)
        'cache_time_popular' => 24,    // 热门文章缓存时间(小时)
        'cache_time_rss' => 5,         // RSS内容缓存时间(小时)
    ));
    
    // 处理自定义标签
    if (strpos($tab, 'custom_') === 0) {
        $custom_tabs = get_option('article_custom_tabs', array());
        $tab_index = substr($tab, 7);
        
        if (isset($custom_tabs[$tab_index])) {
            $tab_content = $custom_tabs[$tab_index]['content'];
            if (!empty($tab_content)) {
                $content = array(
                    'content' => do_shortcode($tab_content),
                    'pagination' => ''
                );
                // 使用设置的RSS缓存时间
                $cache_time = intval($settings['cache_time_rss']) * HOUR_IN_SECONDS;
                set_transient($cache_key, $content, $cache_time);
                wp_send_json_success($content);
                return;
            }
        }
    }
    
    // 如果不是自定义标签或自定义标签内容为空，则处理默认文章列表
    $args = array(
        'posts_per_page' => $settings['posts_per_page'],
        'paged' => $page,
        'post_status' => 'publish'
    );
    
    // 根据不同标签设置查询参数和缓存时间
    switch ($tab) {
        case 'latest':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            $cache_time = intval($settings['cache_time_latest']) * HOUR_IN_SECONDS;
            break;
            
        case 'updated':
            $args['orderby'] = 'modified';
            $args['order'] = 'DESC';
            $cache_time = intval($settings['cache_time_updated']) * HOUR_IN_SECONDS;
            break;
            
        case 'popular':
            $args['orderby'] = 'comment_count';
            $args['order'] = 'DESC';
            $cache_time = intval($settings['cache_time_popular']) * HOUR_IN_SECONDS;
            break;
            
        default:
            $cache_time = 24 * HOUR_IN_SECONDS; // 默认24小时
    }
    
    $query = new WP_Query($args);
    $output = '';
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $output .= get_article_item_html($settings['excerpt_length']);
        }
    } else {
        $output = '<div class="no-posts">暂无文章</div>';
    }
    
    // 生成分页HTML
    $pagination = '';
    if ($query->max_num_pages > 1) {
        $pagination = get_articles_pagination_html($query->max_num_pages, $page);
    }
    
    wp_reset_postdata();
    
    $content = array(
        'content' => $output,
        'pagination' => $pagination
    );
    
    // 设置缓存
    set_transient($cache_key, $content, $cache_time);
    
    wp_send_json_success($content);
}

// 在文章发布、更新或删除时清除相关缓存
function clear_article_tabs_cache($post_id) {
    // 清除最新文章和最近修改的缓存
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like('_transient_article_tabs_latest_') . '%',
            $wpdb->esc_like('_transient_article_tabs_updated_') . '%'
        )
    );
}
add_action('save_post', 'clear_article_tabs_cache');
add_action('delete_post', 'clear_article_tabs_cache');

// 在评论状态改变时清除热门文章缓存
function clear_popular_articles_cache() {
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_article_tabs_popular_') . '%'
        )
    );
}
add_action('wp_insert_comment', 'clear_popular_articles_cache');
add_action('edit_comment', 'clear_popular_articles_cache');
add_action('delete_comment', 'clear_popular_articles_cache');

// 生成文章项的HTML
function get_article_item_html($excerpt_length) {
    ob_start();
    ?>
    <article class="article-item">
        <h2 class="article-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        <div class="article-meta">
            <span class="post-date"><?php echo get_the_date(); ?></span>
            <?php if (get_the_modified_date() != get_the_date()) : ?>
                <span class="post-modified">最后修改: <?php echo get_the_modified_date(); ?></span>
            <?php endif; ?>
            <span class="post-comments"><?php comments_number('0 评论', '1 评论', '% 评论'); ?></span>
        </div>
        <div class="article-excerpt">
            <?php echo wp_trim_words(get_the_excerpt(), $excerpt_length); ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

// 生成分页HTML
function get_articles_pagination_html($max_pages, $current_page) {
    $output = '<div class="articles-pagination">';
    
    // 上一页
    if ($current_page > 1) {
        $output .= sprintf(
            '<a href="#" class="page-number prev" data-page="%d">&laquo; 上一页</a>',
            $current_page - 1
        );
    }
    
    // 页码
    for ($i = 1; $i <= $max_pages; $i++) {
        if ($i == $current_page) {
            $output .= sprintf(
                '<span class="page-number active">%d</span>',
                $i
            );
        } else {
            $output .= sprintf(
                '<a href="#" class="page-number" data-page="%d">%d</a>',
                $i,
                $i
            );
        }
    }
    
    // 下一页
    if ($current_page < $max_pages) {
        $output .= sprintf(
            '<a href="#" class="page-number next" data-page="%d">下一页 &raquo;</a>',
            $current_page + 1
        );
    }
    
    $output .= '</div>';
    return $output;
} 