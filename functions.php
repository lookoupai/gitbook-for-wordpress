<?php
// 按依赖关系顺序加载
require_once get_template_directory() . '/inc/notifications.php';      // 基础功能
require_once get_template_directory() . '/inc/user-center-functions.php'; 
require_once get_template_directory() . '/inc/comments.php';           // 评论功能
require_once get_template_directory() . '/inc/post-submission.php';    
require_once get_template_directory() . '/inc/post-editing.php';       
require_once get_template_directory() . '/inc/voting-functions.php';   
require_once get_template_directory() . '/inc/voting-settings.php';    

// 主题激活时的处理函数
function theme_activation() {
    // 创建必要的数据表
    create_notifications_table();  // 通知表
    create_voting_tables();       // 投票表
    
    // 创建必要的页面
    $pages = array(
        'user-center' => array(
            'title' => '用户中心',
            'template' => 'page-user-center.php'
        ),
        'submit-post' => array(
            'title' => '发布文章',
            'template' => 'page-submit-post.php'
        ),
        'edit-post' => array(
            'title' => '编辑文章',
            'template' => 'page-edit-post.php'
        ),
        'voting' => array(
            'title' => '投票管理',
            'template' => 'page-voting.php'
        )
    );
    
    foreach ($pages as $slug => $page_data) {
        $existing_page = get_page_by_path($slug);
        if (!$existing_page) {
            wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '',
                'page_template' => $page_data['template']
            ));
        }
    }
    
    // 创建默认菜单
    create_default_menus();
    
    // 设置默认选项
    update_option('voting_votes_required', 10);    // 所需投票数
    update_option('voting_approve_ratio', 0.6);    // 通过比例
    update_option('voting_min_register_months', 3); // 最小注册月数
    
    // 刷新重写规则
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'theme_activation');

// 主题停用时的处理函数
function theme_deactivation() {
    // 清理重写规则
    flush_rewrite_rules();
}
add_action('switch_theme', 'theme_deactivation');

// 设置主题默认特性
function my_theme_setup() {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('align-wide');
    
    // 注册导航菜单
    register_nav_menus(array(
        'primary' => __('顶部菜单', 'your-theme-text-domain'),
        'sidebar' => __('侧边栏菜单', 'your-theme-text-domain'),
        'footer' => __('底部菜单', 'your-theme-text-domain')
    ));

    // HTML5支持
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'my_theme_setup');

// 资源加载
function my_theme_enqueue_scripts() {
    // 主样式表
    wp_enqueue_style('my-theme-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));

    // Bootstrap
    wp_enqueue_style('bootstrap', get_template_directory_uri() . '/css/bootstrap.min.css', array(), '5.3.0');
    wp_enqueue_script('bootstrap', get_template_directory_uri() . '/js/bootstrap.min.js', array('jquery'), '5.3.0', true);

    // 自定义脚本和样式
    wp_enqueue_script('custom-scripts', get_template_directory_uri() . '/js/custom-scroll-sidebar-scripts.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('search-form', get_template_directory_uri() . '/search-form.js', array(), '1.0', true);
    wp_enqueue_style('nav-menu-styles', get_template_directory_uri() . '/assets/css/nav-menu.css');

    // 条件加载
    if (is_page_template(array('page-login.php', 'page-register.php', 'page-lost-password.php'))) {
        wp_enqueue_style('login-register-styles', get_template_directory_uri() . '/assets/css/login-register.css');
    }

    // 菜单样式
    wp_enqueue_style('menu-styles', get_template_directory_uri() . '/assets/css/menu.css', array(), '1.0.0', 'all');

    // 根据页面类型加载对应的样式和脚本
    if (is_singular() && comments_open()) {
        wp_enqueue_style('comments-style', get_template_directory_uri() . '/assets/css/comments.css');
    }

    if (is_page_template('page-edit-post.php')) {
        wp_enqueue_style('edit-post-style', get_template_directory_uri() . '/assets/css/edit-post.css');
    }

    if (is_page_template('page-submit-post.php')) {
        wp_enqueue_style('submit-post-style', get_template_directory_uri() . '/assets/css/submit-post.css');
    }
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_scripts');

// 小工具注册
function my_theme_widgets_init() {
    register_sidebar(array(
        'name'          => '《求和!李姐万岁!》主题自定义小部件',
        'id'            => 'right-sidebar',
        'before_widget' => '<div class="widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'my_theme_widgets_init');

// 创建默认菜单
function create_default_menus() {
    // 创建顶部菜单
    $primary_menu_name = '顶部菜单';
    $primary_menu_exists = wp_get_nav_menu_object($primary_menu_name);
    
    if (!$primary_menu_exists) {
        $primary_menu_id = wp_create_nav_menu($primary_menu_name);
        
        // 添加默认菜单项
        wp_update_nav_menu_item($primary_menu_id, 0, array(
            'menu-item-title' => '首页',
            'menu-item-url' => home_url('/'),
            'menu-item-status' => 'publish',
            'menu-item-type' => 'custom'
        ));
        
        // 分配菜单到位置
        $locations = get_theme_mod('nav_menu_locations', array());
        $locations['primary'] = $primary_menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
    
    // 创建侧边栏菜单
    $sidebar_menu_name = '侧边栏菜单';
    $sidebar_menu_exists = wp_get_nav_menu_object($sidebar_menu_name);
    
    if (!$sidebar_menu_exists) {
        $sidebar_menu_id = wp_create_nav_menu($sidebar_menu_name);
        
        // 添加默认菜单项
        wp_update_nav_menu_item($sidebar_menu_id, 0, array(
            'menu-item-title' => '用户中心',
            'menu-item-url' => home_url('/user-center/'),
            'menu-item-status' => 'publish',
            'menu-item-type' => 'custom'
        ));
        
        // 分配菜单到位置
        $locations = get_theme_mod('nav_menu_locations', array());
        $locations['sidebar'] = $sidebar_menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
}

// URL重写
function custom_login_url($login_url) {
    $page = get_page_by_path('login');
    return $page ? get_permalink($page) : $login_url;
}
add_filter('login_url', 'custom_login_url', 10, 1);

function custom_register_url($register_url) {
    $page = get_page_by_path('register');
    return $page ? get_permalink($page) : $register_url;
}
add_filter('register_url', 'custom_register_url', 10, 1);

function custom_lostpassword_url($lostpassword_url) {
    $page = get_page_by_path('lost-password');
    return $page ? get_permalink($page) : $lostpassword_url;
}
add_filter('lostpassword_url', 'custom_lostpassword_url', 10, 1);

// 访问控制
function check_user_center_access() {
    if (is_page('user-center') && !is_user_logged_in()) {
        wp_redirect(add_query_arg(
            'redirect_to', 
            urlencode(get_permalink(get_page_by_path('user-center'))), 
            get_permalink(get_page_by_path('login'))
        ));
        exit;
    }
}
add_action('template_redirect', 'check_user_center_access');

// 性能优化
function optimize_post_pages() {
    if (is_page_template(['page-submit-post.php', 'page-edit-post.php'])) {
        wp_enqueue_script('jquery');
        
        // 移除不必要的资源
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-block-style');
        wp_dequeue_style('global-styles');
        
        // 预加载
        add_action('wp_head', function() {
            ?>
            <link rel="preload" href="https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js" as="script">
            <link rel="preconnect" href="https://cdn.jsdelivr.net">
            <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
            <?php
        });

        // 延迟加载
        add_filter('script_loader_tag', function($tag, $handle) {
            if (!in_array($handle, ['jquery', 'markdown-it'])) {
                return str_replace(' src', ' defer src', $tag);
            }
            return $tag;
        }, 10, 2);
    }
}
add_action('wp_enqueue_scripts', 'optimize_post_pages', 100);

// 编码和缓存控
function ensure_correct_encoding() {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}
add_action('template_redirect', 'ensure_correct_encoding');

// 禁用新用户注册邮件通知
add_filter('wp_new_user_notification_email', '__return_false');
add_filter('wp_new_user_notification_email_admin', '__return_false');

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

// 导航菜单自定义 Walker 类
class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
        $url = $item->url;
        $title = $item->title;
        $output .= "<a href='$url'><li class='list-group-item'>$title</li></a>";
    }
}

// 添加分类列表样式过滤器
function custom_category_list_style($output) {
    if (empty($output)) {
        return '<div class="uncategorized-label">未分类</div>';
    }
    
    // 修改分类链接的包装方式，使用与文章列表相同的结构
    $output = preg_replace(
        '/<li class="cat-item cat-item-(\d+)"><a href="([^"]+)">([^<]+)<\/a>/',
        '<a href="?post_category=uncategorized" class="text-decoration-none list-group-item font-weight-normal"><li>$3</li></a>',
        $output
    );
    
    // 修改子分类的包装方式
    $output = str_replace(
        '<ul class=\'children\'>', 
        '<ul class="sub-menu list-group">', 
        $output
    );
    
    // 包装在列表容器中
    return '<ul class="left-sidebar-post-list list-group no-border-radius">' . $output . '</ul>';
}
add_filter('wp_list_categories', 'custom_category_list_style');

// 添加分类选择器容器
function wrap_category_dropdown($output) {
    // 保持原有的选择器类名
    return '<div class="category-select-container">' . $output . '</div>';
}
add_filter('wp_dropdown_categories', 'wrap_category_dropdown');