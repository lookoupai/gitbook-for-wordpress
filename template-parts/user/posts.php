<div class="user-posts-section">
    <h3>我的文章</h3>
    <?php
    $args = array(
        'author' => get_current_user_id(),
        'post_type' => 'post',
        'posts_per_page' => 10
    );
    $user_posts = new WP_Query($args);
    
    if ($user_posts->have_posts()) :
        while ($user_posts->have_posts()) : $user_posts->the_post();
    ?>
        <div class="post-item">
            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
            <div class="post-meta">
                <span>状态：<?php 
                    $status = get_post_status();
                    $status_label = array(
                        'publish' => '已发布',
                        'pending' => '待审核',
                        'draft' => '草稿'
                    );
                    echo isset($status_label[$status]) ? $status_label[$status] : $status;
                ?></span>
                <a href="<?php echo home_url('/编辑内容?post_id=' . get_the_ID()); ?>" class="edit-link">编辑</a>
            </div>
        </div>
    <?php
        endwhile;
        wp_reset_postdata();
    else:
    ?>
        <p>暂无文章</p>
    <?php endif; ?>
</div> 