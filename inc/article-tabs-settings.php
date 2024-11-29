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
    
    $settings = get_option('article_tabs_settings', array(
        'posts_per_page' => 10,
        'excerpt_length' => 200,
        'cache_time_latest' => 24,     // 最新文章缓存时间(小时)
        'cache_time_updated' => 24,    // 最近修改缓存时间(小时)
        'cache_time_popular' => 24,    // 热门文章缓存时间(小时)
        'cache_time_rss' => 5,         // RSS内容缓存时间(小时)
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
            </table>
            
            <h2>缓存设置</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">最新文章缓存时间</th>
                    <td>
                        <input type="number" name="article_tabs_settings[cache_time_latest]" 
                               value="<?php echo esc_attr($settings['cache_time_latest']); ?>" min="1" max="72">
                        <p class="description">小时</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">最近修改缓存时间</th>
                    <td>
                        <input type="number" name="article_tabs_settings[cache_time_updated]" 
                               value="<?php echo esc_attr($settings['cache_time_updated']); ?>" min="1" max="72">
                        <p class="description">小时</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">热门文章缓存时间</th>
                    <td>
                        <input type="number" name="article_tabs_settings[cache_time_popular]" 
                               value="<?php echo esc_attr($settings['cache_time_popular']); ?>" min="1" max="72">
                        <p class="description">小时</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">RSS内容缓存时间</th>
                    <td>
                        <input type="number" name="article_tabs_settings[cache_time_rss]" 
                               value="<?php echo esc_attr($settings['cache_time_rss']); ?>" min="1" max="72">
                        <p class="description">小时</p>
                    </td>
                </tr>
            </table>
            
            <h2>自定义标签</h2>
            <div id="custom-tabs">
                <?php foreach ($custom_tabs as $index => $tab) : ?>
                <div class="custom-tab">
                    <input type="text" name="article_custom_tabs[<?php echo $index; ?>][title]" 
                           value="<?php echo esc_attr($tab['title']); ?>" placeholder="标签标题">
                    <textarea name="article_custom_tabs[<?php echo $index; ?>][content]" 
                              placeholder="RSS短代码或其他内容"><?php echo esc_textarea($tab['content']); ?></textarea>
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
                '<input type="text" name="article_custom_tabs[' + tabIndex + '][title]" placeholder="标签标题">' +
                '<textarea name="article_custom_tabs[' + tabIndex + '][content]" placeholder="RSS短代码或其他内容"></textarea>' +
                '<button type="button" class="button remove-tab">删除</button>' +
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
    }
    return $value;
}
add_filter('pre_update_option_article_custom_tabs', 'save_article_tabs_settings'); 