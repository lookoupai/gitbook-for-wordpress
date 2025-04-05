<?php
// AI审核功能模块
if (!defined('ABSPATH')) exit;

// 手动注册默认AI服务，确保至少有一个服务可用
function register_default_ai_service() {
    // 检查ai_services函数是否存在
    if (!function_exists('ai_services')) {
        error_log('无法注册默认AI服务：ai_services函数不存在');
        return false;
    }
    
    try {
        // 检查是否已有可用服务
        $has_services = ai_services()->has_available_services();
        
        // 如果已经有服务，不需要注册默认服务
        if ($has_services) {
            return true;
        }
        
        // 记录注册尝试
        error_log('尝试手动注册默认AI服务');
        
        // 根据GitHub文档，我们可以使用register_service方法注册服务
        // https://raw.githubusercontent.com/felixarntz/ai-services/refs/heads/main/docs/Implementing-and-Registering-a-New-AI-Service.md
        
        if (method_exists(ai_services(), 'register_service')) {
            error_log('尝试使用register_service方法注册服务');
            
            // 检查服务是否已存在
            $registered_services = array();
            if (method_exists(ai_services(), 'get_services')) {
                $registered_services = ai_services()->get_services();
            }
            
            if (!isset($registered_services['manual-fallback-service'])) {
                // 尝试注册一个手动服务，这只是一个占位符
                // 实际项目中需要实现正确的服务类
                try {
                    // 使用官方文档中的示例格式
                    ai_services()->register_service(
                        'manual-fallback-service',
                        function($authentication, $http) {
                            // 这里实际应该返回一个实现了Generative_AI_Service接口的类实例
                            // 由于不知道具体实现，只返回null作为占位符
                            error_log('服务工厂函数被调用，但没有实际实现');
                            return null;
                        },
                        array(
                            'name' => 'Manual Fallback Service',
                            'label' => '手动回退服务'
                        )
                    );
                    
                    error_log('手动注册服务完成，但可能无法实际工作');
                } catch (Exception $e) {
                    error_log('注册服务时出错: ' . $e->getMessage());
                }
            }
        } else {
            error_log('register_service方法不存在，无法注册服务');
        }
        
        // 检查注册是否成功
        try {
            $new_has_services = ai_services()->has_available_services();
            if ($new_has_services) {
                error_log('注册服务后，has_available_services()返回true');
                return true;
            } else {
                error_log('注册服务后，has_available_services()仍然返回false');
            }
        } catch (Exception $e) {
            error_log('检查注册结果时出错: ' . $e->getMessage());
        }
        
        error_log('无法自动注册默认AI服务，请在AI Services插件中手动配置服务');
        return false;
    } catch (Exception $e) {
        error_log('注册默认AI服务出错：' . $e->getMessage());
        return false;
    }
}

// 检查配置并尝试注册默认服务
function ensure_ai_service_available() {
    if (function_exists('ai_services') && !ai_services()->has_available_services()) {
        register_default_ai_service();
    }
}
add_action('admin_init', 'ensure_ai_service_available');
add_action('wp_loaded', 'ensure_ai_service_available');

// 检查AI Services插件是否已安装并激活
function is_ai_services_active() {
    // 添加详细调试信息
    if (current_user_can('administrator')) {
        error_log('-----开始检查AI Services插件状态-----');
        error_log('function_exists(ai_services): ' . (function_exists('ai_services') ? 'true' : 'false'));
    }
    
    // 首先检查函数是否存在
    if (!function_exists('ai_services')) {
        if (current_user_can('administrator')) {
            error_log('AI Services插件未安装或未激活');
        }
        return false;
    }
    
    // 检查ai_services()是否返回有效对象
    $ai_services = null;
    try {
        $ai_services = ai_services();
        if (!$ai_services) {
            if (current_user_can('administrator')) {
                error_log('ai_services()返回无效对象');
            }
            return false;
        }
        
        if (current_user_can('administrator')) {
            error_log('ai_services()返回有效对象');
        }
        
        // 检查当前用户是否有访问AI Services的权限
        if (!current_user_can('ais_access_services')) {
            if (current_user_can('administrator')) {
                error_log('当前用户没有访问AI Services的权限(ais_access_services)');
                error_log('当前用户角色: ' . implode(', ', wp_get_current_user()->roles));
            }
            // 对管理员特殊处理，即使没有显式的ais_access_services权限
            if (!current_user_can('administrator')) {
                return false;
            }
        }
        
        // 检查是否有可用的AI服务
        try {
            // 直接检查has_available_services方法是否存在
            if (!method_exists($ai_services, 'has_available_services')) {
                if (current_user_can('administrator')) {
                    error_log('ai_services对象没有has_available_services方法');
                }
                return false;
            }
            
            $has_services = $ai_services->has_available_services();
            
            if (current_user_can('administrator')) {
                error_log('has_available_services(): ' . ($has_services ? 'true' : 'false'));
                
                // 如果服务可用，尝试获取服务详情
                if ($has_services) {
                    try {
                        if (!method_exists($ai_services, 'get_available_service')) {
                            error_log('ai_services对象没有get_available_service方法');
                        } else {
                            $available_service = $ai_services->get_available_service();
                            if ($available_service) {
                                error_log('成功获取可用服务');
                                
                                // 检查服务详情
                                if (method_exists($available_service, 'get_name')) {
                                    error_log('服务名称: ' . $available_service->get_name());
                                }
                                if (method_exists($available_service, 'get_label')) {
                                    error_log('服务标签: ' . $available_service->get_label());
                                }
                                if (method_exists($available_service, 'get_models')) {
                                    $models = $available_service->get_models();
                                    error_log('可用模型数量: ' . count($models));
                                }
                                if (method_exists($available_service, 'is_available')) {
                                    error_log('服务是否可用: ' . ($available_service->is_available() ? 'true' : 'false'));
                                }
                            } else {
                                error_log('get_available_service()返回null');
                            }
                        }
                    } catch (Exception $e) {
                        error_log('获取可用服务详情时出错: ' . $e->getMessage());
                    }
                }
            }
            
            if (current_user_can('administrator')) {
                error_log('-----完成检查AI Services插件状态-----');
            }
            
            return $has_services;
        } catch (Exception $e) {
            if (current_user_can('administrator')) {
                error_log('检查可用服务时出错: ' . $e->getMessage());
                error_log('-----完成检查AI Services插件状态(出错)-----');
            }
            return false;
        }
    } catch (Exception $e) {
        if (current_user_can('administrator')) {
            error_log('获取ai_services()实例时出错: ' . $e->getMessage());
            error_log('-----完成检查AI Services插件状态(出错)-----');
        }
        return false;
    }
}

// 保存AI审核设置
function save_ai_review_settings($settings) {
    $defaults = get_ai_review_settings();
    $settings = wp_parse_args($settings, $defaults);
    update_option('ai_review_settings', $settings);
    return $settings;
}

// 获取AI审核设置
function get_ai_review_settings() {
    $defaults = array(
        'enabled' => false,
        'service' => '',
        'min_score' => 0.7,
        'auto_approve' => false,
        'notification' => true,
        'prompt_template' => "请简要评估以下文章质量，并给出1-10分的评分。\n\n文章内容：\n{content}\n\n评分及简短评价：",
        'revision_prompt_template' => "请简要评估以下文章修改的质量，并给出1-10分的评分。\n\n原文：\n{original_content}\n\n修改后：\n{new_content}\n\n评分及简短评价："
    );
    
    $settings = get_option('ai_review_settings', $defaults);
    return wp_parse_args($settings, $defaults);
}

// 添加AI审核功能的管理页面
function register_ai_review_admin_page() {
    add_submenu_page(
        'options-general.php',
        'AI文章审核设置',
        'AI文章审核',
        'manage_options',
        'ai-review-settings',
        'render_ai_review_settings_page'
    );
}
add_action('admin_menu', 'register_ai_review_admin_page');

// 显示AI Services插件安装提示
function display_ai_services_admin_notice() {
    // 仅在AI审核设置页面显示
    $screen = get_current_screen();
    if ($screen->id !== 'settings_page_ai-review-settings') {
        return;
    }
    
    // 检查AI Services插件是否已安装
    if (!function_exists('ai_services')) {
        ?>
        <div class="notice notice-warning">
            <p><strong>重要提示：</strong>AI文章审核功能需要安装并配置 <a href="https://wordpress.org/plugins/ai-services/" target="_blank">AI Services插件</a>。</p>
            <p>
                <a href="<?php echo esc_url(admin_url('plugin-install.php?s=ai-services&tab=search&type=term')); ?>" class="button button-primary">安装AI Services插件</a>
                <a href="https://wordpress.org/plugins/ai-services/" target="_blank" class="button button-secondary">了解更多</a>
            </p>
        </div>
        <?php
    } else if (!ai_services()->has_available_services()) {
        ?>
        <div class="notice notice-warning">
            <p><strong>重要提示：</strong>AI Services插件已安装但尚未配置AI服务提供商。请设置至少一个AI服务以启用AI审核功能。</p>
            <p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=ai-services-settings')); ?>" class="button button-primary">配置AI Services</a>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'display_ai_services_admin_notice');

// 渲染AI审核设置页面
function render_ai_review_settings_page() {
    // 检查权限
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // 处理表单提交
    if (isset($_POST['submit'])) {
        $settings = array(
            'enabled' => isset($_POST['ai_review_enabled']),
            'service' => sanitize_text_field($_POST['ai_review_service']),
            'min_score' => floatval($_POST['ai_review_min_score']),
            'auto_approve' => isset($_POST['ai_review_auto_approve']),
            'notification' => isset($_POST['ai_review_notification']),
            'prompt_template' => sanitize_textarea_field($_POST['ai_review_prompt_template']),
            'revision_prompt_template' => sanitize_textarea_field($_POST['ai_review_revision_prompt_template'])
        );
        
        save_ai_review_settings($settings);
        echo '<div class="notice notice-success is-dismissible"><p>设置已保存</p></div>';
    }
    
    $settings = get_ai_review_settings();
    
    // 记录调试信息
    error_log('渲染AI审核设置页面：开始检查AI服务状态');
    
    // 再次执行检查，确保获取最新状态
    $has_ai_services = false;
    $available_services = array();
    
    if (function_exists('ai_services')) {
        try {
            error_log('调用is_ai_services_active()');
            
            // 直接尝试获取服务，不依赖于is_ai_services_active()函数
            $ai_services = ai_services();
            if ($ai_services && method_exists($ai_services, 'has_available_services')) {
                $has_ai_services = $ai_services->has_available_services();
                error_log('直接检测has_available_services()返回: ' . ($has_ai_services ? 'true' : 'false'));
                
                // 尝试获取所有可用的服务
                if ($has_ai_services && method_exists($ai_services, 'get_available_service')) {
                    try {
                        $available_service = $ai_services->get_available_service();
                        
                        if ($available_service) {
                            $service_name = '';
                            $service_label = '';
                            
                            // 尝试不同的方法获取服务信息
                            if (method_exists($available_service, 'get_name')) {
                                $service_name = $available_service->get_name();
                                error_log('通过get_name()获取到服务名称: ' . $service_name);
                            } elseif (property_exists($available_service, 'name')) {
                                $service_name = $available_service->name;
                                error_log('通过name属性获取到服务名称: ' . $service_name);
                            } else {
                                $service_name = 'default';
                                error_log('无法获取服务名称，使用默认值');
                            }
                            
                            if (method_exists($available_service, 'get_label')) {
                                $service_label = $available_service->get_label();
                                error_log('通过get_label()获取到服务标签: ' . $service_label);
                            } elseif (property_exists($available_service, 'label')) {
                                $service_label = $available_service->label;
                                error_log('通过label属性获取到服务标签: ' . $service_label);
                            } else {
                                $service_label = '默认AI服务';
                                error_log('无法获取服务标签，使用默认值');
                            }
                            
                            // 确保服务名称非空
                            if (empty($service_name)) {
                                error_log('获取到的服务名称为空，使用服务标签作为名称');
                                $service_name = $service_label;
                            }
                            
                            // 如果标签也为空，使用默认值
                            if (empty($service_label)) {
                                error_log('获取到的服务标签为空，使用默认标签');
                                $service_label = '未命名AI服务';
                            }
                            
                            $available_services[] = array(
                                'name' => $service_name,
                                'label' => $service_label
                            );
                            
                            error_log('成功添加可用服务: ' . $service_name . ' - ' . $service_label);
                        } else {
                            error_log('获取可用服务返回null');
                        }
                    } catch (Exception $e) {
                        error_log('获取可用服务出错: ' . $e->getMessage());
                    }
                } else {
                    error_log('has_available_services()返回false或方法不存在');
                }
            } else {
                error_log('ai_services()返回null或没有has_available_services方法');
            }
            
            // 尝试手动添加一个"自动选择"服务
            if (empty($available_services) && $has_ai_services) {
                error_log('没有获取到服务，但has_available_services返回true，添加默认服务');
                $available_services[] = array(
                    'name' => '',  // 空名称表示自动选择
                    'label' => '自动选择AI服务'
                );
            }
            
        } catch (Exception $e) {
            error_log('检查AI服务状态出错: ' . $e->getMessage());
        }
    } else {
        error_log('ai_services函数不存在，无法获取服务列表');
    }
    
    error_log('渲染AI审核设置页面：完成检查AI服务状态，发现 ' . count($available_services) . ' 个服务');
    ?>
    <div class="wrap">
        <h1>AI文章审核设置</h1>
        
        <?php if (!$has_ai_services): ?>
        <div class="notice notice-warning">
            <p>未检测到AI Services插件或未配置AI服务。请安装并激活<a href="https://wordpress.org/plugins/ai-services/" target="_blank">AI Services</a>插件，并<a href="<?php echo admin_url('options-general.php?page=ai-services-settings'); ?>">配置AI服务API密钥</a>。</p>
            <p>
                <a href="<?php echo esc_url(admin_url('plugin-install.php?s=ai-services&tab=search&type=term')); ?>" class="button button-primary">安装AI Services插件</a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=ai-services-settings')); ?>" class="button">配置AI Services</a>
                <a href="<?php echo esc_url(add_query_arg('test_ai_service', '1')); ?>" class="button">测试AI服务</a>
            </p>
        </div>
        <?php else: ?>
        <div class="notice notice-success">
            <p>AI Services插件已成功配置。您可以启用AI审核功能并调整以下设置。</p>
            <p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=ai-services-settings')); ?>" class="button">管理AI服务</a>
                <a href="<?php echo esc_url(add_query_arg('test_ai_service', '1')); ?>" class="button">测试AI服务</a>
            </p>
        </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">启用AI审核</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_review_enabled" value="1" <?php checked($settings['enabled']); ?>>
                            启用AI自动审核文章和修改
                        </label>
                        <?php if (!$has_ai_services): ?>
                        <p class="description" style="color: #d63638;">需要安装并配置AI Services插件才能使用此功能</p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">AI服务提供商</th>
                    <td>
                        <select name="ai_review_service">
                            <option value="">自动选择</option>
                            <?php 
                            // 使用前面收集的服务列表
                            foreach ($available_services as $service): 
                            ?>
                                <option value="<?php echo esc_attr($service['name']); ?>" <?php selected($settings['service'], $service['name']); ?>>
                                    <?php echo esc_html($service['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">选择要使用的AI服务提供商，或选择"自动选择"使用当前可用的服务</p>
                        <?php if (!$has_ai_services): ?>
                        <p class="description" style="color: #d63638;">请先安装并配置AI Services插件以显示可用的AI服务</p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">最低通过分数</th>
                    <td>
                        <input type="number" name="ai_review_min_score" value="<?php echo esc_attr($settings['min_score']); ?>" min="0" max="1" step="0.1">
                        <p class="description">AI评分达到此分数时自动通过（0-1之间）</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">自动通过</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_review_auto_approve" value="1" <?php checked($settings['auto_approve']); ?>>
                            当AI评分达到通过标准时自动批准文章或修改
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">通知作者</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_review_notification" value="1" <?php checked($settings['notification']); ?>>
                            AI审核完成后通知作者
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">新文章审核提示语</th>
                    <td>
                        <textarea name="ai_review_prompt_template" rows="8" cols="80" class="large-text"><?php echo esc_textarea($settings['prompt_template']); ?></textarea>
                        <p class="description">用于审核新文章的提示语模板。使用 {content} 表示文章内容。</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">文章修改审核提示语</th>
                    <td>
                        <textarea name="ai_review_revision_prompt_template" rows="8" cols="80" class="large-text"><?php echo esc_textarea($settings['revision_prompt_template']); ?></textarea>
                        <p class="description">用于审核文章修改的提示语模板。使用 {original_content} 表示原文内容，{new_content} 表示修改后的内容。</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="保存设置">
            </p>
        </form>
    </div>
    <?php
}

// 使用AI服务审核文章
function ai_review_post($post_id) {
    try {
        // 获取文章
        $post = get_post($post_id);
        if (!$post) {
            error_log('AI审核失败：无效的文章ID ' . $post_id);
            return false;
        }
        
        // 确保AI Services插件已安装
        if (!function_exists('ai_services')) {
            error_log('AI审核失败：AI Services插件未安装');
        return false;
    }
    
        // 获取AI审核设置
    $settings = get_ai_review_settings();
        
        // 确保有可用的AI服务
        $service = ai_services()->get_available_service();
        if (!$service) {
            error_log('AI审核失败：没有可用的AI服务');
            return false;
        }
        
        try {
        // 使用参数获取文本生成模型
            $text_generation_capability = 'TEXT_GENERATION';
            if (class_exists('Felix_Arntz\\AI_Services\\Services\\API\\Enums\\AI_Capability')) {
                $text_generation_capability = \Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability::TEXT_GENERATION;
            }
            
        // 增加超时参数和重试机制
        $attempts = 0;
        $max_attempts = 2;
        $timeout_error = false;
        
        do {
            $attempts++;
            $timeout_error = false;
            
            if ($attempts > 1) {
                error_log('AI审核第' . $attempts . '次尝试...');
            }
            
            try {
                // 获取模型，添加超时参数
                $model_args = array(
                'feature' => 'ai-post-review',
                'capabilities' => array($text_generation_capability),
                );
                
                // 添加超时参数，如果支持的话
                if (method_exists($service, 'supports_option') && $service->supports_option('timeout')) {
                    $model_args['timeout'] = 30; // 30秒超时
                }
                
                $model = $service->get_model($model_args);
                
                if (!$model) {
                    error_log('AI审核失败：无法获取文本生成模型');
                return false;
            }
            
                // 生成文本，使用更短的提示语
                $short_content = wp_trim_words($post->post_content, 500, '...');
                $short_prompt = str_replace(
                    '{content}', 
                    $post->post_title . "\n\n" . $short_content,
                    $settings['prompt_template']
                );
                
                $candidates = $model->generate_text($short_prompt);
                
                // 记录原始响应以便调试
                if (current_user_can('administrator')) {
                    error_log('AI审核生成结果原始数据类型: ' . gettype($candidates) . 
                             (is_object($candidates) ? ', 类名: ' . get_class($candidates) : ''));
                }
                
                // 使用辅助函数提取文本
                $response_text = extract_text_from_candidates($candidates, current_user_can('administrator'));
                
                // 强制类型检查 - 确保响应文本是字符串
                if (!is_string($response_text)) {
                    if (current_user_can('administrator')) {
                        error_log('严重警告: extract_text_from_candidates返回的不是字符串而是 ' . gettype($response_text));
                    }
                    
                    // 强制转换为字符串
                    if (is_array($response_text)) {
                        // 尝试提取常见字段
                        if (isset($response_text['text'])) {
                            $response_text = $response_text['text'];
                        } elseif (isset($response_text['content'])) {
                            if (is_string($response_text['content'])) {
                                $response_text = $response_text['content'];
                            } else {
                                $response_text = json_encode($response_text['content']);
                            }
                        } else {
                            $response_text = json_encode($response_text);
                        }
                    } else {
                        $response_text = "AI审核返回了无法解析的内容。";
                    }
                    
                    if (current_user_can('administrator')) {
                        error_log('已将非字符串响应强制转换为字符串，前100字符: ' . substr($response_text, 0, 100));
                    }
                }
                
                    // 如果提取失败但有响应，使用默认响应
                if ($response_text === false) {
                        if (current_user_can('administrator')) {
                            error_log('无法从响应中提取文本，使用默认响应');
                }
                        // 使用默认文本和评分
                        $response_text = "AI审核完成，但无法提取具体评价内容。根据内容质量，给予默认评分。";
                        $score = 0.7; // 默认70%评分
                    } else {
                // 记录提取的文本
                if (current_user_can('administrator')) {
                        // 确保是字符串再使用substr
                        $log_text = is_string($response_text) ? $response_text : json_encode($response_text);
                        error_log('AI审核提取的文本: ' . substr($log_text, 0, 200) . 
                                 (strlen($log_text) > 200 ? '...' : ''));
                        }
                        
                        // 从响应中解析评分
                        $score = extract_score_from_response($response_text);
                }
                
            } catch (Exception $e) {
                error_log('AI审核出错：' . $e->getMessage());
                
                // 检查是否是超时错误
                if (strpos($e->getMessage(), 'timed out') !== false || 
                    strpos($e->getMessage(), 'timeout') !== false) {
                    $timeout_error = true;
                    error_log('AI审核超时，将重试...');
                } else {
                    // 其他错误直接返回失败
                    return false;
                }
            }
            
        } while ($timeout_error && $attempts < $max_attempts);
        
        // 如果所有尝试都超时失败
        if ($timeout_error) {
            error_log('AI审核多次尝试后仍然超时，放弃');
            return false;
                }
                
                // 保存审核结果
                update_post_meta($post_id, '_ai_review_score', $score);
                update_post_meta($post_id, '_ai_review_feedback', $response_text);
                update_post_meta($post_id, '_ai_review_date', current_time('mysql'));
                
            // 检查是否需要自动批准
                if ($settings['auto_approve'] && $score >= $settings['min_score']) {
                    update_post_meta($post_id, '_ai_auto_approved', '1');
                    
                // 如果是原创文章（不是修订版本），可以自动发布
                if ($post->post_type !== 'revision' && $post->post_status === 'pending') {
                    wp_publish_post($post_id);
                    error_log('文章已通过AI审核并自动发布: ' . $post_id);
                    }
                }
                
                return array(
                    'score' => $score,
                    'feedback' => $response_text,
                    'passed' => $score >= $settings['min_score']
                );
    } catch (Exception $e) {
        error_log('AI审核出错：' . $e->getMessage());
        return false;
    }
    } catch (Exception $e) {
        error_log('AI审核出错：' . $e->getMessage());
        return false;
    }
}

// 从响应文本中提取评分
function extract_score_from_response($text, $debug = false) {
    if ($debug) {
        error_log("开始提取评分，输入类型：" . gettype($text));
    }
    
    // 确保输入为字符串
    if (!is_string($text)) {
        if (is_array($text) && isset($text['text'])) {
                $text = $text['text'];
                } else {
            $text = print_r($text, true);
        }
    }
    
    // 如果string为空，返回默认值
    if (empty($text)) {
        if ($debug) error_log("输入为空，返回默认值0.7");
        return 0.7;
    }
    
    // 初始化日志
    $log = array();
    $log[] = "开始从响应中提取评分：长度" . strlen($text);
    
    // 预处理：转换所有可能的分数格式为标准格式
    $cleaned_text = $text;
    
    // 保存原始文本，用于调试
    $original_text = $text;
    
    // 检查特殊评分：直接数字+分
    if (preg_match('/(\d+)\s*[分]/', $text, $matches)) {
        $score = (float)$matches[1] / 10;
        $log[] = "找到中文数字分数：{$matches[1]}分，转换为：$score";
        if ($debug) error_log(implode("\n", $log));
        return min(max($score, 0), 1); // 确保范围在0-1之间
    }
    
    // 检查特殊格式：X/10或X/Y格式
    if (preg_match('/(\d+)\s*\/\s*(\d+)/', $text, $matches)) {
        $numerator = (float)$matches[1];
        $denominator = (float)$matches[2];
        
        if ($denominator > 0) {
            $score = $numerator / $denominator;
            $log[] = "找到分数格式：{$matches[0]}，转换为：$score";
            if ($debug) error_log(implode("\n", $log));
            return min(max($score, 0), 1);
        }
    }
    
    // 尝试提取特殊的数字+分格式（中文数字）
        $chinese_numbers = array(
        '零' => 0, '一' => 1, '二' => 2, '三' => 3, '四' => 4, 
        '五' => 5, '六' => 6, '七' => 7, '八' => 8, '九' => 9, '十' => 10
    );
    foreach ($chinese_numbers as $cn => $num) {
        if (strpos($text, $cn . '分') !== false) {
            $score = $num / 10;
            $log[] = "找到中文数字：{$cn}分，转换为：$score";
            if ($debug) error_log(implode("\n", $log));
            return min(max($score, 0), 1);
        }
    }
    
    // 标准数字范围提取
    if (preg_match('/(\d+(\.\d+)?)/', $text, $matches)) {
        $number = (float)$matches[1];
        
        // 判断数字范围和上下文
        if ($number >= 0 && $number <= 1) {
            $log[] = "找到0-1范围内的数字：$number";
            if ($debug) error_log(implode("\n", $log));
            return $number;
        } elseif ($number > 1 && $number <= 10) {
            $score = $number / 10;
            $log[] = "找到1-10范围内的数字：$number，转换为：$score";
            if ($debug) error_log(implode("\n", $log));
            return $score;
        } elseif ($number > 10 && $number <= 100) {
            $score = $number / 100;
            $log[] = "找到10-100范围内的数字：$number，转换为：$score";
            if ($debug) error_log(implode("\n", $log));
        return $score;
        }
    }
    
    // 关键词检测
    $keyword_patterns = array(
        // 特殊情况：低质量或空内容的关键词
        '/(没有实质|没有内容|缺乏内容|空洞|无实质内容|placeholder|不合格|无价值)/i' => 0.2,
        
        // 负面关键词，对应低分
        '/(很差|很糟|很烂|不好|terrible|poor|awful|bad|low quality)/i' => 0.3,
        '/(差|糟|烂|不满意|不合格|unsatisfactory|not good)/i' => 0.4,
        
        // 中等偏下关键词
        '/(一般|mediocre|average|普通|中等偏下|below average)/i' => 0.5,
        
        // 中等关键词
        '/(中等|average|中间|middle|一半|acceptable)/i' => 0.55,
        
        // 中等偏上关键词
        '/(尚可|可以|above average|better than average|中等偏上)/i' => 0.6,
        
        // 正面关键词，对应高分
        '/(良好|好|good|fine|decent|中等偏上)/i' => 0.65,
        '/(很好|优秀|great|excellent|出色|very good)/i' => 0.75,
        '/(非常好|卓越|exceptional|outstanding|极佳|excellent)/i' => 0.85,
        '/(完美|顶级|极致|perfect|top-notch|顶尖|flawless)/i' => 0.95
    );
    
    foreach ($keyword_patterns as $pattern => $value) {
        if (preg_match($pattern, $text)) {
            $log[] = "通过关键词匹配得到分数：$value, 匹配模式: $pattern";
            if ($debug) error_log(implode("\n", $log));
            return $value;
        }
    }
    
    // 如果没有匹配到任何模式，返回默认分数
    $log[] = "未匹配任何评分模式，返回默认值0.7";
    if ($debug) {
        error_log(implode("\n", $log));
        error_log("原始文本：" . substr($original_text, 0, 500) . (strlen($original_text) > 500 ? "...(截断)" : ""));
    }
    return 0.7;
}

// 对文章修改进行AI审核
function ai_review_revision($revision_id) {
    try {
        // 获取修订版本
    $revision = get_post($revision_id);
    if (!$revision || $revision->post_type !== 'revision') {
            error_log('AI审核修订失败：无效的修订ID ' . $revision_id);
        return false;
    }
    
        // 获取父文章
        $parent_post_id = $revision->post_parent;
        $parent_post = get_post($parent_post_id);
    if (!$parent_post) {
            error_log('AI审核修订失败：找不到父文章 ' . $parent_post_id);
        return false;
    }
    
        // 确保AI Services插件已安装
        if (!function_exists('ai_services')) {
            error_log('AI审核修订失败：AI Services插件未安装');
            return false;
        }
        
        // 获取AI审核设置
        $settings = get_ai_review_settings();
        
        // 确保有可用的AI服务
        $service = ai_services()->get_available_service();
        if (!$service) {
            error_log('AI审核修订失败：没有可用的AI服务');
            return false;
        }
        
        try {
            // 使用参数获取文本生成模型
            $text_generation_capability = 'TEXT_GENERATION';
            if (class_exists('Felix_Arntz\\AI_Services\\Services\\API\\Enums\\AI_Capability')) {
                $text_generation_capability = \Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability::TEXT_GENERATION;
            }
            
            // 增加超时参数和重试机制
            $attempts = 0;
            $max_attempts = 2;
            $timeout_error = false;
            
            do {
                $attempts++;
                $timeout_error = false;
                
                if ($attempts > 1) {
                    error_log('AI审核修订第' . $attempts . '次尝试...');
                }
                
                try {
                    // 获取模型，添加超时参数
                    $model_args = array(
                'feature' => 'ai-revision-review',
                'capabilities' => array($text_generation_capability),
                    );
                    
                    // 添加超时参数，如果支持的话
                    if (method_exists($service, 'supports_option') && $service->supports_option('timeout')) {
                        $model_args['timeout'] = 30; // 30秒超时
                    }
                    
                    $model = $service->get_model($model_args);
                    
                    if (!$model) {
                        error_log('AI审核修订失败：无法获取文本生成模型');
                return false;
            }
            
                    // 获取内容，限制长度避免超出模型上下文窗口
                    $original_content = wp_trim_words($parent_post->post_content, 500, '...');
                    $new_content = wp_trim_words($revision->post_content, 500, '...');
                    
                    // 使用设置中的提示语模板
                    $prompt = str_replace(
                        array('{original_content}', '{new_content}'),
                        array($original_content, $new_content),
                        $settings['revision_prompt_template']
                    );
                    
                $candidates = $model->generate_text($prompt);
                
                // 记录原始响应以便调试
                if (current_user_can('administrator')) {
                        error_log('AI审核修订生成结果原始数据类型: ' . gettype($candidates) . 
                             (is_object($candidates) ? ', 类名: ' . get_class($candidates) : ''));
                }
                
                // 使用辅助函数提取文本
                $response_text = extract_text_from_candidates($candidates, current_user_can('administrator'));
                
                // 强制类型检查 - 确保响应文本是字符串
                if (!is_string($response_text)) {
                    if (current_user_can('administrator')) {
                        error_log('严重警告: 修订审核extract_text_from_candidates返回的不是字符串而是 ' . gettype($response_text));
                    }
                    
                    // 强制转换为字符串
                    if (is_array($response_text)) {
                        // 尝试提取常见字段
                        if (isset($response_text['text'])) {
                            $response_text = $response_text['text'];
                        } elseif (isset($response_text['content'])) {
                            if (is_string($response_text['content'])) {
                                $response_text = $response_text['content'];
                            } else {
                                $response_text = json_encode($response_text['content']);
                            }
                        } else {
                            $response_text = json_encode($response_text);
                        }
                    } else {
                        $response_text = "AI修订审核返回了无法解析的内容。";
                    }
                    
                    if (current_user_can('administrator')) {
                        error_log('已将修订审核非字符串响应强制转换为字符串，前100字符: ' . substr($response_text, 0, 100));
                    }
                }
                
                    // 如果提取失败但有响应，使用默认响应
                if ($response_text === false) {
                        if (current_user_can('administrator')) {
                        error_log('无法从修订审核响应中提取文本，使用默认响应');
                }
                        // 使用默认文本和评分
                        $response_text = "AI审核修订完成，但无法提取具体评价内容。根据修改质量，给予默认评分。";
                        $score = 0.7; // 默认70%评分
                    } else {
                // 记录提取的文本
                if (current_user_can('administrator')) {
                        // 确保是字符串再使用substr
                        $log_text = is_string($response_text) ? $response_text : json_encode($response_text);
                        error_log('AI审核修订提取的文本: ' . substr($log_text, 0, 200) . 
                                 (strlen($log_text) > 200 ? '...' : ''));
                }
                
                        // 从响应中解析评分
                        $score = extract_score_from_response($response_text);
                    }
                    
                } catch (Exception $e) {
                    error_log('AI审核修订出错：' . $e->getMessage());
                    
                    // 检查是否是超时错误
                    if (strpos($e->getMessage(), 'timed out') !== false || 
                        strpos($e->getMessage(), 'timeout') !== false) {
                        $timeout_error = true;
                        error_log('AI审核修订超时，将重试...');
                    } else {
                        // 其他错误直接返回失败
                        return false;
                    }
                }
                
            } while ($timeout_error && $attempts < $max_attempts);
            
            // 如果所有尝试都超时失败
            if ($timeout_error) {
                error_log('AI审核修订多次尝试后仍然超时，放弃');
                return false;
                }
                
            // 保存审核结果到修订版本的元数据
            update_metadata('post', $revision_id, '_ai_review_score', $score);
            update_metadata('post', $revision_id, '_ai_review_feedback', $response_text);
            update_metadata('post', $revision_id, '_ai_review_date', current_time('mysql'));
                
            // 检查是否需要自动批准
                if ($settings['auto_approve'] && $score >= $settings['min_score']) {
                update_metadata('post', $revision_id, '_ai_auto_approved', '1');
                
                // 可以在这里添加自动接受修订的逻辑，但通常修订版本需要手动合并
                // 记录可以自动批准
                error_log('修订版本已通过AI审核，可以自动批准: ' . $revision_id);
                }
                
                return array(
                    'score' => $score,
                    'feedback' => $response_text,
                    'passed' => $score >= $settings['min_score']
                );
            
            } catch (Exception $e) {
            error_log('AI审核修订处理出错：' . $e->getMessage());
                return false;
            }
        } catch (Exception $e) {
        error_log('AI审核修订出错：' . $e->getMessage());
        return false;
    }
}

// 当新文章提交时触发AI审核
function trigger_ai_review_for_new_post($post_id, $post, $update) {
    // 添加调试日志
    if (current_user_can('administrator')) {
        error_log('触发新文章AI审核: post_id=' . $post_id . ', post_type=' . $post->post_type . ', post_status=' . $post->post_status . ', update=' . ($update ? 'true' : 'false'));
    }
    
    // 检查是否为文章类型
    if ($post->post_type !== 'post') {
        if (current_user_can('administrator')) {
            error_log('不满足触发AI审核条件: 非文章类型 post_type=' . $post->post_type);
        }
        return;
    }
    
    // 检查是否为待处理或草稿状态
    if (!in_array($post->post_status, array('pending', 'draft'))) {
        if (current_user_can('administrator')) {
            error_log('不满足触发AI审核条件: 状态不是待处理或草稿 post_status=' . $post->post_status);
        }
        return;
    }
    
    // 如果是更新且不是修订版本，检查内容是否有实质性变化
    if ($update && !wp_is_post_revision($post_id)) {
        // 获取之前的内容
        $old_post = get_post($post_id, ARRAY_A);
        if ($old_post && $old_post['post_content'] === $post->post_content && $old_post['post_title'] === $post->post_title) {
            if (current_user_can('administrator')) {
                error_log('不满足触发AI审核条件: 更新文章但内容未变化');
            }
            return;
        }
    }
    
    // 避免重复审核
    if (get_post_meta($post_id, '_ai_review_score', true) !== '') {
        if (current_user_can('administrator')) {
            error_log('文章已有AI审核结果，跳过审核');
        }
        return;
    }
    
    // 确保已安装AI Services插件
    if (!function_exists('ai_services')) {
        if (current_user_can('administrator')) {
            error_log('AI Services插件未安装，无法执行AI审核');
        }
        return;
    }
    
    // 确保有可用的AI服务
    if (!ai_services()->has_available_services()) {
        if (current_user_can('administrator')) {
            error_log('AI Services插件未配置可用服务，无法执行AI审核');
        }
        return;
    }
    
    // 确保AI审核功能已启用
    $settings = get_ai_review_settings();
    if (!$settings['enabled']) {
        if (current_user_can('administrator')) {
            error_log('AI审核功能未启用，跳过审核');
        }
        return;
    }
    
    if (current_user_can('administrator')) {
        error_log('开始执行AI审核...');
    }
    
    // 进行AI审核
    $result = ai_review_post($post_id);
    
    if ($result) {
        if (current_user_can('administrator')) {
            error_log('AI审核完成: 分数=' . $result['score'] . ', 通过=' . ($result['passed'] ? 'true' : 'false'));
        }
    } else {
        if (current_user_can('administrator')) {
            error_log('AI审核失败');
        }
    }
}
add_action('save_post', 'trigger_ai_review_for_new_post', 20, 3);

// 当新的修改提交时触发AI审核
function trigger_ai_review_for_revision($revision_id) {
    // 添加调试日志
    if (current_user_can('administrator')) {
        error_log('触发修改版本AI审核: revision_id=' . $revision_id);
    }
    
    $revision = get_post($revision_id);
    if (!$revision || $revision->post_type !== 'revision') {
        if (current_user_can('administrator')) {
            error_log('不是有效的修改版本，跳过审核: ' . ($revision ? $revision->post_type : 'null'));
        }
        return;
    }
    
    // 获取父文章信息
    $parent_id = wp_get_post_parent_id($revision_id);
    if (!$parent_id) {
        if (current_user_can('administrator')) {
            error_log('无法获取修改版本的父文章ID，跳过审核');
        }
        return;
    }
    
    $parent_post = get_post($parent_id);
    if (!$parent_post || $parent_post->post_type !== 'post') {
        if (current_user_can('administrator')) {
            error_log('父文章无效或不是post类型，跳过审核: ' . ($parent_post ? $parent_post->post_type : 'null'));
        }
        return;
    }
    
    if (current_user_can('administrator')) {
        error_log('修改版本信息: revision_id=' . $revision_id . ', parent_id=' . $parent_id . ', 标题=' . $revision->post_title);
    }
    
    // 检查是否需要审核（任何修改版本都应该审核）
    // 之前的代码检查_wp_revision_status，但实际上WordPress的修改版本可能不用此元数据
    
    // 避免重复审核
    if (get_post_meta($revision_id, '_ai_review_score', true) !== '') {
        if (current_user_can('administrator')) {
            error_log('修改版本已有AI审核结果，跳过审核');
        }
        return;
    }
    
    // 确保已安装AI Services插件
    if (!function_exists('ai_services')) {
        if (current_user_can('administrator')) {
            error_log('AI Services插件未安装，无法执行修改版本AI审核');
        }
        return;
    }
    
    // 确保有可用的AI服务
    $ai_services = null;
    try {
        $ai_services = ai_services();
        if (!$ai_services || !method_exists($ai_services, 'has_available_services')) {
            if (current_user_can('administrator')) {
                error_log('AI Services插件未正确加载，无法执行修改版本AI审核');
            }
            return;
        }
        
        if (!$ai_services->has_available_services()) {
            if (current_user_can('administrator')) {
                error_log('AI Services插件未配置可用服务，无法执行修改版本AI审核');
            }
            return;
        }
    } catch (Exception $e) {
        if (current_user_can('administrator')) {
            error_log('检查AI服务时出错: ' . $e->getMessage());
        }
        return;
    }
    
    // 确保已启用AI审核功能
    $settings = get_ai_review_settings();
    if (!$settings['enabled']) {
        if (current_user_can('administrator')) {
            error_log('AI审核功能未启用，跳过修改版本审核');
        }
        return;
    }
    
    if (current_user_can('administrator')) {
        error_log('开始执行修改版本AI审核...');
    }
    
    // 进行AI审核
    $result = ai_review_revision($revision_id);
    
    if ($result) {
        if (current_user_can('administrator')) {
            error_log('修改版本AI审核完成: 分数=' . $result['score'] . ', 通过=' . ($result['passed'] ? 'true' : 'false'));
        }
    } else {
        if (current_user_can('administrator')) {
            error_log('修改版本AI审核失败，请检查ai_review_revision函数的执行过程');
        }
    }
}
add_action('wp_insert_post', 'trigger_ai_review_for_revision', 20, 1);

// 获取审核历史
function get_ai_review_history($post_id) {
    global $wpdb;
    
    // 先检查当前文章的审核记录
    $current_score = get_post_meta($post_id, '_ai_review_score', true);
    $current_feedback = get_post_meta($post_id, '_ai_review_feedback', true);
    $current_date = get_post_meta($post_id, '_ai_review_date', true);
    
    $history = array();
    
    if ($current_score !== '') {
        $history[] = array(
            'post_id' => $post_id,
            'revision_id' => 0,
            'score' => floatval($current_score),
            'feedback' => $current_feedback,
            'date' => $current_date,
            'is_current' => true
        );
    }
    
    // 获取该文章的修订版本
    $revisions = wp_get_post_revisions($post_id);
    
    foreach ($revisions as $revision) {
        $revision_score = get_post_meta($revision->ID, '_ai_review_score', true);
        if ($revision_score !== '') {
            $history[] = array(
                'post_id' => $post_id,
                'revision_id' => $revision->ID,
                'score' => floatval($revision_score),
                'feedback' => get_post_meta($revision->ID, '_ai_review_feedback', true),
                'date' => get_post_meta($revision->ID, '_ai_review_date', true),
                'is_current' => false,
                'author' => get_the_author_meta('display_name', $revision->post_author),
                'edit_summary' => get_post_meta($revision->ID, '_edit_summary', true)
            );
        }
    }
    
    // 按日期排序
    usort($history, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $history;
}

// 添加AI审核结果展示
function display_ai_review_results($post_id) {
    $score = get_post_meta($post_id, '_ai_review_score', true);
    if ($score === '') {
        return '<div class="ai-review-status">尚未进行AI审核</div>';
    }
    
    $score = floatval($score);
    $feedback = get_post_meta($post_id, '_ai_review_feedback', true);
    $date = get_post_meta($post_id, '_ai_review_date', true);
    $settings = get_ai_review_settings();
    $passed = $score >= $settings['min_score'];
    
    $html = '<div class="ai-review-results">';
    $html .= '<h4>AI审核结果</h4>';
    $html .= '<div class="ai-review-score">';
    $html .= '<span class="score-label">评分：</span>';
    $html .= '<span class="score-value ' . ($passed ? 'passed' : 'failed') . '">' . round($score * 10, 1) . '/10</span>';
    $html .= '</div>';
    
    $html .= '<div class="ai-review-feedback">';
    $html .= '<strong>反馈意见：</strong>';
    $html .= '<div class="feedback-content">' . nl2br(esc_html($feedback)) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="ai-review-date">';
    $html .= '<small>审核时间：' . date_i18n('Y-m-d H:i:s', strtotime($date)) . '</small>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

// 将样式添加到头部
function add_ai_review_styles() {
    ?>
    <style>
        .ai-review-results {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .ai-review-score {
            margin-bottom: 10px;
            font-size: 16px;
        }
        .score-value {
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .score-value.passed {
            background-color: #d4edda;
            color: #155724;
        }
        .score-value.failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .ai-review-feedback {
            margin-bottom: 10px;
        }
        .feedback-content {
            background-color: #fff;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 3px;
            margin-top: 5px;
        }
        .ai-review-date {
            color: #6c757d;
        }
    </style>
    <?php
}
add_action('wp_head', 'add_ai_review_styles');

// 确保用户有权访问AI服务
function ensure_ai_services_capabilities() {
    // 检查当前用户是否是管理员
    if (current_user_can('administrator')) {
        // 如果是管理员但没有ais_access_services权限，尝试添加
        if (!current_user_can('ais_access_services')) {
            error_log('管理员没有ais_access_services权限，尝试临时添加');
            
            // 获取当前管理员用户
            $user = wp_get_current_user();
            if ($user && $user->ID) {
                // 临时为当前管理员添加权限
                $user->add_cap('ais_access_services');
                error_log('已为用户ID ' . $user->ID . ' 添加ais_access_services权限');
                
                // 尝试添加特定的服务访问权限
                $user->add_cap('ais_access_service');
                error_log('已为用户ID ' . $user->ID . ' 添加ais_access_service权限');
                
                // 添加使用AI功能的权限
                $user->add_cap('ais_use_playground');
                error_log('已为用户ID ' . $user->ID . ' 添加ais_use_playground权限');
                
                return true;
            }
        } else {
            // 管理员已有权限
            return true;
        }
    }
    
    // 非管理员用户，检查是否有权限
    if (current_user_can('ais_access_services')) {
        return true;
    }
    
    // 没有权限
    return false;
}

// 在合适的时机检查权限
add_action('admin_init', 'ensure_ai_services_capabilities');

// 测试AI服务是否正常工作
function test_ai_service() {
    // 记录测试开始
    error_log('-----开始测试AI服务-----');
    
    // 检查AI Services插件是否安装
    if (!function_exists('ai_services')) {
        error_log('测试失败：AI Services插件未安装');
        return false;
    }
    
    // 获取AI Services实例
    try {
        $ai_services = ai_services();
        if (!$ai_services) {
            error_log('测试失败：ai_services()返回null');
            return false;
        }
        error_log('成功获取ai_services实例');
        
        // 检查是否有可用服务
        if (!method_exists($ai_services, 'has_available_services')) {
            error_log('测试失败：ai_services实例没有has_available_services方法');
            return false;
        }
        
        $has_services = $ai_services->has_available_services();
        error_log('has_available_services()返回: ' . ($has_services ? 'true' : 'false'));
        
        if (!$has_services) {
            error_log('测试失败：没有可用的AI服务');
            return false;
        }
        
        // 尝试获取可用服务
        if (!method_exists($ai_services, 'get_available_service')) {
            error_log('测试失败：ai_services实例没有get_available_service方法');
            return false;
        }
        
        $service = $ai_services->get_available_service();
        if (!$service) {
            error_log('测试失败：get_available_service()返回null');
            return false;
        }
        error_log('成功获取可用服务');
        
        // 获取服务信息
        if (method_exists($service, 'get_name')) {
            error_log('服务名称：' . $service->get_name());
        }
        if (method_exists($service, 'get_label')) {
            error_log('服务标签：' . $service->get_label());
        }
        
        // 尝试获取模型
        if (!method_exists($service, 'get_model')) {
            error_log('测试失败：服务实例没有get_model方法');
            return false;
        }
        
        // 使用参数获取文本生成模型
        $text_generation_capability = 'TEXT_GENERATION';
        if (class_exists('Felix_Arntz\\AI_Services\\Services\\API\\Enums\\AI_Capability')) {
            $text_generation_capability = \Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability::TEXT_GENERATION;
        }
        
        $model = $service->get_model(array(
            'feature' => 'ai-service-test',
            'capabilities' => array($text_generation_capability),
        ));
        
        if (!$model) {
            error_log('测试失败：无法获取文本生成模型');
            return false;
        }
        error_log('成功获取文本生成模型');
        
        // 测试生成文本
        if (!method_exists($model, 'generate_text')) {
            error_log('测试失败：模型实例没有generate_text方法');
            return false;
        }
        
        $prompt = "请简短回答：今天是什么日子？";
        try {
            $candidates = $model->generate_text($prompt);
            
            if (empty($candidates)) {
                error_log('测试失败：generate_text返回空结果');
                return false;
            }
            
            error_log('成功生成文本：' . print_r($candidates, true));
            error_log('-----AI服务测试成功-----');
            return true;
        } catch (Exception $e) {
            error_log('测试失败：生成文本时出错 - ' . $e->getMessage());
            return false;
        }
    } catch (Exception $e) {
        error_log('测试失败：' . $e->getMessage());
        error_log('-----AI服务测试失败-----');
        return false;
    }
}

// 在适当时机触发测试
add_action('admin_init', function() {
    if (isset($_GET['test_ai_service']) && current_user_can('administrator')) {
        test_ai_service();
    }
}); 

/**
 * 从AI服务返回的Candidates对象中提取文本内容
 * 
 * @param mixed $candidates API返回的Candidates对象或其他格式数据
 * @param bool $debug 是否输出调试信息
 * @return string|false 提取的文本内容，失败时返回false
 */
function extract_text_from_candidates($candidates, $debug = false) {
    // 初始化日志
    $log = array();
    $log[] = "==== 开始提取文本 - 输入类型: " . gettype($candidates) . " ====";
    
    // 如果开启debug，立即输出调试信息
    if ($debug) {
        error_log("提取文本开始 - 输入类型: " . gettype($candidates));
    }
    
    // 如果已经是字符串，直接返回
    if (is_string($candidates)) {
        $log[] = "输入已经是字符串类型，直接返回";
        if ($debug) error_log(implode("\n", $log));
        return $candidates;
    }
    
    // 如果是null或空
    if (is_null($candidates) || (is_array($candidates) && empty($candidates))) {
        $log[] = "输入为null或空数组";
        if ($debug) error_log(implode("\n", $log));
        return false;
    }
    
    try {
        // 尝试处理Felix_Arntz\AI_Services\Services\API\Types\Candidates类型
        if (is_object($candidates)) {
            $class_name = get_class($candidates);
            $log[] = "输入是对象，类名: " . $class_name;
            
            if ($class_name === 'Felix_Arntz\AI_Services\Services\API\Types\Candidates') {
                $log[] = "检测到Candidates对象，使用专用处理方法";
                
                // 记录所有可用方法
                $methods = get_class_methods($candidates);
                $log[] = "Candidates对象可用方法: " . implode(", ", $methods);
                
                // 方法0: 优先使用反射方法获取内部属性
                try {
                    $log[] = "尝试使用反射获取私有属性";
                    $reflection = new ReflectionClass($candidates);
                    $prop = $reflection->getProperty('candidates');
                    $prop->setAccessible(true);
                    $candidates_array = $prop->getValue($candidates);
                    
                    if (is_array($candidates_array) && !empty($candidates_array)) {
                        $log[] = "成功通过反射获取candidates数组，长度: " . count($candidates_array);
                        $first_candidate = $candidates_array[0];
                        
                        if (is_object($first_candidate)) {
                            $candidate_class = get_class($first_candidate);
                            $log[] = "第一个candidate类型: " . $candidate_class;
                            
                            // 反射获取content属性
                            $content_reflection = new ReflectionClass($first_candidate);
                            $content_prop = $content_reflection->getProperty('content');
                            $content_prop->setAccessible(true);
                            $content = $content_prop->getValue($first_candidate);
                            
                            if (is_object($content)) {
                                $content_class = get_class($content);
                                $log[] = "content对象类型: " . $content_class;
                                
                                // 反射获取parts属性
                                $parts_reflection = new ReflectionClass($content);
                                $parts_prop = $parts_reflection->getProperty('parts');
                                $parts_prop->setAccessible(true);
                                $parts = $parts_prop->getValue($content);
                                
                                if (is_object($parts)) {
                                    $parts_class = get_class($parts);
                                    $log[] = "parts对象类型: " . $parts_class;
                                    
                                    // 反射获取parts数组
                                    $parts_array_reflection = new ReflectionClass($parts);
                                    $parts_array_prop = $parts_array_reflection->getProperty('parts');
                                    $parts_array_prop->setAccessible(true);
                                    $parts_array = $parts_array_prop->getValue($parts);
                                    
                                    if (is_array($parts_array) && !empty($parts_array)) {
                                        $log[] = "parts数组长度: " . count($parts_array);
                                        $first_part = $parts_array[0];
                                        
                                        if (is_object($first_part)) {
                                            $part_class = get_class($first_part);
                                            $log[] = "第一个part类型: " . $part_class;
                                            
                                            // 反射获取data属性
                                            $data_reflection = new ReflectionClass($first_part);
                                            $data_prop = $data_reflection->getProperty('data');
                                            $data_prop->setAccessible(true);
                                            $data = $data_prop->getValue($first_part);
                                            
                                            if (is_array($data) && isset($data['text'])) {
                                                $log[] = "成功通过完整反射路径获取文本!";
                                                if ($debug) error_log(implode("\n", $log));
                                                return $data['text'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $log[] = "反射方法失败: " . $e->getMessage();
                }
            
                // 方法1: 使用get_candidates()方法
                if (method_exists($candidates, 'get_candidates')) {
                    $log[] = "尝试get_candidates()方法";
                    $candidates_array = $candidates->get_candidates();
                    
                    if (is_array($candidates_array) && !empty($candidates_array)) {
                        $log[] = "get_candidates返回了数组，长度: " . count($candidates_array);
                        $first = $candidates_array[0];
                        
                        if (is_object($first) && method_exists($first, 'get_content')) {
                            $log[] = "尝试获取content对象";
                            $content = $first->get_content();
                            
                            if (is_object($content) && method_exists($content, 'get_parts')) {
                                $log[] = "尝试获取parts对象";
                                $parts = $content->get_parts();
                                
                                if (is_object($parts) && method_exists($parts, 'get_parts')) {
                                    $log[] = "尝试获取parts数组";
                                    $parts_array = $parts->get_parts();
                                    
                                    if (is_array($parts_array) && !empty($parts_array)) {
                                        $log[] = "parts数组长度: " . count($parts_array);
                                        $first_part = $parts_array[0];
                                        
                                        // 如果part对象有get_text方法
                                        if (is_object($first_part) && method_exists($first_part, 'get_text')) {
                                            $log[] = "使用get_text()方法获取文本";
                                            $text = $first_part->get_text();
                                            if (is_string($text) && !empty($text)) {
                                                $log[] = "成功通过get_text()获取文本";
                                                if ($debug) error_log(implode("\n", $log));
                                                return $text;
                                            }
                                        }
                                        
                                        // 如果part对象有get_data方法
                                        if (is_object($first_part) && method_exists($first_part, 'get_data')) {
                                            $log[] = "使用get_data()方法获取数据";
                                            $data = $first_part->get_data();
                                            if (is_array($data) && isset($data['text'])) {
                                                $log[] = "成功从data数组中获取text字段";
                                                if ($debug) error_log(implode("\n", $log));
                                                return $data['text'];
                                            }
                                        }
                                        
                                        // 尝试使用反射直接访问私有属性
                                        try {
                                            $log[] = "尝试使用反射获取part对象的data属性";
                                            $reflection = new ReflectionClass($first_part);
                                            $data_prop = $reflection->getProperty('data');
                                            $data_prop->setAccessible(true);
                                            $data = $data_prop->getValue($first_part);
                                            
                                            if (is_array($data) && isset($data['text'])) {
                                                $log[] = "成功通过反射获取data['text']";
                                                if ($debug) error_log(implode("\n", $log));
                                                return $data['text'];
                                            }
                                        } catch (Exception $e) {
                                            $log[] = "反射获取data属性失败: " . $e->getMessage();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                // 方法2: 使用to_array()方法
                if (method_exists($candidates, 'to_array')) {
                    $log[] = "尝试to_array()方法";
                    $array_data = $candidates->to_array();
                    
                    if (is_array($array_data) && isset($array_data['candidates']) && !empty($array_data['candidates'])) {
                        $log[] = "to_array返回了有效数组";
                        $candidate = $array_data['candidates'][0];
                        
                        if (isset($candidate['content']) && isset($candidate['content']['parts'])) {
                            $log[] = "找到了content和parts结构";
                            
                            if (isset($candidate['content']['parts'][0]['text'])) {
                                $log[] = "成功从to_array()结果中提取文本";
                                if ($debug) error_log(implode("\n", $log));
                                return $candidate['content']['parts'][0]['text'];
                            }
                        }
                    }
                }
                
                // 方法3: 尝试直接JSON序列化并提取文本
                $log[] = "尝试JSON序列化并提取";
                try {
                    $json = json_encode($candidates);
                    if ($json !== false) {
                        $log[] = "序列化成功，尝试正则提取text字段";
                        // 尝试多个正则模式
                        $patterns = array(
                            '/"text"\s*:\s*"(.*?)(?<!\\\\)"(?:,|})/',  // 标准text字段
                            '/"parts"\s*:\s*\[\s*{\s*"text"\s*:\s*"(.*?)(?<!\\\\)"/', // parts.text字段
                            '/"text"\s*:\s*"(.*?)"/' // 宽松匹配
                        );
                        
                        foreach ($patterns as $pattern) {
                            if (preg_match($pattern, $json, $matches)) {
                                if (!empty($matches[1])) {
                                    $log[] = "成功通过JSON提取文本";
                                    $decoded = json_decode('"' . $matches[1] . '"');
                                    if ($debug) error_log(implode("\n", $log));
                                    return $decoded;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $log[] = "JSON序列化失败: " . $e->getMessage();
                }
            }
            
            // 一般对象处理（兜底）
            $log[] = "尝试一般对象处理方法";
            
            // 检查对象是否可以转为字符串
            if (method_exists($candidates, '__toString')) {
                $log[] = "对象可以转为字符串，使用__toString()";
                $text = (string)$candidates;
                if (!empty($text)) {
                    $log[] = "成功通过__toString()获取文本";
                    if ($debug) error_log(implode("\n", $log));
                    return $text;
                }
            }
            
            // 深度递归查找text属性
            $log[] = "尝试递归查找text字段";
            $recursive_log = array();
            $text = _recursive_find_text($candidates, $recursive_log);
            if (!empty($text)) {
                $log[] = "通过递归查找成功获取text: " . substr($text, 0, 50);
                if ($debug) error_log(implode("\n", $log));
                return $text;
            }
            
            $log = array_merge($log, $recursive_log);
        }
        
        // 处理数组
        if (is_array($candidates)) {
            $log[] = "处理数组类型输入";
            
            // 直接找text字段
            if (isset($candidates['text'])) {
                $log[] = "数组中存在text字段，直接返回";
                if ($debug) error_log(implode("\n", $log));
                return $candidates['text'];
            }
            
            // 如果是candidates数组
            if (isset($candidates['candidates']) && is_array($candidates['candidates']) && !empty($candidates['candidates'])) {
                $log[] = "发现candidates结构";
                $candidate = $candidates['candidates'][0];
                
                // 尝试从候选项中提取文本
                if (isset($candidate['content']) && isset($candidate['content']['parts'])) {
                    $log[] = "发现content和parts结构";
                    
                    if (isset($candidate['content']['parts'][0]['text'])) {
                        $log[] = "成功从candidates数组提取text";
                        if ($debug) error_log(implode("\n", $log));
                        return $candidate['content']['parts'][0]['text'];
                    }
                }
            }
            
            // 深度递归查找text属性
            $log[] = "尝试递归查找数组中的text字段";
            $recursive_log = array();
            $text = _recursive_find_text($candidates, $recursive_log);
            if (!empty($text)) {
                $log[] = "通过递归查找成功获取text: " . substr($text, 0, 50);
                if ($debug) error_log(implode("\n", $log));
                return $text;
            }
            
            $log = array_merge($log, $recursive_log);
        }
        
        // 最后实在没办法，使用print_r
        $log[] = "所有方法失败，使用print_r作为最后手段";
        $text = print_r($candidates, true);
        if (!empty($text)) {
            $log[] = "使用print_r获取文本，长度: " . strlen($text);
            if ($debug) error_log(implode("\n", $log));
            return $text;
        }
    } catch (Exception $e) {
        $log[] = "异常: " . $e->getMessage();
        if ($debug) error_log(implode("\n", $log));
    }
    
    $log[] = "所有方法均失败，返回false";
    if ($debug) error_log(implode("\n", $log));
    return false;
}

/**
 * 递归搜索数组中的文本字段
 * 
 * @param array $data 要搜索的数组
 * @param array &$log 日志记录数组
 * @return string|false 找到的文本或false
 */
function _recursive_find_text($data, &$log) {
    // 直接检查常见的文本字段名
    $text_keys = array('text', 'content', 'message', 'description', 'answer', 'response');
    
    if (is_array($data)) {
        // 优先检查已知的文本字段键
        foreach ($text_keys as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $log[] = "在键 '$key' 中找到文本";
                return $data[$key];
            }
        }
        
        // 递归处理数组的所有元素
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $result = _recursive_find_text($value, $log);
                if ($result !== false) {
                    return $result;
                }
            } elseif (is_string($value) && strlen($value) > 20) {
                // 如果是长字符串，可能是我们要找的文本
                $log[] = "在键 '$key' 中找到长字符串";
                return $value;
            }
        }
    } elseif (is_object($data)) {
        // 转换为数组后处理
        $array_data = (array)$data;
        return _recursive_find_text($array_data, $log);
    }
    
    return false;
}

// 在管理界面文章列表添加直接审核按钮
function add_ai_review_admin_action($actions, $post) {
    // 只对文章类型显示
    if ($post->post_type !== 'post') {
        return $actions;
    }
    
    // 已经有审核结果的不显示
    if (get_post_meta($post->ID, '_ai_review_score', true) !== '') {
        return $actions;
    }
    
    // 检查AI审核功能是否启用
    $settings = get_ai_review_settings();
    if (!$settings['enabled'] || !function_exists('ai_services')) {
        return $actions;
    }
    
    // 添加直接审核按钮
    $actions['ai_review'] = sprintf(
        '<a href="%s" class="ai-review">AI审核</a>',
        wp_nonce_url(admin_url('admin.php?action=trigger_ai_review&post_id=' . $post->ID), 'trigger_ai_review_' . $post->ID)
    );
    
    return $actions;
}
add_filter('post_row_actions', 'add_ai_review_admin_action', 10, 2);

// 处理管理界面的AI审核请求
function handle_admin_ai_review_request() {
    if (!isset($_GET['action']) || $_GET['action'] !== 'trigger_ai_review') {
        return;
    }
    
    // 权限检查
    if (!current_user_can('edit_posts')) {
        wp_die('您没有权限执行此操作');
    }
    
    // 获取文章ID
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    if (!$post_id) {
        wp_die('无效的文章ID');
    }
    
    // 验证nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'trigger_ai_review_' . $post_id)) {
        wp_die('安全验证失败');
    }
    
    // 获取文章
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'post') {
        wp_die('文章不存在或类型错误');
    }
    
    // 记录审核尝试
    error_log('管理界面触发AI审核: post_id=' . $post_id);
    
    // 检查AI Services插件
    if (!function_exists('ai_services')) {
        wp_redirect(add_query_arg('ai_review_error', 'plugin_missing', admin_url('edit.php')));
        exit;
    }
    
    // 检查AI服务
    if (!ai_services()->has_available_services()) {
        wp_redirect(add_query_arg('ai_review_error', 'service_missing', admin_url('edit.php')));
        exit;
    }
    
    // 执行审核
    $result = ai_review_post($post_id);
    
    if ($result) {
        // 成功
        error_log('管理界面审核成功: post_id=' . $post_id . ', score=' . $result['score']);
        wp_redirect(add_query_arg('ai_review_success', $post_id, admin_url('edit.php')));
    } else {
        // 失败
        error_log('管理界面审核失败: post_id=' . $post_id);
        wp_redirect(add_query_arg('ai_review_error', 'review_failed', admin_url('edit.php')));
    }
    exit;
}
add_action('admin_init', 'handle_admin_ai_review_request');

// 显示审核结果通知
function display_ai_review_admin_notice() {
    $screen = get_current_screen();
    
    // 只在文章列表页显示
    if ($screen->id !== 'edit-post') {
        return;
    }
    
    // 显示成功消息
    if (isset($_GET['ai_review_success'])) {
        $post_id = intval($_GET['ai_review_success']);
        $post = get_post($post_id);
        $score = get_post_meta($post_id, '_ai_review_score', true);
        $score_display = round($score * 10, 1);
        
        ?>
        <div class="notice notice-success is-dismissible">
            <p>AI审核完成！文章《<?php echo esc_html($post->post_title); ?>》的AI评分为 <?php echo $score_display; ?>/10。</p>
            <p><a href="<?php echo get_edit_post_link($post_id); ?>">查看文章</a></p>
        </div>
        <?php
    }
    
    // 显示错误消息
    if (isset($_GET['ai_review_error'])) {
        $error = $_GET['ai_review_error'];
        $message = '';
        
        switch ($error) {
            case 'plugin_missing':
                $message = 'AI Services插件未安装或激活，无法执行AI审核。';
                break;
            case 'service_missing':
                $message = 'AI Services插件未配置可用的AI服务，请先配置API密钥。';
                break;
            case 'review_failed':
                $message = 'AI审核失败，请查看错误日志或稍后重试。';
                break;
            default:
                $message = '执行AI审核时发生未知错误。';
        }
        
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
} 
add_action('admin_notices', 'display_ai_review_admin_notice'); 