<?php
if (!defined('ABSPATH')) exit;

// 添加设置菜单
function article_tabs_settings_menu() {
    add_theme_page(
        '文章标签页设置',
        '文章标签页',
        'manage_options',
        'article-tabs-settings',
        'article_tabs_settings_page'
    );
}
add_action('admin_menu', 'article_tabs_settings_menu');

// 注册设置
function article_tabs_register_settings() {
    register_setting('article_tabs_options', 'article_tabs_settings');
    register_setting('article_tabs_options', 'article_custom_tabs');
}
add_action('admin_init', 'article_tabs_register_settings');

// 设置页面内容
function article_tabs_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // 获取所有分类
    $categories = get_categories(array('hide_empty' => false));
    
    $settings = get_option('article_tabs_settings', array(
        'posts_per_page' => 10,
        'excerpt_length' => 200,
        'cache_time_latest' => 24,
        'cache_time_updated' => 24,
        'cache_time_popular' => 24,
        'cache_time_rss' => 5,
        // 添加默认的分类过滤设置
        'latest_category_filter' => 'none',
        'latest_categories' => array(),
        'updated_category_filter' => 'none',
        'updated_categories' => array(),
        'popular_category_filter' => 'none', 
        'popular_categories' => array()
    ));
    
    $custom_tabs = get_option('article_custom_tabs', array());
    ?>
    
    <div class="wrap">
        <h1>文章标签页设置</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('article_tabs_options'); ?>
            
            <h2>基本设置</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">每页显示文章数</th>
                    <td>
                        <input type="number" name="article_tabs_settings[posts_per_page]" 
                               value="<?php echo esc_attr($settings['posts_per_page']); ?>" min="1" max="50">
                    </td>
                </tr>
                <tr>
                    <th scope="row">文章摘要长度</th>
                    <td>
                        <input type="number" name="article_tabs_settings[excerpt_length]" 
                               value="<?php echo esc_attr($settings['excerpt_length']); ?>" min="50" max="500">
                        <p class="description">字符数</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">公告内容</th>
                    <td>
                        <textarea name="article_tabs_settings[announcement]" 
                                  rows="3" style="width: 100%;"
                                  placeholder="留空则不显示公告"><?php 
                            echo esc_textarea(isset($settings['announcement']) ? $settings['announcement'] : ''); 
                        ?></textarea>
                        <p class="description">支持HTML，可以添加链接等</p>
                    </td>
                </tr>
            </table>
            
            <h2>列表设置</h2>
            <table class="form-table">
                <!-- 最新文章设置 -->
                <tr>
                    <th scope="row">最新文章</th>
                    <td>
                        <label>
                            <input type="checkbox" name="article_tabs_settings[latest_enabled]" value="1" 
                                   <?php checked(isset($settings['latest_enabled']) && $settings['latest_enabled']); ?>>
                            启用最新文章列表
                        </label>
                        <div class="tab-category-settings">
                            <label>
                                <input type="radio" name="article_tabs_settings[latest_category_filter]" value="none" 
                                       <?php checked($settings['latest_category_filter'], 'none'); ?>>
                                不限制分类
                            </label>
                            <label>
                                <input type="radio" name="article_tabs_settings[latest_category_filter]" value="include" 
                                       <?php checked($settings['latest_category_filter'], 'include'); ?>>
                                仅包含以下分类
                            </label>
                            <label>
                                <input type="radio" name="article_tabs_settings[latest_category_filter]" value="exclude" 
                                       <?php checked($settings['latest_category_filter'], 'exclude'); ?>>
                                排除以下分类
                            </label>
                            
                            <div class="category-select-wrapper" style="display: <?php echo ($settings['latest_category_filter'] === 'none') ? 'none' : 'block'; ?>;">
                                <select name="article_tabs_settings[latest_categories][]" multiple class="category-select">
                                    <?php
                                    $selected_cats = isset($settings['latest_categories']) ? (array)$settings['latest_categories'] : array();
                                    foreach ($categories as $category) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($category->term_id),
                                            in_array($category->term_id, $selected_cats) ? 'selected' : '',
                                            esc_html($category->name)
                                        );
                                    }
                                    ?>
                                </select>
                                <p class="description">按住 Ctrl/Command 键可多选</p>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <!-- 最近修改设置 -->
                <tr>
                    <th scope="row">最近修改</th>
                    <td>
                        <label>
                            <input type="checkbox" name="article_tabs_settings[updated_enabled]" value="1" 
                                   <?php checked(isset($settings['updated_enabled']) && $settings['updated_enabled']); ?>>
                            启用最近修改列表
                        </label>
                        <div class="tab-category-settings">
                            <label>
                                <input type="radio" name="article_tabs_settings[updated_category_filter]" value="none" 
                                       <?php checked($settings['updated_category_filter'], 'none'); ?>>
                                不限制分类
                            </label>
                            <label>
                                <input type="radio" name="article_tabs_settings[updated_category_filter]" value="include" 
                                       <?php checked($settings['updated_category_filter'], 'include'); ?>>
                                仅包含以下分类
                            </label>
                            <label>
                                <input type="radio" name="article_tabs_settings[updated_category_filter]" value="exclude" 
                                       <?php checked($settings['updated_category_filter'], 'exclude'); ?>>
                                排除以下分类
                            </label>
                            
                            <div class="category-select-wrapper" style="display: <?php echo ($settings['updated_category_filter'] === 'none') ? 'none' : 'block'; ?>;">
                                <select name="article_tabs_settings[updated_categories][]" multiple class="category-select">
                                    <?php
                                    $selected_cats = isset($settings['updated_categories']) ? (array)$settings['updated_categories'] : array();
                                    foreach ($categories as $category) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($category->term_id),
                                            in_array($category->term_id, $selected_cats) ? 'selected' : '',
                                            esc_html($category->name)
                                        );
                                    }
                                    ?>
                                </select>
                                <p class="description">按住 Ctrl/Command 键可多选</p>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <!-- 热门文章设置 -->
                <tr>
                    <th scope="row">热门文章</th>
                    <td>
                        <label>
                            <input type="checkbox" name="article_tabs_settings[popular_enabled]" value="1" 
                                   <?php checked(isset($settings['popular_enabled']) && $settings['popular_enabled']); ?>>
                            启用热门文章列表
                        </label>
                        <div class="tab-category-settings">
                            <label>
                                <input type="radio" name="article_tabs_settings[popular_category_filter]" value="none" 
                                       <?php checked($settings['popular_category_filter'], 'none'); ?>>
                                不限制分类
                            </label>
                            <label>
                                <input type="radio" name="article_tabs_settings[popular_category_filter]" value="include" 
                                       <?php checked($settings['popular_category_filter'], 'include'); ?>>
                                仅包含以下分类
                            </label>
                            <label>
                                <input type="radio" name="article_tabs_settings[popular_category_filter]" value="exclude" 
                                       <?php checked($settings['popular_category_filter'], 'exclude'); ?>>
                                排除以下分类
                            </label>
                            
                            <div class="category-select-wrapper" style="display: <?php echo ($settings['popular_category_filter'] === 'none') ? 'none' : 'block'; ?>;">
                                <select name="article_tabs_settings[popular_categories][]" multiple class="category-select">
                                    <?php
                                    $selected_cats = isset($settings['popular_categories']) ? (array)$settings['popular_categories'] : array();
                                    foreach ($categories as $category) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($category->term_id),
                                            in_array($category->term_id, $selected_cats) ? 'selected' : '',
                                            esc_html($category->name)
                                        );
                                    }
                                    ?>
                                </select>
                                <p class="description">按住 Ctrl/Command 键可多选</p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            
            <h2>缓存设置</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">最新文章缓存时间</th>
                    <td>
                        <input type="number" name="article_tabs_settings[cache_time_latest]" 
                               value="<?php echo esc_attr($settings['cache_time_latest']); ?>" min="1" max="72">
                        <p class="description">小时</p>
                        <button type="button" id="clear-latest-cache" class="button" data-type="latest">清理最新文章缓存</button>
                        <span id="clear-latest-status" class="clear-cache-status" style="margin-left: 10px;"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">最近修改缓存时间</th>
                    <td>
                        <input type="number" name="article_tabs_settings[cache_time_updated]" 
                               value="<?php echo esc_attr($settings['cache_time_updated']); ?>" min="1" max="72">
                        <p class="description">小时</p>
                        <button type="button" id="clear-updated-cache" class="button" data-type="updated">清理最近修改缓存</button>
                        <span id="clear-updated-status" class="clear-cache-status" style="margin-left: 10px;"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">热门文章缓存时间</th>
                    <td>
                        <input type="number" name="article_tabs_settings[cache_time_popular]" 
                               value="<?php echo esc_attr($settings['cache_time_popular']); ?>" min="1" max="72">
                        <p class="description">小时</p>
                        <button type="button" id="clear-popular-cache" class="button" data-type="popular">清理热门文章缓存</button>
                        <span id="clear-popular-status" class="clear-cache-status" style="margin-left: 10px;"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">RSS内容缓存时间</th>
                    <td>
                        <input type="number" name="article_tabs_settings[cache_time_rss]" 
                               value="<?php echo esc_attr($settings['cache_time_rss']); ?>" min="1" max="72">
                        <p class="description">小时</p>
                        <button type="button" id="clear-rss-cache" class="button" data-type="rss">清理RSS缓存</button>
                        <span id="clear-rss-status" class="clear-cache-status" style="margin-left: 10px;"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">清理所有缓存</th>
                    <td>
                        <button type="button" id="clear-all-cache" class="button button-primary">清理所有缓存</button>
                        <span id="clear-all-status" class="clear-cache-status" style="margin-left: 10px;"></span>
                    </td>
                </tr>
            </table>
            
            <h2>自定义标签</h2>
            <div id="custom-tabs">
                <?php foreach ($custom_tabs as $index => $tab) : ?>
                <div class="custom-tab">
                    <input type="text" name="article_custom_tabs[<?php echo $index; ?>][title]" 
                           value="<?php echo esc_attr($tab['title']); ?>" class="regular-text" required>
                    <textarea name="article_custom_tabs[<?php echo $index; ?>][content]" 
                              rows="3" class="large-text" required><?php echo esc_textarea($tab['content']); ?></textarea>
                    <label>
                        <input type="checkbox" name="article_custom_tabs[<?php echo $index; ?>][login_required]" value="1" 
                               <?php checked(isset($tab['login_required']) && $tab['login_required']); ?>>
                        仅登录用户可见
                    </label>
                    <button type="button" class="button remove-tab">删除</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button" id="add-tab">添加标签</button>
            
            <?php submit_button(); ?>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var tabIndex = <?php echo count($custom_tabs); ?>;
        
        $('#add-tab').on('click', function() {
            var newTab = $('<div class="custom-tab">' +
                '<input type="text" name="article_custom_tabs[' + tabIndex + '][title]" class="regular-text" required>' +
                '<textarea name="article_custom_tabs[' + tabIndex + '][content]" rows="3" class="large-text" required></textarea>' +
                '<label><input type="checkbox" name="article_custom_tabs[' + tabIndex + '][login_required]" value="1">仅登录用户可见</label>' +
                '<button type="button" class="button remove-tab">��除</button>' +
                '</div>');
            $('#custom-tabs').append(newTab);
            tabIndex++;
        });
        
        $(document).on('click', '.remove-tab', function() {
            $(this).closest('.custom-tab').remove();
        });
    });
    </script>
    
    <style>
    .custom-tab {
        margin-bottom: 15px;
        padding: 15px;
        background: #fff;
        border: 1px solid #ccc;
    }
    .custom-tab input[type="text"] {
        width: 100%;
        margin-bottom: 10px;
    }
    .custom-tab textarea {
        width: 100%;
        height: 100px;
        margin-bottom: 10px;
    }
    </style>
    <?php
}

// 在保存设置时添加ID
function save_article_tabs_settings($value) {
    foreach ($value as $index => &$tab) {
        if (!isset($tab['id'])) {
            $tab['id'] = 'custom_' . $index;
        }
        if (!isset($tab['login_required'])) {
            $tab['login_required'] = 0;
        }
    }
    return $value;
}
add_filter('pre_update_option_article_custom_tabs', 'save_article_tabs_settings');

// 加载关样式和脚本
function enqueue_article_tabs_assets() {
    if (is_page_template('page-article-tabs.php')) {
        wp_register_script('article-tabs', get_template_directory_uri() . '/assets/js/article-tabs.js', array('jquery'), null, true);
        wp_localize_script('article-tabs', 'articleTabsData', array(
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_style('article-tabs', get_template_directory_uri() . '/assets/css/article-tabs.css');
        wp_enqueue_script('article-tabs');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_article_tabs_assets');

// 加载后台脚本
function enqueue_article_tabs_admin_assets($hook) {
    if ('appearance_page_article-tabs-settings' !== $hook) {
        return;
    }
    
    wp_enqueue_script(
        'article-tabs-admin', 
        get_template_directory_uri() . '/assets/js/article-tabs-admin.js', 
        array('jquery'), 
        null, 
        true
    );
    
    // 添加本地化数据
    wp_localize_script('article-tabs-admin', 'articleTabsSettings', array(
        'nonce' => wp_create_nonce('article_tabs_settings'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_article_tabs_admin_assets');

// 获取文章标签页的ID
function get_article_tabs_page_id() {
    $page = get_page_by_path('article-tabs');
    return $page ? $page->ID : false;
}

// 修改重写规则函数
function add_article_tabs_rewrite_rules() {
    $page_id = get_article_tabs_page_id();
    if ($page_id) {
        // 检查是否是首页
        $front_page_id = get_option('page_on_front');
        $is_front_page = ($front_page_id == $page_id);
        
        if ($is_front_page) {
            // 如果是首页，使用特殊的规则
            add_rewrite_rule(
                '^tabs/([^/]+)/?$',
                'index.php?tab=$matches[1]',
                'top'
            );
        } else {
            add_rewrite_rule(
                '^tabs/([^/]+)/?$',
                'index.php?page_id=' . $page_id . '&tab=$matches[1]',
                'top'
            );
        }
    }
}
add_action('init', 'add_article_tabs_rewrite_rules');

// 添加查询变量
function add_article_tabs_query_vars($vars) {
    $vars[] = 'tab';
    return $vars;
}
add_filter('query_vars', 'add_article_tabs_query_vars');

// 修改模板加载逻辑
function article_tabs_template_include($template) {
    if (get_query_var('tab')) {
        $tab = get_query_var('tab');
        
        // 检查是否是需要登录的自定义标签
        if (strpos($tab, 'custom_') === 0) {
            $custom_tabs = get_option('article_custom_tabs', array());
            $tab_index = substr($tab, 7);
            
            if (isset($custom_tabs[$tab_index]) && 
                !empty($custom_tabs[$tab_index]['login_required']) && 
                !is_user_logged_in()) {
                
                // 获取登录页面链接
                $login_page = get_page_by_path('login');
                if ($login_page) {
                    // 构建带有返回URL的登录链接
                    $current_url = home_url($_SERVER['REQUEST_URI']);
                    $redirect_url = add_query_arg('redirect_to', 
                                                   urlencode($current_url), 
                                                   get_permalink($login_page));
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    // 如果没有自定义登录页面，跳转到首页
                    wp_redirect(home_url('/'));
                    exit;
                }
            }
        }
        
        // 设置查询为文章标签页
        global $wp_query;
        $page = get_page_by_path('article-tabs');
        if ($page) {
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
            $wp_query->is_home = false;
            $wp_query->is_archive = false;
            $wp_query->is_category = false;
            $wp_query->post = $page;
            $wp_query->posts = array($page);
            $wp_query->queried_object = $page;
            $wp_query->queried_object_id = $page->ID;
            $wp_query->post_count = 1;
            $wp_query->current_post = -1;
        }
        
        // 如果有 tab 参数，强制使用文章标签页模板
        $new_template = locate_template('page-article-tabs.php');
        if ($new_template) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'article_tabs_template_include');

// 在保存首页设置时也刷新规则
function refresh_rules_on_front_page_change($old_value, $new_value) {
    if ($old_value != $new_value) {
        refresh_article_tabs_rules();
    }
}
add_action('update_option_page_on_front', 'refresh_rules_on_front_page_change', 10, 2);

// 手动刷新重写规则
function refresh_article_tabs_rules() {
    add_article_tabs_rewrite_rules();
    flush_rewrite_rules();
}

// 在保存设置时刷新规则
add_action('update_option_article_tabs_settings', 'refresh_article_tabs_rules'); 