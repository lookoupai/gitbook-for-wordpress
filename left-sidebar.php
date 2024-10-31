<?php
// 获取提交的分类slug
$category_slug = isset($_GET['post_category']) ? sanitize_title($_GET['post_category']) : '';

// Select组件和表单
?>
<form method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <select name="post_category" class="post_category form-select custom-select-margin">
        <option value="">所有分类</option>
        <?php
        $categories = get_categories(array('orderby' => 'name', 'order' => 'ASC'));
        foreach ($categories as $category) {
            $selected = ($category_slug == $category->slug) ? 'selected' : '';
            echo '<option value="' . $category->slug . '" ' . $selected . '>' . $category->name . '</option>';
        }
        ?>
    </select>
</form>

<?php
// 如果选择了分类，显示分类文章列表
if ($category_slug) {
    // 构建查询参数
    $query_args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'category_name' => $category_slug
    );

    $post_query = new WP_Query($query_args);

    if ($post_query->have_posts()): ?>
        <ul class="left-sidebar-post-list list-group no-border-radius">
            <?php while ($post_query->have_posts()):
                $post_query->the_post();
                $current_post_id = get_the_ID();
                $is_active = is_single() && $current_post_id == get_queried_object_id();
                $font_weight = $is_active ? 'font-weight-bold' : 'font-weight-normal';
                $active_class = $is_active ? 'gitbook-active' : '';
                
                // 检查文章URL是否包含查询参数
                $post_permalink = get_permalink();
                $separator = (false === strpos($post_permalink, '?')) ? '?' : '&';
                $category_param = $category_slug ? $separator . 'post_category=' . $category_slug : '';
                ?>
                <a href="<?php echo $post_permalink . $category_param; ?>" class="text-decoration-none <?php echo $active_class; ?> list-group-item <?php echo $font_weight; ?>">
                    <li>
                        <?php the_title(); ?>
                    </li>
                </a>
            <?php endwhile; ?>
        </ul>
    <?php endif;
    wp_reset_postdata();
} else {
    // 如果没有选择分类，显示侧边栏菜单
    ?>
    <ul class="left-sidebar-post-list list-group no-border-radius">
        <?php
        $menu_args = array(
            'theme_location' => 'sidebar', // 修改为侧边栏菜单
            'container' => false,
            'items_wrap' => '%3$s',
            'walker' => new Custom_Walker_Nav_Menu(),
        );
        wp_nav_menu($menu_args);
        ?>
    </ul>
<?php } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var postCategorySelects = document.querySelectorAll('.post_category');

        // 为每个匹配的<select>元素添加事件监听器
        postCategorySelects.forEach(function (postCategorySelect) {
            postCategorySelect.addEventListener('change', function () {
                var form = this.form;
                var actionURL = new URL(form.action);
                if (this.value === '') {
                    this.name = '';
                    // 跳转到根路由
                    window.location.href = actionURL.origin + actionURL.pathname;
                } else {
                    this.name = 'post_category';
                    actionURL.searchParams.set('post_category', this.value);
                    form.action = actionURL.toString();
                    form.submit();
                }
            });

            // 获取当前URL中的查询参数
            var searchParams = new URLSearchParams(window.location.search);

            // 如果URL中包含'category_name'查询参数，设置选择器的值
            if (searchParams.has('category_name')) {
                postCategorySelect.value = searchParams.get('category_name');
            }
        });
    });
</script>