<?php
// 设置主题默认特性
function my_theme_setup()
{
  // 为主题添加特色图像支持
  add_theme_support('post-thumbnails');

  // 为文章和页面添加自定义标题支持
  add_theme_support('title-tag');

  // 注册导航菜单
  register_nav_menus(
    array(
      'primary' => 'Header Menu', // 修改为顶部菜单
      'sidebar' => 'Sidebar Menu'  // 新增侧边栏菜单
    )
  );

  // 为主题添加HTML5支持
  add_theme_support(
    'html5',
    array(
      'search-form',
      'comment-form',
      'comment-list',
      'gallery',
      'caption',
    )
  );

  // 添加对Gutenberg编辑器的宽度支持
  add_theme_support('align-wide');
}
add_action('after_setup_theme', 'my_theme_setup');

// 将样式表和脚本加入到队列中
function my_theme_enqueue_scripts()
{
  // 注册和加载主样式表
  wp_enqueue_style('my-theme-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));

  // 在投稿页面加载 Markdown 编辑器相关文件
  if (is_page_template('page-submit-post.php')) {
      wp_enqueue_script('markdown-editor', get_template_directory_uri() . '/js/markdown-editor.js', array('jquery'), time(), true);
  }

  // 在有评论的页面加载 Markdown 编辑器
  if (is_singular() && comments_open()) {
      wp_enqueue_script('markdown-editor', get_template_directory_uri() . '/js/markdown-editor.js', array('jquery'), time(), true);
  }
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_scripts');

// 注册小工具区域
function my_theme_widgets_init()
{
  register_sidebar(
    array(
      'name' => 'Primary Sidebar',
      'id' => 'primary-sidebar',
      'before_widget' => '<div id="%1$s" class="widget %2$s">',
      'after_widget' => '</div>',
      'before_title' => '<h3 class="widget-title">',
      'after_title' => '</h3>',
    )
  );

  register_sidebar(
    array(
      'name' => 'Footer Widgets',
      'id' => 'footer-widgets',
      'before_widget' => '<div id="%1$s" class="widget %2$s">',
      'after_widget' => '</div>',
      'before_title' => '<h3 class="widget-title">',
      'after_title' => '</h3>',
    )
  );
}
add_action('widgets_init', 'my_theme_widgets_init');

// 添加BootStrap
function mytheme_enqueue_scripts()
{
  // 注册Bootstrap CSS文件
  wp_register_style('bootstrap', get_template_directory_uri() . '/css/bootstrap.min.css', array(), '5.3.0');
  // 将Bootstrap CSS文件加入队列
  wp_enqueue_style('bootstrap');

  // 注册Bootstrap JavaScript文件，并添加jQuery作为依赖
  wp_register_script('bootstrap', get_template_directory_uri() . '/js/bootstrap.min.js', array('jquery'), '5.3.0', true);
  // 将Bootstrap JavaScript文件加
  wp_enqueue_script('bootstrap');

  // 注册自定义脚本
  wp_register_script('custom-scripts', get_template_directory_uri() . '/js/custom-scroll-sidebar-scripts.js', array('jquery'), '1.0.0', true);

  // 将jQuery和自定义脚本添加到队列
  wp_enqueue_script('jquery');
  wp_enqueue_script('custom-scripts');
  // 注册search-form.js
  wp_register_script('search-form', get_template_directory_uri() . '/search-form.js', array(), '1.0', true);
  wp_enqueue_script('search-form');
}
add_action('wp_enqueue_scripts', 'mytheme_enqueue_scripts');

// 创建个性设置自定义字段用于保存备案信息
function my_customizer_settings($wp_customize) {
  // 添加自定义部分
  $wp_customize->add_section('my_custom_section', array(
      'title' => '《求和!李姐万岁!》个性设置',
      'priority' => 30,
  ));

  // 添加自定义设置
  $wp_customize->add_setting('beian_info', array(
      'default' => '',
      'sanitize_callback' => 'sanitize_text_field',
  ));

  // 添加自定义控件
  $wp_customize->add_control(new WP_Customize_Control(
      $wp_customize,
      'beian_info_control',
      array(
          'label' => '备案信息',
          'section' => 'my_custom_section',
          'settings' => 'beian_info',
          'type' => 'text',
      )
  ));
}
add_action('customize_register', 'my_customizer_settings');

class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
  function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
      $url = $item->url;
      $title = $item->title;
      $output .= "<a href='$url'><li class='list-group-item'>$title</li></a>";
  }
}

// 检查菜单是否为空的函数
function is_menu_empty($location) {
  $menu_locations = get_nav_menu_locations();
  if (isset($menu_locations[$location])) {
      $menu = wp_get_nav_menu_object($menu_locations[$location]);
      if ($menu && !empty($menu->term_id)) {
          $menu_items = wp_get_nav_menu_items($menu->term_id);
          return empty($menu_items);
      }
  }
  return true;
}

function github_com_zhaoolee_gitbook_for_wordpress_widgets_init() {
  register_sidebar( array(
      'name'          => '《求和!李姐万岁!》主题自定义小部件',
      'id'            => 'right-sidebar',
      'before_widget' => '<div class="widget">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3 class="widget-title">',
      'after_title'   => '</h3>',
  ) );
}
add_action( 'widgets_init', 'github_com_zhaoolee_gitbook_for_wordpress_widgets_init' );

// 添加前端投稿功能
function add_frontend_submission_form() {
    if (!is_user_logged_in()) {
        return;
    }
    
    wp_enqueue_script('markdown-editor', get_template_directory_uri() . '/js/markdown-editor.js', array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'add_frontend_submission_form');

// 处理文章提交
function handle_frontend_submission() {
    if (!isset($_POST['submit_post_nonce']) || 
        !wp_verify_nonce($_POST['submit_post_nonce'], 'submit_post')) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    $post_data = array(
        'post_title'    => sanitize_text_field($_POST['post_title']),
        'post_content'  => wp_kses_post($_POST['post_content']),
        'post_status'   => 'pending',  // 设置为待审核状态
        'post_author'   => get_current_user_id(),
        'post_type'     => 'post'
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id) {
        // 处理分类
        if (!empty($_POST['cat'])) {
            wp_set_post_categories($post_id, array(intval($_POST['cat'])));
        }

        // 处理标签
        if (!empty($_POST['post_tags'])) {
            $tags = explode(',', sanitize_text_field($_POST['post_tags']));
            $tags = array_map('trim', $tags);
            wp_set_post_tags($post_id, $tags);
        }

        // 添加用户通知
        add_user_notification(
            get_current_user_id(),
            'post_submitted',
            sprintf('您的章《%s》已提交，等待审核。', $post_data['post_title'])
        );

        // 通知管理员
        $admin_email = get_option('admin_email');
        $subject = '新文章待审核';
        $message = sprintf(
            '新文章《%s》等待审核。\n作者：%s',
            $post_data['post_title'],
            wp_get_current_user()->display_name
        );
        wp_mail($admin_email, $subject, $message);

        // 重定向到用户中心的文章列表
        wp_redirect(add_query_arg(
            array('tab' => 'posts', 'submitted' => '1'),
            get_permalink(get_page_by_path('user-center'))
        ));
        exit;
    }
}
add_action('init', 'handle_frontend_submission');

// 添加文章版本控制
function save_post_version($post_id) {
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    $post = get_post($post_id);
    if ($post->post_type === 'revision') {
        return;
    }
    
    wp_save_post_revision($post_id);
}
add_action('post_updated', 'save_post_version');

// 注册用户中心的样式和脚本
function register_user_center_assets() {
    wp_register_style(
        'user-center-style',
        get_template_directory_uri() . '/assets/css/user-center.css',
        array(),
        '1.0.0'
    );

    wp_register_script(
        'user-center-script',
        get_template_directory_uri() . '/assets/js/user-center.js',
        array('jquery'),
        '1.0.0',
        true
    );

    // 只在用户中心页面和编辑页面加载
    if (is_page('user-center') || is_page('edit-post') || is_page('comment')) {
        wp_enqueue_style('user-center-style');
        wp_enqueue_script('user-center-script');
        
        wp_localize_script('user-center-script', 'userCenter', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('user-center-nonce')
        ));
    }

    // 在编辑页面和投稿页面加载 Markdown 编辑器资源
    if (is_page('edit-post') || is_page('submit-post')) {
        wp_enqueue_style(
            'markdown-editor',
            get_template_directory_uri() . '/assets/css/markdown-editor.css',
            array(),
            '1.0.0'
        );
        wp_enqueue_script(
            'markdown-editor',
            get_template_directory_uri() . '/assets/js/markdown-editor.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'register_user_center_assets');

// 修改论链接，定向到前台
function custom_comment_link($link, $comment) {
    // 如果用户已登录且是自己的评论，重定向到用户中心的评论标签页
    if (is_user_logged_in() && get_current_user_id() == $comment->user_id) {
        return get_permalink(get_page_by_path('user-center')) . '?tab=comments#comment-' . $comment->comment_ID;
    }
    return $link;
}
add_filter('get_comment_link', 'custom_comment_link', 10, 2);

// 修改个人资料链接
function custom_profile_link($link) {
    if (is_user_logged_in()) {
        return get_permalink(get_page_by_path('user-center')) . '?tab=profile';
    }
    return $link;
}
add_filter('edit_profile_url', 'custom_profile_link');

// 修改评论处理函数
function parse_comment_markdown($content) {
    // 使用已有的前端 Markdown 编辑器的解析功能
    wp_enqueue_script('markdown-editor', get_template_directory_uri() . '/js/markdown-editor.js', array('jquery'), time(), true);
    return $content;
}
add_filter('comment_text', 'parse_comment_markdown', 5);

// 修改评论模板函数
function custom_comment_template($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    ?>
    <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
        <article class="comment-body">
            <footer class="comment-meta">
                <div class="comment-author vcard">
                    <?php echo get_avatar($comment, 60); ?>
                    <?php printf('<b class="fn">%s</b>', get_comment_author_link()); ?>
                </div>

                <div class="comment-metadata">
                    <time datetime="<?php comment_time('c'); ?>">
                        <?php printf('%1$s %2$s', get_comment_date(), get_comment_time()); ?>
                    </time>
                    <?php
                    if (is_user_logged_in() && 
                        ($comment->user_id === get_current_user_id() || current_user_can('manage_options'))) {
                        echo ' | <a href="javascript:void(0);" onclick="editComment(' . $comment->comment_ID . ', \'' . 
                             esc_attr(str_replace(array("\r\n", "\r", "\n"), "\\n", $comment->comment_content)) . 
                             '\')" class="comment-edit-link">编辑</a>';
                        
                        if (current_user_can('manage_options')) {
                            echo ' | <a href="javascript:void(0);" onclick="deleteComment(' . 
                                 $comment->comment_ID . ')" class="comment-delete-link">删除</a>';
                        }
                    }
                    ?>
                </div>

                <?php if ('0' == $comment->comment_approved) : ?>
                    <p class="comment-awaiting-moderation">您的评论正在等待审核。</p>
                <?php endif; ?>
            </footer>

            <div class="comment-content markdown-body" id="comment-content-<?php echo $comment->comment_ID; ?>">
                <?php comment_text(); ?>
            </div>

            <div class="comment-edit-form" id="comment-edit-form-<?php echo $comment->comment_ID; ?>" style="display: none;">
                <textarea id="comment-edit-textarea-<?php echo $comment->comment_ID; ?>" class="comment-edit-textarea"></textarea>
                <div class="comment-edit-buttons">
                    <button onclick="saveComment(<?php echo $comment->comment_ID; ?>)" class="save-comment">保存</button>
                    <button onclick="cancelEdit(<?php echo $comment->comment_ID; ?>)" class="cancel-edit">取消</button>
                </div>
            </div>

            <div class="reply">
                <?php
                comment_reply_link(array_merge($args, array(
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth']
                )));
                ?>
            </div>
        </article>
    </li>

    <?php
    static $js_added = false;
    if (!$js_added) {
        ?>
        <script>
        function editComment(commentId, content) {
            // 显示编辑表单
            document.getElementById('comment-content-' + commentId).style.display = 'none';
            document.getElementById('comment-edit-form-' + commentId).style.display = 'block';
            document.getElementById('comment-edit-textarea-' + commentId).value = content.replace(/\\n/g, "\n");
        }

        function cancelEdit(commentId) {
            // 取消编辑
            document.getElementById('comment-content-' + commentId).style.display = 'block';
            document.getElementById('comment-edit-form-' + commentId).style.display = 'none';
        }

        function saveComment(commentId) {
            var content = document.getElementById('comment-edit-textarea-' + commentId).value;
            
            // 发送AJAX请求
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'edit_comment',
                    comment_id: commentId,
                    content: content,
                    nonce: '<?php echo wp_create_nonce('edit-comment'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // 更新评论内容
                        var contentDiv = document.getElementById('comment-content-' + commentId);
                        contentDiv.innerHTML = response.data.content;
                        // 显示成功消息
                        alert(response.data.message);
                        // 隐藏编辑表单
                        cancelEdit(commentId);
                        // 添加待审核提示
                        if (response.data.message.includes('等待审核')) {
                            var commentBody = document.getElementById('comment-' + commentId);
                            if (!commentBody.querySelector('.comment-awaiting-moderation')) {
                                var moderationNote = document.createElement('p');
                                moderationNote.className = 'comment-awaiting-moderation';
                                moderationNote.textContent = '您的评论正在等待审核。';
                                commentBody.querySelector('.comment-meta').appendChild(moderationNote);
                            }
                        }
                    } else {
                        alert('更新失败：' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        xhr: xhr.responseText
                    });
                    alert('更新失败，请稍后重试。\n错误信息：' + error + '\n状态：' + xhr.status + '\n响应：' + xhr.responseText);
                }
            });
        }

        function deleteComment(commentId) {
            if (confirm('确定要删除这条评论吗？')) {
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'delete_comment',
                        comment_id: commentId,
                        nonce: '<?php echo wp_create_nonce('delete-comment'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            jQuery('#comment-' + commentId).fadeOut(function() {
                                jQuery(this).remove();
                            });
                        } else {
                            alert('删除失败：' + response.data);
                        }
                    },
                    error: function() {
                        alert('删除失败，请稍后重试。');
                    }
                });
            }
        }
        </script>
        <?php
        $js_added = true;
    }
}

// 添加 Markdown 预览功能
function ajax_preview_markdown_comment() {
    check_ajax_referer('preview-comment', 'nonce');
    
    if (!isset($_POST['content'])) {
        wp_send_json_error('No content provided');
        return;
    }

    $content = wp_kses_post($_POST['content']);
    // 直接返回内容，让前端的 Markdown 编辑器处理渲染
    wp_send_json_success($content);
}
add_action('wp_ajax_preview_markdown_comment', 'ajax_preview_markdown_comment');
add_action('wp_ajax_nopriv_preview_markdown_comment', 'ajax_preview_markdown_comment');

// 修改评论提交处理
function handle_comment_submission($comment_ID, $comment_approved, $commentdata) {
    if (!is_user_logged_in()) {
        return;
    }

    require_once get_template_directory() . '/inc/user-center-functions.php';

    // 如果是回复评论，通知原评论作者
    if ($commentdata['comment_parent']) {
        $parent_comment = get_comment($commentdata['comment_parent']);
        if ($parent_comment && $parent_comment->user_id) {
            add_user_notification(
                $parent_comment->user_id,
                sprintf('您的评论收到了新回复：%s', wp_trim_words($commentdata['comment_content'], 20)),
                'comment_reply'
            );
        }
    }

    // 如果评论需要审核，通知评论作者
    if ($comment_approved === '0') {
        add_user_notification(
            get_current_user_id(),
            '您的评论已提交，等待审核。',
            'comment_pending'
        );
    }

    // 设置评论提交后的重定向URL
    add_filter('comment_post_redirect', function($location) use ($comment_ID) {
        $comment = get_comment($comment_ID);
        return get_permalink($comment->comment_post_ID) . '#comment-' . $comment_ID;
    });
}
add_action('comment_post', 'handle_comment_submission', 10, 3);

// 添加修订版本处理
function handle_revision_approval() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['approve_revision']) && isset($_POST['revision_id'])) {
        $revision_id = intval($_POST['revision_id']);
        $revision = wp_get_post_revision($revision_id);
        
        if ($revision) {
            // 更新原文章内容
            $update_data = array(
                'ID' => $revision->post_parent,
                'post_title' => $revision->post_title,
                'post_content' => $revision->post_content
            );
            
            wp_update_post($update_data);
            
            // 通知修订作者
            add_user_notification(
                $revision->post_author,
                sprintf('您对文章《%s》的修改已被通过。', get_the_title($revision->post_parent)),
                'revision_approved'
            );
        }
    }
}
add_action('admin_init', 'handle_revision_approval');

// 添加修订对比功能
function get_revision_diff($revision_id) {
    $revision = wp_get_post_revision($revision_id);
    if (!$revision) {
        return '';
    }

    $parent_id = $revision->post_parent;
    $parent = get_post($parent_id);
    
    // 获文本差异
    $title_diff = wp_text_diff(
        $parent->post_title,
        $revision->post_title,
        array('show_split_view' => true)
    );
    
    $content_diff = wp_text_diff(
        $parent->post_content,
        $revision->post_content,
        array('show_split_view' => true)
    );

    $output = '<div class="revision-diff">';
    if ($title_diff) {
        $output .= '<h4>标题修改</h4>' . $title_diff;
    }
    if ($content_diff) {
        $output .= '<h4>内容修改</h4>' . $content_diff;
    }
    $output .= '</div>';

    return $output;
}

// 添加修订历史小部件
function add_revision_history_widget() {
    global $post;
    if ($post && is_single()) {
        $revisions = wp_get_post_revisions($post->ID);
        if ($revisions) {
            echo '<div class="widget revision-history-widget">';
            echo '<h3>修订历史</h3>';
            echo '<ul>';
            foreach ($revisions as $revision) {
                $author = get_userdata($revision->post_author);
                echo sprintf(
                    '<li>%s 由 %s 修改 <a href="#" class="show-diff" data-revision="%d">查看异</a></li>',
                    get_the_modified_time('Y-m-d H:i', $revision),
                    $author->display_name,
                    $revision->ID
                );
            }
            echo '</ul>';
            echo '</div>';
        }
    }
}
add_action('widgets_init', function() {
    register_sidebar(array(
        'name' => '修订史小部件',
        'id' => 'revision-history',
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
        'before_title' => '<h3>',
        'after_title' => '</h3>'
    ));
});

// 添加文章状态标签
function get_post_status_label($status) {
    $status_labels = array(
        'publish' => '已发',
        'pending' => '待审核',
        'draft' => '草稿',
        'pending-revision' => '改待审核',
        'private' => '私密',
        'trash' => '已删除'
    );
    return isset($status_labels[$status]) ? $status_labels[$status] : $status;
}

// 添加成功提示消息
function add_submission_notice() {
    if (isset($_GET['submitted']) && $_GET['submitted'] == '1') {
        echo '<div class="notice notice-success"><p>文章提交成功，等待审核。</p></div>';
    }
    if (isset($_GET['edited']) && $_GET['edited'] == '1') {
        echo '<div class="notice notice-success"><p>文章修改已提交，等待审核。</p></div>';
    }
}
add_action('admin_notices', 'add_submission_notice');

// 添加 AJAX 处理修订历史差异显示
function ajax_get_revision_diff() {
    check_ajax_referer('user-center-nonce', 'nonce');
    
    $revision_id = intval($_POST['revision_id']);
    $revision = wp_get_post_revision($revision_id);
    
    if (!$revision) {
        wp_send_json_error('修订版本不存在');
        return;
    }

    $parent = get_post($revision->post_parent);
    
    // 获取差异对比
    $title_diff = wp_text_diff(
        $parent->post_title,
        $revision->post_title,
        array('show_split_view' => true)
    );
    
    $content_diff = wp_text_diff(
        $parent->post_content,
        $revision->post_content,
        array('show_split_view' => true)
    );

    $output = '<div class="revision-diff-content">';
    if ($title_diff) {
        $output .= '<div class="diff-title"><h4>标题修改</h4>' . $title_diff . '</div>';
    }
    if ($content_diff) {
        $output .= '<div class="diff-content"><h4>内容修改</h4>' . $content_diff . '</div>';
    }
    
    // 添加修改说明
    $edit_summary = get_post_meta($revision->ID, '_edit_summary', true);
    if ($edit_summary) {
        $output .= '<div class="diff-summary"><h4>修改说明</h4><p>' . esc_html($edit_summary) . '</p></div>';
    }
    
    $output .= '</div>';
    
    wp_send_json_success($output);
}
add_action('wp_ajax_get_revision_diff', 'ajax_get_revision_diff');

// 处理文章编辑提交
function handle_post_edit() {
    if (!isset($_POST['edit_post_nonce']) || !wp_verify_nonce($_POST['edit_post_nonce'], 'edit_post')) {
        wp_die('安全验证失败');
    }

    require_once get_template_directory() . '/inc/user-center-functions.php';

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_title = isset($_POST['post_title']) ? sanitize_text_field($_POST['post_title']) : '';
    $post_content = isset($_POST['post_content']) ? wp_kses_post($_POST['post_content']) : '';
    $edit_summary = isset($_POST['edit_summary']) ? sanitize_text_field($_POST['edit_summary']) : '';

    // 获取原文章
    $post = get_post($post_id);
    if (!$post) {
        wp_die('文章不存在');
    }

    // 创建新的修订版本
    $revision_data = array(
        'post_type'    => 'revision',
        'post_title'   => $post_title,
        'post_content' => $post_content,
        'post_parent'  => $post_id,
        'post_author'  => get_current_user_id(),
        'post_status'  => 'pending'  // 修订版本状态为待审核
    );

    // 插入修订版本
    $revision_id = wp_insert_post($revision_data);

    if ($revision_id) {
        // 保存修改说明到修订版本的元数据
        update_post_meta($revision_id, '_edit_summary', $edit_summary);
        
        // 通知管理员
        $admin_users = get_users(array('role' => 'administrator'));
        foreach ($admin_users as $admin) {
            add_user_notification(
                $admin->ID,
                sprintf('文章《%s》有新编辑待审核', $post->post_title),
                'edit_post'
            );
        }

        // 通知编辑者
        add_user_notification(
            get_current_user_id(),
            sprintf('您对文章《%s》的修改已提交，等待管理员审核。', $post->post_title),
            'edit_submitted'
        );

        wp_redirect(add_query_arg('edited', '1', get_permalink($post_id)));
        exit;
    }

    wp_die('保存修订版本失败');
}
add_action('admin_post_edit_post', 'handle_post_edit');
add_action('admin_post_nopriv_edit_post', 'handle_post_edit');

// 添加待审核修订管理菜单
function add_pending_revisions_menu() {
    add_menu_page(
        '待审核修订', // 页面标题
        '待审核修订', // 菜单标题
        'manage_options', // 权限要求
        'pending-revisions', // 菜单slug
        'display_pending_revisions_page', // 回调函数
        'dashicons-backup', // 图标
        25 // 位
    );
}
add_action('admin_menu', 'add_pending_revisions_menu');

// 显示待审核修订页面
function display_pending_revisions_page() {
    if (!current_user_can('manage_options')) {
        wp_die('您没有权限访问此页面');
    }

    // 引入用户中心函数
    require_once get_template_directory() . '/inc/user-center-functions.php';

    // 处理审核操作
    if (isset($_POST['action']) && isset($_POST['revision_id']) && check_admin_referer('approve_revision')) {
        $revision_id = intval($_POST['revision_id']);
        $revision = wp_get_post_revision($revision_id);
        
        if ($revision) {
            if ($_POST['action'] === 'approve') {
                // 通过修订
                $parent_post = get_post($revision->post_parent);
                $update_data = array(
                    'ID' => $parent_post->ID,
                    'post_title' => $revision->post_title,
                    'post_content' => $revision->post_content
                );
                wp_update_post($update_data);
                
                // 更新修订状态
                wp_update_post(array(
                    'ID' => $revision_id,
                    'post_status' => 'inherit'
                ));

                // 通知作者
                add_user_notification(
                    $revision->post_author,
                    sprintf('您对文章《%s》的修改已被通过', get_the_title($revision->post_parent)),
                    'revision_approved'
                );
                
                echo '<div class="notice notice-success"><p>修订已通过并应用到文章</p></div>';
            } elseif ($_POST['action'] === 'reject') {
                // 拒绝修订
                wp_delete_post_revision($revision_id);
                
                // 通知作者
                add_user_notification(
                    $revision->post_author,
                    sprintf('您对文章《%s》的修改未被通过', get_the_title($revision->post_parent)),
                    'revision_rejected'
                );
                
                echo '<div class="notice notice-warning"><p>修订已被拒绝</p></div>';
            }
        }
    }

    // 修改AJAX处理差异对比的JavaScript代码
    add_action('admin_footer', function() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.view-diff').click(function() {
                var revisionId = $(this).data('revision');
                var parentId = $(this).data('parent');
                
                $.post(ajaxurl, {
                    action: 'get_revision_diff_admin',  // 修改为正确的action名称
                    revision_id: revisionId,
                    parent_id: parentId,
                    nonce: '<?php echo wp_create_nonce("revision-diff-nonce"); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#diff-content').html(response.data);
                        $('#diff-modal').show();
                    }
                });
            });

            $('.close').click(function() {
                $('#diff-modal').hide();
            });

            $(window).click(function(e) {
                if ($(e.target).is('#diff-modal')) {
                    $('#diff-modal').hide();
                }
            });
        });
        </script>
        <?php
    });

    // 获取所有待审核的修订
    $pending_revisions = get_posts(array(
        'post_type' => 'revision',
        'post_status' => 'pending',
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC'
    ));

    ?>
    <div class="wrap">
        <h1>待审核修订</h1>
        
        <?php if (empty($pending_revisions)) : ?>
            <p>目前没有待审核的修订。</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>原文章</th>
                        <th>修改者</th>
                        <th>修时间</th>
                        <th>修改说明</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_revisions as $revision) : 
                        $parent_post = get_post($revision->post_parent);
                        $author = get_user_by('id', $revision->post_author);
                        $edit_summary = get_post_meta($revision->ID, '_edit_summary', true);
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($parent_post->post_title); ?></strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url(get_permalink($parent_post->ID)); ?>" target="_blank">查看原文</a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo esc_html($author->display_name); ?></td>
                            <td><?php echo get_the_modified_time('Y-m-d H:i:s', $revision); ?></td>
                            <td><?php echo esc_html($edit_summary); ?></td>
                            <td>
                                <button type="button" class="button view-diff" 
                                        data-revision="<?php echo $revision->ID; ?>"
                                        data-parent="<?php echo $parent_post->ID; ?>">
                                    查看差异
                                </button>
                                
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('approve_revision'); ?>
                                    <input type="hidden" name="revision_id" value="<?php echo $revision->ID; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="submit" class="button button-primary" value="通过">
                                </form>
                                
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('approve_revision'); ?>
                                    <input type="hidden" name="revision_id" value="<?php echo $revision->ID; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="submit" class="button" value="拒绝">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 差异对比弹窗 -->
            <div id="diff-modal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div id="diff-content"></div>
                </div>
            </div>

            <style>
                .modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.6);
                    z-index: 999999;
                }
                .modal-content {
                    position: relative;
                    background: #fff;
                    margin: 50px auto;
                    padding: 20px;
                    width: 80%;
                    max-height: 80vh;
                    overflow-y: auto;
                }
                .close {
                    position: absolute;
                    right: 20px;
                    top: 10px;
                    font-size: 28px;
                    cursor: pointer;
                }
                .diff-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .diff-table td {
                    padding: 5px;
                    border: 1px solid #ddd;
                }
            </style>
        <?php endif; ?>
    </div>
    <?php
}

// 添加AJAX处理差异对比
function ajax_get_revision_diff_admin() {
    check_admin_referer('revision-diff-nonce', 'nonce');
    
    $revision_id = intval($_POST['revision_id']);
    $parent_id = intval($_POST['parent_id']);
    
    $revision = wp_get_post_revision($revision_id);
    $parent = get_post($parent_id);
    
    if (!$revision || !$parent) {
        wp_send_json_error('无法加载修订版本');
        return;
    }
    
    $title_diff = wp_text_diff(
        $parent->post_title,
        $revision->post_title,
        array('show_split_view' => true)
    );
    
    $content_diff = wp_text_diff(
        $parent->post_content,
        $revision->post_content,
        array('show_split_view' => true)
    );
    
    $output = '<h3>修订差异对比</h3>';
    
    if ($title_diff) {
        $output .= '<h4>标题修改</h4>' . $title_diff;
    }
    
    if ($content_diff) {
        $output .= '<h4>内容修改</h4>' . $content_diff;
    }
    
    $edit_summary = get_post_meta($revision->ID, '_edit_summary', true);
    if ($edit_summary) {
        $output .= '<h4>修改说明</h4><p>' . esc_html($edit_summary) . '</p>';
    }
    
    wp_send_json_success($output);
}
add_action('wp_ajax_get_revision_diff_admin', 'ajax_get_revision_diff_admin');

// 添加一个函数获取文章的最后更新时间
function get_last_update_time($post = null) {
    if (!$post) {
        global $post;
    }
    
    // 获取最新的已批准修订版本
    $revisions = wp_get_post_revisions($post->ID, array(
        'posts_per_page' => 1,
        'orderby' => 'post_modified',
        'order' => 'DESC',
        'post_status' => 'inherit'
    ));

    if (!empty($revisions)) {
        $revision = array_shift($revisions);
        return get_the_modified_time('Y-m-d H:i:s', $revision);
    }
    
    // 如果没有修订版本，返回文章的修改时间
    return get_the_modified_time('Y-m-d H:i:s', $post);
}

// 修改文章元信息的显示
function display_post_meta($post) {
    $update_time = get_last_update_time($post);
    echo sprintf(
        '最后更新于：%s 作者：%s 分类：%s',
        $update_time,
        get_the_author_meta('display_name', $post->post_author),
        get_the_category_list(', ', '', $post->ID)
    );
}

// 添加评论编辑页面模板
function create_comment_edit_page() {
    // 检查评论编辑页面是否存在
    if (!get_page_by_path('comment')) {
        // 创建评论编辑页面
        wp_insert_post(array(
            'post_title' => '编辑评论',
            'post_name' => 'comment',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
            'post_author' => 1,
            'page_template' => 'page-comment.php'
        ));
    }
}
add_action('after_switch_theme', 'create_comment_edit_page');

// 添加AJAX处理评论编辑
function ajax_edit_comment() {
    global $wpdb;
    
    try {
        // 开启错误报告
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // 验证 nonce
        if (!check_ajax_referer('edit-comment', 'nonce', false)) {
            error_log('Comment edit: Nonce verification failed');
            wp_send_json_error('安全验证失败');
            return;
        }
        
        if (!isset($_POST['comment_id']) || !isset($_POST['content'])) {
            error_log('Comment edit: Missing required parameters');
            wp_send_json_error('缺少必要参数');
            return;
        }

        $comment_id = intval($_POST['comment_id']);
        $content = wp_kses_post($_POST['content']);
        
        // 记录接收到的数据
        error_log('Editing comment ID: ' . $comment_id);
        error_log('New content: ' . $content);

        // 获取评论
        $comment = get_comment($comment_id);

        // 检查评论是否存
        if (!$comment) {
            error_log('Comment edit: Comment not found - ID: ' . $comment_id);
            wp_send_json_error('评论不存在');
            return;
        }

        // 检查用户权限
        if (!is_user_logged_in()) {
            error_log('Comment edit: User not logged in');
            wp_send_json_error('请先登录');
            return;
        }

        $current_user_id = get_current_user_id();
        if ($comment->user_id != $current_user_id && !current_user_can('manage_options')) {
            error_log('Comment edit: Permission denied for user ' . $current_user_id);
            wp_send_json_error('没有权限编辑此评论');
            return;
        }

        // 准备更新数据
        $comment_data = array(
            'comment_ID' => $comment_id,
            'comment_content' => $content,
            'comment_approved' => current_user_can('manage_options') ? $comment->comment_approved : '0'
        );

        // 使用 wp_update_comment 更新评论
        $result = wp_update_comment($comment_data);

        if ($result === false) {
            error_log('Comment edit: Update failed - ' . $wpdb->last_error);
            wp_send_json_error('更新失败：' . $wpdb->last_error);
            return;
        }

        // 清除缓存
        clean_comment_cache($comment_id);

        // 获取更新后的评论
        $updated_comment = get_comment($comment_id);
        
        // 准备响应数据
        $response_data = array(
            'content' => apply_filters('get_comment_text', $updated_comment->comment_content),
            'message' => '评论已更新' . (!current_user_can('manage_options') ? '，等待审核' : '')
        );

        // 发送成功响应
        wp_send_json_success($response_data);

    } catch (Exception $e) {
        error_log('Comment edit exception: ' . $e->getMessage());
        wp_send_json_error('发生错误：' . $e->getMessage());
    }
}
add_action('wp_ajax_edit_comment', 'ajax_edit_comment');

// 添加AJAX处理评论删除
function ajax_delete_comment() {
    // 验证nonce
    check_ajax_referer('delete-comment', 'nonce');
    
    // 检查权限
    if (!current_user_can('manage_options')) {
        wp_send_json_error('没有权限删除评论');
        return;
    }

    $comment_id = intval($_POST['comment_id']);
    
    // 删除评论
    $result = wp_delete_comment($comment_id, true);

    if ($result) {
        wp_send_json_success('评论已删除');
    } else {
        wp_send_json_error('删除失败');
    }
}
add_action('wp_ajax_delete_comment', 'ajax_delete_comment');

// 修改默认评论表单
function custom_comment_form_defaults($defaults) {
    // 加载 Markdown 编辑器脚本
    wp_enqueue_script('markdown-editor', get_template_directory_uri() . '/js/markdown-editor.js', array('jquery'), time(), true);

    // 修改评论框
    $defaults['comment_field'] = '
        <p class="comment-form-comment">
            <label for="comment">评论内容</label>
            <div class="markdown-toolbar">
                <button type="button" onclick="insertMarkdown(\'**\', \'**\', \'粗体文本\')" title="粗">B</button>
                <button type="button" onclick="insertMarkdown(\'*\', \'*\', \'斜体文本\')" title="斜体">I</button>
                <button type="button" onclick="insertMarkdown(\'`\', \'`\', \'代码\')" title="代码">Code</button>
                <button type="button" onclick="insertMarkdown(\'\\n```\\n\', \'\\n```\\n\', \'代码块\')" title="代码块">CodeBlock</button>
                <button type="button" onclick="insertMarkdown(\'[\', \'](url)\', \'链接文本\')" title="链接">Link</button>
                <button type="button" onclick="insertMarkdown(\'> \', \'\', \'引用文本\')" title="引用">Quote</button>
            </div>
            <textarea id="comment" name="comment" cols="45" rows="8" required></textarea>
            <div class="markdown-preview" id="comment-preview"></div>
            <button type="button" onclick="previewComment()" class="preview-button">预览</button>
        </p>
        <script>
        function insertMarkdown(prefix, suffix, placeholder) {
            const textarea = document.getElementById("comment");
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const before = text.substring(0, start);
            const selected = text.substring(start, end);
            const after = text.substring(end);

            let insertion = selected || placeholder;
            textarea.value = before + prefix + insertion + suffix + after;
            
            const newPosition = start + prefix.length + insertion.length;
            textarea.setSelectionRange(newPosition, newPosition);
            textarea.focus();
        }

        function previewComment() {
            const content = document.getElementById("comment").value;
            const previewDiv = document.getElementById("comment-preview");
            
            if (typeof markdownToHtml === "function") {
                previewDiv.innerHTML = markdownToHtml(content);
                previewDiv.style.display = "block";
            }
        }
        </script>
        <style>
        .markdown-toolbar {
            padding: 5px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-bottom: 0;
        }
        .markdown-toolbar button {
            padding: 5px 10px;
            margin-right: 5px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 3px;
            cursor: pointer;
        }
        .markdown-toolbar button:hover {
            background: #635753;
            color: #fff;
        }
        .markdown-preview {
            display: none;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }
        .preview-button {
            margin-top: 10px;
            padding: 5px 15px;
            background: #635753;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        #comment {
            border-radius: 0 0 4px 4px;
            margin-top: 0;
        }
        </style>
    ';

    return $defaults;
}
add_filter('comment_form_defaults', 'custom_comment_form_defaults');

// 修改评论内容显示，支持 Markdown
function render_comment_markdown($content) {
    // 加载 markdown-it
    wp_enqueue_script('markdown-it', 'https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js', array(), null, true);
    
    // 添加渲染脚本
    static $script_added = false;
    if (!$script_added) {
        add_action('wp_footer', function() {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                function initMarkdown() {
                    if (typeof markdownit === 'function') {
                        const md = window.markdownit({
                            html: true,
                            linkify: true,
                            typographer: true,
                            breaks: true
                        });

                        // 渲染所有评论内容
                        document.querySelectorAll('.comment-content').forEach(function(element) {
                            const originalContent = element.textContent.trim();
                            if (originalContent) {
                                try {
                                    element.innerHTML = md.render(originalContent);
                                } catch (e) {
                                    console.error('Markdown rendering error:', e);
                                }
                            }
                        });
                    } else {
                        setTimeout(initMarkdown, 100);
                    }
                }
                initMarkdown();
            });
            </script>
            <?php
        }, 99);
        $script_added = true;
    }

    // 将评论内容包装在一个 div 中
    return '<div class="comment-content markdown-body">' . $content . '</div>';
}

// 移除默认的评论内容过滤器
remove_filter('comment_text', 'wpautop', 30);
remove_filter('comment_text', 'wptexturize', 10);

// 添加我们的 Markdown 渲染过滤器
add_filter('comment_text', 'render_comment_markdown', 20);

// 添加 Markdown 样式
function add_markdown_styles() {
    if (is_singular() && comments_open()) {
        ?>
        <style>
        .markdown-body {
            line-height: 1.6;
        }
        .markdown-body p {
            margin: 1em 0;
        }
        .markdown-body code {
            background-color: #f6f8fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
        .markdown-body pre {
            background-color: #f6f8fa;
            padding: 16px;
            border-radius: 3px;
            overflow: auto;
        }
        .markdown-body pre code {
            padding: 0;
            background: none;
        }
        .markdown-body blockquote {
            padding-left: 1em;
            border-left: 4px solid #ddd;
            color: #666;
            margin: 1em 0;
        }
        .markdown-body strong {
            font-weight: bold;
        }
        .markdown-body em {
            font-style: italic;
        }
        .markdown-body ul, .markdown-body ol {
            padding-left: 2em;
            margin: 1em 0;
        }
        .markdown-body img {
            max-width: 100%;
            height: auto;
        }
        </style>
        <?php
    }
}
add_action('wp_head', 'add_markdown_styles');

function add_login_register_styles() {
    // 添加 page-lost-password.php 模板
    if (is_page_template('page-login.php') || 
        is_page_template('page-register.php') || 
        is_page_template('page-lost-password.php')) {
        wp_enqueue_style('login-register-styles', get_template_directory_uri() . '/assets/css/login-register.css');
        // 添加导航菜单样式
        wp_enqueue_style('nav-menu-styles', get_template_directory_uri() . '/style.css');
    }
}
add_action('wp_enqueue_scripts', 'add_login_register_styles');

// 创建登录和注册页面
function create_login_register_pages() {
    // 检查用户中心页面
    if (!get_page_by_path('user-center')) {
        wp_insert_post(array(
            'post_title'    => '用户中心',
            'post_name'     => 'user-center',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'page_template' => 'page-user-center.php'
        ));
    }

    // 检查登录页面
    if (!get_page_by_path('login')) {
        wp_insert_post(array(
            'post_title'    => '登录',
            'post_name'     => 'login',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'page_template' => 'page-login.php'
        ));
    }
    
    // 检查注册页面
    if (!get_page_by_path('register')) {
        wp_insert_post(array(
            'post_title'    => '注册',
            'post_name'     => 'register',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'page_template' => 'page-register.php'
        ));
    }
    
    // 检查忘记密码页面
    if (!get_page_by_path('lost-password')) {
        wp_insert_post(array(
            'post_title'    => '忘记密码',
            'post_name'     => 'lost-password',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'page_template' => 'page-lost-password.php'
        ));
    }
}
add_action('after_switch_theme', 'create_login_register_pages');

// 修改默认的登录URL
function custom_login_url($login_url) {
    $login_page = get_page_by_path('login');
    return $login_page ? get_permalink($login_page) : $login_url;
}
add_filter('login_url', 'custom_login_url', 10, 1);

// 修改默认的注册URL
function custom_register_url($register_url) {
    $register_page = get_page_by_path('register');
    return $register_page ? get_permalink($register_page) : $register_url;
}
add_filter('register_url', 'custom_register_url', 10, 1);

// 添加修改忘记密码URL的函数
function custom_lostpassword_url($lostpassword_url) {
    $lostpassword_page = get_page_by_path('lost-password');
    return $lostpassword_page ? get_permalink($lostpassword_page) : $lostpassword_url;
}
add_filter('lostpassword_url', 'custom_lostpassword_url', 10, 1);

// 修改密码重置邮件中的链接
function custom_password_reset_url($url, $key, $user_login) {
    $lostpassword_page = get_page_by_path('lost-password');
    if ($lostpassword_page) {
        $url = add_query_arg(
            array(
                'action' => 'rp',
                'key' => $key,
                'login' => rawurlencode($user_login)
            ),
            get_permalink($lostpassword_page)
        );
    }
    return $url;
}
add_filter('retrieve_password_url', 'custom_password_reset_url', 10, 3);

// 添加用户中心访问限制和重定向
function check_user_center_access() {
    if (is_page('user-center') && !is_user_logged_in()) {
        $login_page = get_page_by_path('login');
        if ($login_page) {
            wp_redirect(add_query_arg('redirect_to', urlencode(get_permalink(get_page_by_path('user-center'))), get_permalink($login_page)));
            exit;
        }
    }
}
add_action('template_redirect', 'check_user_center_access');

// 添加到现有的 functions.php 中
function ensure_correct_encoding() {
    header('Content-Type: text/html; charset=utf-8');
    // 禁用缓存
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}
add_action('template_redirect', 'ensure_correct_encoding');