<div class="user-comments-section">
    <h3>我的评论</h3>
    <?php
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $args = array(
        'user_id' => get_current_user_id(),
        'number' => 10,
        'status' => 'all',
        'offset' => ($paged - 1) * 10
    );
    $comments = get_comments($args);
    $total_comments = get_comments(array(
        'user_id' => get_current_user_id(),
        'count' => true
    ));
    
    if ($comments) :
    ?>
        <div class="comments-list">
        <?php foreach ($comments as $comment) :
            $post = get_post($comment->comment_post_ID);
        ?>
            <div class="comment-item" id="comment-<?php echo $comment->comment_ID; ?>">
                <div class="comment-content">
                    <div class="comment-text" id="comment-text-<?php echo $comment->comment_ID; ?>">
                        <?php echo wp_kses_post($comment->comment_content); ?>
                    </div>
                    <form class="edit-comment-form" style="display: none;" 
                          id="edit-form-<?php echo $comment->comment_ID; ?>">
                        <textarea name="comment_content"><?php echo esc_textarea($comment->comment_content); ?></textarea>
                        <input type="hidden" name="comment_id" value="<?php echo $comment->comment_ID; ?>">
                        <?php wp_nonce_field('edit_comment_' . $comment->comment_ID, 'edit_comment_nonce'); ?>
                        <div class="form-buttons">
                            <button type="submit" class="save-comment">保存</button>
                            <button type="button" class="cancel-edit">取消</button>
                        </div>
                    </form>
                </div>
                <div class="comment-meta">
                    <span>发表于：<a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a></span>
                    <span>时间：<?php echo get_comment_date('Y-m-d H:i', $comment->comment_ID); ?></span>
                    <span>状态：<?php echo $comment->comment_approved == '1' ? '已通过' : '待审核'; ?></span>
                    <div class="comment-actions">
                        <a href="#" class="edit-comment" data-comment-id="<?php echo $comment->comment_ID; ?>">编辑</a>
                        <a href="#" class="delete-comment" data-comment-id="<?php echo $comment->comment_ID; ?>">删除</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <?php
        // 分页
        $total_pages = ceil($total_comments / 10);
        if ($total_pages > 1) :
        ?>
        <div class="pagination">
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $paged
            ));
            ?>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <p>暂无评论</p>
    <?php endif; ?>
</div> 