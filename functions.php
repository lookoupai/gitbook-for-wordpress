<?php
// 按依赖关系顺序加载
require_once get_template_directory() . '/inc/notifications.php';      // 基础功能
require_once get_template_directory() . '/inc/user-center-functions.php'; 
require_once get_template_directory() . '/inc/comments.php';           // 评论功能
require_once get_template_directory() . '/inc/post-submission.php';    
require_once get_template_directory() . '/inc/post-editing.php';       
require_once get_template_directory() . '/inc/back-to-top.php';
require_once get_template_directory() . '/inc/login-functions.php';    // 添加登录功能
require_once get_template_directory() . '/inc/ai-review-functions.php';  // AI审核功能
require_once get_template_directory() . '/inc/ai-review-ajax.php';       // AI审核AJAX处理

// 添加主题版本号和升级函数
function theme_upgrade_db() {
    global $wpdb;
    $current_version = get_option('theme_db_version', '0');
    
    if (version_compare($current_version, '1.1', '<')) {
        // 此版本原本创建投票相关表，现已不需要
        // 但仍保持版本号递增，避免重复执行升级
        update_option('theme_db_version', '1.1');
    }

    if (version_compare($current_version, '1.2', '<')) {
        // 此版本原本更新投票理由表，现已不需要
        // 但仍保持版本号递增，避免重复执行升级
        update_option('theme_db_version', '1.2');
    }
    
    if (version_compare($current_version, '1.3', '<')) {
        // 从投票系统升级到AI审核系统
        // 确保创建必要的AI审核页面
        $ai_review_page = get_page_by_path('ai-review');
        if (!$ai_review_page) {
            wp_insert_post(array(
                'post_title' => 'AI内容审核',
                'post_name' => 'ai-review',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '',
                'page_template' => 'page-ai-review.php'
            ));
        }
        
        // 更新版本号
        update_option('theme_db_version', '1.3');
    }
}

// 主题激活时的处理函数
function theme_activation() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // 创建必要的数据表
    create_notifications_table();  // 通知表
    
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
            'title' => '编文章',
            'template' => 'page-edit-post.php'
        ),
        'article-tabs' => array(
            'title' => '文章标签页',
            'template' => 'page-article-tabs.php'
        ),
        'ai-review' => array(
            'title' => 'AI内容审核',
            'template' => 'page-ai-review.php'
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
    
    // 刷新重写规则
    flush_rewrite_rules();
    
    // 执行数据库升级
    theme_upgrade_db();
}
add_action('after_switch_theme', 'theme_activation');

// 主题停用时的处理函数
function theme_deactivation() {
    // 清理重写规则
    flush_rewrite_rules();
}
add_action('switch_theme', 'theme_deactivation');

// 设置题默认特性
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

    // 根据页类型加载对应的样式和脚本
    if (is_singular() && comments_open()) {
        wp_enqueue_style('comments-style', get_template_directory_uri() . '/assets/css/comments.css', array(), '1.0.1', 'all');
        wp_enqueue_script('comment-actions', get_template_directory_uri() . '/assets/js/comment-actions.js', array('jquery'), '1.0', true);
        
        // 添加本地化数据
        wp_localize_script('comment-actions', 'commentVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('comment-action-nonce')
        ));
        
        // 如果在用户中心页面，确保用户中心的样式在评论样式之后加载
        if (is_page_template('page-user-center.php')) {
            wp_enqueue_style('user-center-style', get_template_directory_uri() . '/assets/css/user-center.css', array('comments-style'), '1.0');
            wp_enqueue_script('user-center-script', get_template_directory_uri() . '/assets/js/user-center.js', array('jquery', 'comment-actions'), '1.0', true);
        }
        
        // Markdown 样式
        wp_enqueue_style('github-markdown', 'https://cdn.jsdelivr.net/gh/sindresorhus/github-markdown-css@4.0.0/github-markdown.min.css');
    }

    if (is_page_template('page-edit-post.php')) {
        wp_enqueue_style('edit-post-style', get_template_directory_uri() . '/assets/css/edit-post.css');
        wp_enqueue_script('edit-post-script', get_template_directory_uri() . '/assets/js/edit-post.js', array('jquery'), '1.0', true);
    }

    if (is_page_template('page-submit-post.php')) {
        wp_enqueue_style('edit-post-style', get_template_directory_uri() . '/assets/css/edit-post.css');
        wp_enqueue_style('vditor-css', 'https://cdn.jsdelivr.net/npm/vditor/dist/index.css');
        wp_enqueue_script('vditor', 'https://cdn.jsdelivr.net/npm/vditor/dist/index.min.js', array('jquery'), null, true);
        wp_enqueue_script('submit-post', get_template_directory_uri() . '/assets/js/submit-post.js', array('jquery', 'vditor'), '1.0', true);
        wp_localize_script('submit-post', 'wpVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }

    wp_enqueue_style('post-list-style', get_template_directory_uri() . '/assets/css/post-list.css', array(), '1.0');
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

// 能优化
function optimize_post_pages() {
    if (is_page_template(['page-submit-post.php', 'page-edit-post.php'])) {
        wp_enqueue_script('jquery');
        
        // 移除不必要的资源
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-block-style');
        wp_dequeue_style('global-styles');
        
        // 加载
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

// 编码和存控
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

// 主题激活时创建修改说明表
function create_edit_summary_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'post_edit_summaries';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        revision_id bigint(20) NOT NULL,
        post_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        summary text NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY revision_id (revision_id),
        KEY post_id (post_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'create_edit_summary_table');

// 加载文章标签页相关文件
function load_article_tabs_files() {
    // 加载设置页面
    require_once get_template_directory() . '/inc/article-tabs-settings.php';
    // 加载AJAX处理
    require_once get_template_directory() . '/inc/article-tabs-ajax.php';
}
add_action('after_setup_theme', 'load_article_tabs_files');

// 添加 Markdown 渲染支持
function enqueue_markdown_assets() {
    // 在文章页面和存档页面加载
    if (is_single() || is_archive() || is_home()) {
        wp_enqueue_script('marked-js', 'https://cdn.jsdelivr.net/npm/marked@4.3.0/marked.min.js', array('jquery'), null, true);
        wp_enqueue_script('markdown-render', get_template_directory_uri() . '/assets/js/markdown-render.js', array('jquery', 'marked-js'), '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_markdown_assets');

// 添加文章内容过滤器
function render_markdown_content($content) {
    if (is_single() || is_archive() || is_home()) {
        return '<div class="markdown-content">' . $content . '</div>';
    }
    return $content;
}
add_filter('the_content', 'render_markdown_content');

// 也可以在管理员访问后台时检查并执行升级
function check_theme_upgrade() {
    if (current_user_can('manage_options')) {
        theme_upgrade_db();
    }
}
add_action('admin_init', 'check_theme_upgrade');

// 确保特定页面不受首页设置影响
function exclude_pages_from_home($query) {
    if ($query->is_home() && $query->is_main_query()) {
        $query->set('post_type', 'post');
        
        // 排除用户中心等页面
        $excluded_pages = array('user-center', 'login', 'register', 'lost-password');
        $page_ids = array();
        
        foreach ($excluded_pages as $page_slug) {
            $page = get_page_by_path($page_slug);
            if ($page) {
                $page_ids[] = $page->ID;
            }
        }
        
        if (!empty($page_ids)) {
            $query->set('post__not_in', $page_ids);
        }
    }
}
add_action('pre_get_posts', 'exclude_pages_from_home');

// 检查AI Services插件状态并显示通知
function check_ai_services_plugin() {
    // 仅对管理员显示
    if (!current_user_can('administrator')) {
        return;
    }
    
    // 检查是否在管理页面
    if (!is_admin()) {
        return;
    }
    
    // 添加详细调试信息
    $debug_info = [];
    $debug_info[] = '检查AI Services插件是否已安装和配置:';
    $debug_info[] = '- function_exists(ai_services): ' . (function_exists('ai_services') ? 'true' : 'false');
    
    $screen = get_current_screen();
    $plugin_status = '未知';
    $has_available_services = false;
    
    if (function_exists('ai_services')) {
        try {
            $debug_info[] = '- ai_services()是否存在: ' . (ai_services() ? 'true' : 'false');
            
            // 检查当前用户是否有访问AI Services的权限
            $debug_info[] = '- 当前用户是否有ais_access_services权限: ' . (current_user_can('ais_access_services') ? 'true' : 'false');
            
            try {
                $has_available_services = ai_services()->has_available_services();
                $debug_info[] = '- has_available_services(): ' . ($has_available_services ? 'true' : 'false');
                $plugin_status = $has_available_services ? '已配置' : '未配置';
                
                // 尝试获取可用服务
                $available_service = null;
                try {
                    $available_service = ai_services()->get_available_service();
                    if ($available_service) {
                        $debug_info[] = '- 成功获取可用服务: true';
                        $plugin_status = '已配置且有可用服务';
                        
                        // 检查服务详情
                        if (method_exists($available_service, 'get_name')) {
                            $debug_info[] = '- 服务名称: ' . $available_service->get_name();
                        }
                        if (method_exists($available_service, 'get_label')) {
                            $debug_info[] = '- 服务标签: ' . $available_service->get_label();
                        }
                        
                        // 检查是否有文本生成能力
                        if (method_exists($available_service, 'get_models')) {
                            $models = $available_service->get_models();
                            $debug_info[] = '- 可用模型数量: ' . count($models);
                            
                            // 检查是否至少有一个模型支持文本生成
                            $has_text_generation = false;
                            foreach ($models as $model) {
                                if (method_exists($model, 'supports_text_generation') && $model->supports_text_generation()) {
                                    $has_text_generation = true;
                                    break;
                                }
                            }
                            $debug_info[] = '- 是否有支持文本生成的模型: ' . ($has_text_generation ? 'true' : 'false');
                        }
                    } else {
                        $debug_info[] = '- get_available_service()返回null';
                    }
                } catch (Exception $e) {
                    $debug_info[] = '- 获取可用服务失败: ' . $e->getMessage();
                }
            } catch (Exception $e) {
                $debug_info[] = '- 检查过程出错: ' . $e->getMessage();
                $plugin_status = '配置出错';
            }
        } catch (Exception $e) {
            $debug_info[] = '- 检查过程出错: ' . $e->getMessage();
            $plugin_status = '异常';
        }
    } else {
        $plugin_status = '未安装';
    }
    
    // 记录调试信息到日志
    if (current_user_can('administrator')) {
        error_log('AI Services插件状态检查: ' . implode(' | ', $debug_info));
        error_log('AI Services插件状态: ' . $plugin_status);
    }
    
    // 检查AI Services插件状态并显示相应通知
    if ($plugin_status == '未安装') {
        // 排除插件安装页面，避免重复通知
        if ($screen && $screen->id != 'plugin-install' && $screen->id != 'plugins') {
            ?>
            <div class="notice notice-info is-dismissible">
                <p><strong>建议：</strong>您的主题包含AI文章审核功能，需要安装 <a href="https://wordpress.org/plugins/ai-services/" target="_blank">AI Services</a> 插件才能正常工作。</p>
                <p>
                    <a href="<?php echo esc_url(admin_url('plugin-install.php?s=ai-services&tab=search&type=term')); ?>" class="button button-primary">安装AI Services插件</a>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=ai-review-settings')); ?>" class="button">查看AI审核设置</a>
                </p>
            </div>
            <?php
        }
    } elseif ($plugin_status == '未配置' || $plugin_status == '配置出错') {
        // 已安装但未配置或配置出错
        // 排除AI Services设置页面，避免重复通知
        if ($screen && $screen->id != 'settings_page_ai-services-settings') {
            ?>
            <div class="notice notice-info is-dismissible">
                <p><strong>提示：</strong>您已安装AI Services插件，但<?php echo $plugin_status == '未配置' ? '尚未配置任何AI服务' : '配置可能有问题'; ?>。请完成配置以启用AI文章审核功能。</p>
                <p>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=ai-services-settings')); ?>" class="button button-primary">配置AI Services</a>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=ai-review-settings')); ?>" class="button">查看AI审核设置</a>
                </p>
            </div>
            <?php
        }
    } elseif ($plugin_status == '异常') {
        // 插件可能安装有问题
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>AI Services插件错误：</strong>插件似乎已安装但无法正常工作。请尝试重新安装或更新插件。</p>
            <p>
                <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-primary">管理插件</a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=ai-services-settings')); ?>" class="button">检查AI Services设置</a>
            </p>
        </div>
        <?php
    } elseif (function_exists('ai_services') && isset($e)) {
        // 发生了异常但不确定具体原因
        error_log('检查AI Services可用性时出错: ' . $e->getMessage());
        
        // 显示错误通知
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>AI Services插件错误：</strong><?php echo esc_html($e->getMessage()); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=ai-services-settings')); ?>" class="button button-primary">检查AI Services设置</a>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'check_ai_services_plugin');
