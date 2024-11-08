<div class="user-favorites-section">
    <h3>我的收藏</h3>
    <?php
    $favorites = get_user_meta(get_current_user_id(), 'user_favorites', true);
    $favorites = $favorites ? explode(',', $favorites) : array();
    
    if (!empty($favorites)) :
        $args = array(
            'post__in' => $favorites,
            'post_type' => 'post',
            'posts_per_page' => -1
        );
        $favorite_posts = new WP_Query($args);
        
        if ($favorite_posts->have_posts()) :
            while ($favorite_posts->have_posts()) : $favorite_posts->the_post();
    ?>
        <div class="favorite-item">
            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
            <div class="favorite-meta">
                <span>收藏时间：<?php echo get_post_modified_time('Y-m-d H:i'); ?></span>
                <a href="#" class="remove-favorite" data-post-id="<?php the_ID(); ?>">取消收藏</a>
            </div>
        </div>
    <?php
            endwhile;
            wp_reset_postdata();
        endif;
    else:
    ?>
        <p>暂无收藏</p>
    <?php endif; ?>
</div> 