<?php
// 添加投票设置菜单
function add_voting_settings_menu() {
    add_menu_page(
        '投票设置',
        '投票设置',
        'manage_options',
        'voting-settings',
        'display_voting_settings_page',
        'dashicons-chart-bar',
        30
    );
}
add_action('admin_menu', 'add_voting_settings_menu');

// 显示设置页面
function display_voting_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // 保存设置
    if (isset($_POST['save_voting_settings'])) {
        check_admin_referer('voting_settings_nonce');
        
        $min_months = intval($_POST['min_register_months']);
        update_option('voting_min_register_months', $min_months);
        
        $required_votes = intval($_POST['votes_required']);
        update_option('voting_votes_required', $required_votes);
        
        $approve_ratio = floatval($_POST['approve_ratio']);
        update_option('voting_approve_ratio', $approve_ratio);
        
        // 保存公告设置
        $announcement = wp_kses_post($_POST['voting_announcement']);
        update_option('voting_announcement', $announcement);
        
        echo '<div class="updated"><p>设置已保存</p></div>';
    }
    
    // 获取当前设置
    $min_months = get_option('voting_min_register_months', 3);
    $required_votes = get_option('voting_votes_required', 10);
    $approve_ratio = get_option('voting_approve_ratio', 0.6);
    $announcement = get_option('voting_announcement', '');
    ?>
    
    <div class="wrap">
        <h1>社区投票设置</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('voting_settings_nonce'); ?>
            
            <!-- 添加公告设置 -->
            <h2>公告设置</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="voting_announcement">公告内容</label>
                    </th>
                    <td>
                        <textarea id="voting_announcement" 
                                name="voting_announcement" 
                                rows="5" 
                                style="width: 100%;"
                                placeholder="留空则不显示公告"><?php 
                            echo esc_textarea($announcement); 
                        ?></textarea>
                        <p class="description">支持HTML，可以添加链接等</p>
                    </td>
                </tr>
            </table>
            
            <h2>投票设置</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="min_register_months">最少注册月数</label>
                    </th>
                    <td>
                        <input type="number" id="min_register_months" name="min_register_months" 
                               value="<?php echo esc_attr($min_months); ?>" min="0" />
                        <p class="description">用户需要注册满多少个月才能参与投票</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="votes_required">所需投票数</label>
                    </th>
                    <td>
                        <input type="number" id="votes_required" name="votes_required" 
                               value="<?php echo esc_attr($required_votes); ?>" min="1" />
                        <p class="description">文章需要达到多少票才能决定是否通过</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="approve_ratio">通过比例</label>
                    </th>
                    <td>
                        <input type="number" id="approve_ratio" name="approve_ratio" 
                               value="<?php echo esc_attr($approve_ratio); ?>" 
                               min="0" max="1" step="0.1" />
                        <p class="description">赞成票占总票数的比例达到多少才算通过（0.1-1.0）</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_voting_settings" class="button-primary" 
                       value="保存设置" />
            </p>
        </form>
    </div>
    <?php
} 