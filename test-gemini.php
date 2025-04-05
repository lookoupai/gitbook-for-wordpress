<?php
// 测试Gemini API提取文本
if (!defined('ABSPATH')) {
    // 独立运行模式
    define('WP_DEBUG', true);
    require_once('../../../wp-load.php');
}

// 安全处理JSON显示，确保不会有类型错误
function safe_json_encode($obj) {
    // 确保对象可以被JSON编码，处理特殊类型
    if (is_object($obj) || is_array($obj)) {
        try {
            $json = json_encode($obj);
            if ($json === false) {
                return json_encode(array('error' => 'JSON编码失败: ' . json_last_error_msg()));
            }
            return $json;
        } catch (Exception $e) {
            return json_encode(array('error' => '对象无法编码为JSON: ' . $e->getMessage()));
        }
    } 
    return json_encode($obj);
}

// 安全输出HTML，确保处理非字符串内容
function safe_html_output($content) {
    if (is_string($content)) {
        return htmlspecialchars($content);
    } elseif (is_array($content) || is_object($content)) {
        return htmlspecialchars(print_r($content, true));
    } else {
        return htmlspecialchars((string)$content);
    }
}

// 允许设置测试选项
$test_type = isset($_GET['test']) ? $_GET['test'] : 'simple';
$debug_level = isset($_GET['debug']) ? (int)$_GET['debug'] : 1;
$prompt_text = isset($_GET['prompt']) ? $_GET['prompt'] : null;
$force_array = isset($_GET['force_array']) && $_GET['force_array'] == '1';

// 输出测试标题和选项
echo "<h1>Gemini API 高级测试工具</h1>";
echo "<div style='margin-bottom:20px;'>";
echo "<p>这个脚本用来测试Gemini API响应和文本提取功能。当前测试模式: <strong>{$test_type}</strong>，调试级别: <strong>{$debug_level}</strong>" . 
     ($force_array ? "，<span style='color:red'>强制数组模式</span>" : "") . "</p>";

// 显示测试选项
echo "<div style='margin: 15px 0; padding: 10px; background-color: #f5f5f5; border-radius: 5px;'>";
echo "<h3>测试选项</h3>";
echo "<a href='?test=simple' class='btn'>简单测试</a> | ";
echo "<a href='?test=review' class='btn'>审核测试</a> | ";
echo "<a href='?test=revision' class='btn'>修订测试</a> | ";
echo "<a href='?test=custom&debug=2' class='btn'>自定义测试</a> | ";
echo "<a href='?test={$test_type}&debug={$debug_level}&force_array=1' class='btn' style='color:red'>测试数组处理</a>";
echo "<p>调试级别: ";
echo "<a href='?test={$test_type}&debug=1" . ($force_array ? "&force_array=1" : "") . "'>基本</a> | ";
echo "<a href='?test={$test_type}&debug=2" . ($force_array ? "&force_array=1" : "") . "'>详细</a> | ";
echo "<a href='?test={$test_type}&debug=3" . ($force_array ? "&force_array=1" : "") . "'>全部</a>";
echo "</p>";
echo "</div>";

// 自定义提示输入框
if ($test_type == 'custom') {
    echo "<div style='margin: 15px 0; padding: 10px; background-color: #e6f7ff; border-radius: 5px;'>";
    echo "<h3>自定义提示</h3>";
    echo "<form method='get'>";
    echo "<input type='hidden' name='test' value='custom'>";
    echo "<input type='hidden' name='debug' value='{$debug_level}'>";
    echo "<textarea name='prompt' style='width:100%; height:100px;'>" . ($prompt_text ?: "请简要评估以下内容质量，并给出1-10分的评分。") . "</textarea>";
    echo "<button type='submit' style='margin-top:10px;'>测试</button>";
    echo "</form>";
    echo "</div>";
}
echo "</div>";

// 1. 检查AI Services插件是否存在
echo "<h2>1. 检查插件状态</h2>";
if (!function_exists('ai_services')) {
    echo "<p style='color:red'>错误: AI Services插件未安装或未激活</p>";
    exit;
} else {
    echo "<p style='color:green'>AI Services插件已安装并激活</p>";
}

// 2. 检查是否有可用服务
echo "<h2>2. 检查可用服务</h2>";
try {
    $ai_services = ai_services();
    if (!$ai_services) {
        echo "<p style='color:red'>错误: ai_services()返回null</p>";
        exit;
    }
    
    if (!method_exists($ai_services, 'has_available_services')) {
        echo "<p style='color:red'>错误: ai_services对象没有has_available_services方法</p>";
        exit;
    }
    
    $has_services = $ai_services->has_available_services();
    if (!$has_services) {
        echo "<p style='color:red'>错误: 没有可用的AI服务</p>";
        exit;
    }
    
    echo "<p style='color:green'>有可用的AI服务</p>";
    
    // 尝试获取服务信息
    $service = $ai_services->get_available_service();
    if (!$service) {
        echo "<p style='color:red'>错误: 无法获取可用服务</p>";
        exit;
    }
    
    echo "<p>服务信息:";
    if (method_exists($service, 'get_name')) {
        echo " 名称: " . $service->get_name();
    }
    if (method_exists($service, 'get_label')) {
        echo ", 标签: " . $service->get_label();
    }
    echo "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
    exit;
}

// 3. 尝试获取模型
echo "<h2>3. 获取文本生成模型</h2>";
try {
    // 使用参数获取文本生成模型
    $text_generation_capability = 'TEXT_GENERATION';
    if (class_exists('Felix_Arntz\\AI_Services\\Services\\API\\Enums\\AI_Capability')) {
        $text_generation_capability = \Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability::TEXT_GENERATION;
    }
    
    $model = $service->get_model(array(
        'feature' => 'api-test',
        'capabilities' => array($text_generation_capability),
    ));
    
    if (!$model) {
        echo "<p style='color:red'>错误: 无法获取文本生成模型</p>";
        exit;
    }
    
    echo "<p style='color:green'>成功获取文本生成模型</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
    exit;
}

// 4. 测试文本生成
echo "<h2>4. 测试文本生成</h2>";
try {
    // 根据测试类型设置提示语
    if ($prompt_text) {
        $prompt = $prompt_text;
    } else {
        switch ($test_type) {
            case 'review':
                $prompt = "请简要评估以下文章质量，并给出1-10分的评分。\n\n文章内容：\n这是一篇测试文章，用于测试AI审核功能。请给出评分和简短评价。";
                break;
            case 'revision':
                $prompt = "请简要评估以下文章修改的质量，并给出1-10分的评分。\n\n原文：\n这是一篇测试文章，有一些错误。\n\n修改后：\n这是一篇测试文章，错误已经修正。";
                break;
            case 'custom':
                $prompt = $prompt_text ?: "请简要评估以下内容质量，并给出1-10分的评分。";
                break;
            default: // simple
                $prompt = "请用一句话回答：今天天气怎么样？";
        }
    }
    
    echo "<p>提示语: " . nl2br(safe_html_output($prompt)) . "</p>";
    
    $candidates = $model->generate_text($prompt);
    
    echo "<p style='color:green'>成功生成文本</p>";
    echo "<h3>API响应类型: " . gettype($candidates) . "</h3>";
    if (is_object($candidates)) {
        echo "<p>类名: " . get_class($candidates) . "</p>";
    }
    
    // 如果调试级别>=2，记录对象的方法
    if ($debug_level >= 2 && is_object($candidates)) {
        echo "<h4>对象可用方法:</h4>";
        echo "<ul>";
        $methods = get_class_methods($candidates);
        if (!empty($methods)) {
            foreach ($methods as $method) {
                echo "<li>" . $method . "</li>";
            }
        } else {
            echo "<li>未找到公共方法</li>";
        }
        echo "</ul>";
        
        // 添加更多的Candidates对象调试
        if (is_object($candidates) && get_class($candidates) === 'Felix_Arntz\AI_Services\Services\API\Types\Candidates') {
            echo "<h4>Candidates对象调试:</h4>";
            
            try {
                // 记录完整的方法列表
                $class_methods = get_class_methods($candidates);
                echo "<p>所有可用方法: " . implode(", ", $class_methods) . "</p>";
                
                // 测试get_candidates方法
                if (method_exists($candidates, 'get_candidates')) {
                    try {
                        $candidates_array = $candidates->get_candidates();
                        echo "<p>get_candidates()返回: " . gettype($candidates_array) . ", 数量: " . (is_array($candidates_array) ? count($candidates_array) : 'N/A') . "</p>";
                        
                        if (is_array($candidates_array) && !empty($candidates_array)) {
                            $first = $candidates_array[0];
                            echo "<p>第一个元素类型: " . gettype($first) . "</p>";
                            
                            if (is_object($first)) {
                                echo "<p>类名: " . get_class($first) . "</p>";
                                echo "<p>方法: " . implode(", ", get_class_methods($first)) . "</p>";
                                
                                if (method_exists($first, 'get_content')) {
                                    $content = $first->get_content();
                                    echo "<p>get_content()返回: " . gettype($content) . "</p>";
                                    
                                    if (is_object($content)) {
                                        echo "<p>Content类名: " . get_class($content) . "</p>";
                                        echo "<p>Content方法: " . implode(", ", get_class_methods($content)) . "</p>";
                                        
                                        if (method_exists($content, 'get_parts')) {
                                            $parts = $content->get_parts();
                                            echo "<p>get_parts()返回: " . gettype($parts) . "</p>";
                                            
                                            if (is_object($parts)) {
                                                echo "<p>Parts类名: " . get_class($parts) . "</p>";
                                                echo "<p>Parts方法: " . implode(", ", get_class_methods($parts)) . "</p>";
                                                
                                                if (method_exists($parts, 'get_parts')) {
                                                    $parts_array = $parts->get_parts();
                                                    echo "<p>parts->get_parts()返回: " . gettype($parts_array) . ", 数量: " . (is_array($parts_array) ? count($parts_array) : 'N/A') . "</p>";
                                                    
                                                    if (is_array($parts_array) && !empty($parts_array)) {
                                                        $first_part = $parts_array[0];
                                                        echo "<p>第一个part类型: " . gettype($first_part) . "</p>";
                                                        
                                                        if (is_object($first_part)) {
                                                            echo "<p>类名: " . get_class($first_part) . "</p>";
                                                            echo "<p>方法: " . implode(", ", get_class_methods($first_part)) . "</p>";
                                                            
                                                            // 特殊测试：使用反射直接访问数据
                                                            try {
                                                                $reflection = new ReflectionClass($first_part);
                                                                $data_prop = $reflection->getProperty('data');
                                                                $data_prop->setAccessible(true);
                                                                $data = $data_prop->getValue($first_part);
                                                                
                                                                echo "<p>通过反射获取data属性: " . gettype($data) . "</p>";
                                                                if (is_array($data)) {
                                                                    echo "<p>数组键: " . implode(", ", array_keys($data)) . "</p>";
                                                                    
                                                                    if (isset($data['text'])) {
                                                                        echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                                        echo "<h5>找到text字段内容:</h5>";
                                                                        echo nl2br(safe_html_output(substr($data['text'], 0, 500)));
                                                                        if (strlen($data['text']) > 500) echo "...(截断)";
                                                                        echo "</div>";
                                                                        
                                                                        // 单独使用这个提取的文本测试评分提取
                                                                        if (function_exists('extract_score_from_response')) {
                                                                            $direct_score = extract_score_from_response($data['text']);
                                                                            echo "<p>直接对text字段提取评分: " . round($direct_score * 10, 1) . "/10</p>";
                                                                        }
                                                                    }
                                                                }
                                                            } catch (Exception $e) {
                                                                echo "<p style='color:red'>反射获取data属性失败: " . safe_html_output($e->getMessage()) . "</p>";
                                                            }
                                                            
                                                            if (method_exists($first_part, 'get_text')) {
                                                                $text = $first_part->get_text();
                                                                echo "<p>get_text()返回: " . gettype($text) . "</p>";
                                                                echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                                echo "<h5>提取的文本内容:</h5>";
                                                                echo nl2br(safe_html_output(substr($text, 0, 500)));
                                                                if (strlen($text) > 500) echo "...(截断)";
                                                                echo "</div>";
                                                            } else {
                                                                echo "<p>注意: get_text()方法不存在</p>";
                                                            }
                                                            
                                                            if (method_exists($first_part, 'get_data')) {
                                                                $data = $first_part->get_data();
                                                                echo "<p>get_data()返回: " . gettype($data) . "</p>";
                                                                if (is_array($data)) {
                                                                    echo "<p>数组键: " . implode(", ", array_keys($data)) . "</p>";
                                                                    if (isset($data['text'])) {
                                                                        echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                                        echo "<h5>data['text']内容:</h5>";
                                                                        echo nl2br(safe_html_output(substr($data['text'], 0, 500)));
                                                                        if (strlen($data['text']) > 500) echo "...(截断)";
                                                                        echo "</div>";
                                                                    }
                                                                }
                                                            } else {
                                                                echo "<p>注意: get_data()方法不存在</p>";
                                                            }
                                                            
                                                            // 尝试to_array()方法
                                                            if (method_exists($first_part, 'to_array')) {
                                                                $array_data = $first_part->to_array();
                                                                echo "<p>to_array()返回: " . gettype($array_data) . "</p>";
                                                                if (is_array($array_data)) {
                                                                    echo "<p>数组键: " . implode(", ", array_keys($array_data)) . "</p>";
                                                                    if (isset($array_data['text'])) {
                                                                        echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                                        echo "<h5>array_data['text']内容:</h5>";
                                                                        echo nl2br(safe_html_output(substr($array_data['text'], 0, 500)));
                                                                        if (strlen($array_data['text']) > 500) echo "...(截断)";
                                                                        echo "</div>";
                                                                    }
                                                                }
                                                            } else {
                                                                echo "<p>注意: to_array()方法不存在</p>";
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        echo "<p style='color:red'>获取Candidates内部结构出错: " . safe_html_output($e->getMessage()) . "</p>";
                    }
                }
                
                // 尝试直接使用to_array方法提取整个结构
                if (method_exists($candidates, 'to_array')) {
                    try {
                        echo "<h4>测试完整to_array()转换:</h4>";
                        $full_array = $candidates->to_array();
                        echo "<p>完整to_array()返回类型: " . gettype($full_array) . "</p>";
                        
                        if (is_array($full_array) && !empty($full_array)) {
                            echo "<p>顶层键: " . implode(", ", array_keys($full_array)) . "</p>";
                            
                            if (isset($full_array['candidates']) && is_array($full_array['candidates']) && !empty($full_array['candidates'])) {
                                echo "<p>candidates数组长度: " . count($full_array['candidates']) . "</p>";
                                
                                if (isset($full_array['candidates'][0])) {
                                    $candidate = $full_array['candidates'][0];
                                    echo "<p>第一个candidate键: " . implode(", ", array_keys($candidate)) . "</p>";
                                    
                                    if (isset($candidate['content'])) {
                                        echo "<p>content键: " . implode(", ", array_keys($candidate['content'])) . "</p>";
                                        
                                        if (isset($candidate['content']['parts'])) {
                                            echo "<p>parts键: " . implode(", ", array_keys($candidate['content']['parts'])) . "</p>";
                                            
                                            if (isset($candidate['content']['parts'][0]['text'])) {
                                                echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                echo "<h5>完整to_array提取到的文本:</h5>";
                                                echo nl2br(safe_html_output(substr($candidate['content']['parts'][0]['text'], 0, 500)));
                                                if (strlen($candidate['content']['parts'][0]['text']) > 500) echo "...(截断)";
                                                echo "</div>";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        echo "<p style='color:red'>完整to_array()失败: " . safe_html_output($e->getMessage()) . "</p>";
                    }
                }
            } catch (Exception $e) {
                echo "<p style='color:red'>对象调试过程中出错: " . safe_html_output($e->getMessage()) . "</p>";
            }
        }
    }
    
    // 保存原始响应
    $raw_response = print_r($candidates, true);
    echo "<h3>原始响应:</h3>";
    echo "<pre style='background-color:#f5f5f5;padding:10px;max-height:300px;overflow:auto'>";
    echo safe_html_output(substr($raw_response, 0, 5000));
    if (strlen($raw_response) > 5000) {
        echo "...(截断)";
    }
    echo "</pre>";
    
    // 5. 测试提取文本函数
    echo "<h2>5. 测试extract_text_from_candidates函数</h2>";
    
    if (!function_exists('extract_text_from_candidates')) {
        echo "<p style='color:red'>错误: extract_text_from_candidates函数不存在</p>";
    } else {
        // 根据调试级别设置debug参数
        $debug_extraction = $debug_level >= 2;
        
        // 添加更多的Candidates对象调试
        if (is_object($candidates) && get_class($candidates) === 'Felix_Arntz\AI_Services\Services\API\Types\Candidates') {
            echo "<h4>Candidates对象调试:</h4>";
            
            try {
                // 记录完整的方法列表
                $class_methods = get_class_methods($candidates);
                echo "<p>所有可用方法: " . implode(", ", $class_methods) . "</p>";
                
                // 测试get_candidates方法
                if (method_exists($candidates, 'get_candidates')) {
                    try {
                        $candidates_array = $candidates->get_candidates();
                        echo "<p>get_candidates()返回: " . gettype($candidates_array) . ", 数量: " . (is_array($candidates_array) ? count($candidates_array) : 'N/A') . "</p>";
                        
                        if (is_array($candidates_array) && !empty($candidates_array)) {
                            $first = $candidates_array[0];
                            echo "<p>第一个元素类型: " . gettype($first) . "</p>";
                            
                            if (is_object($first)) {
                                echo "<p>类名: " . get_class($first) . "</p>";
                                echo "<p>方法: " . implode(", ", get_class_methods($first)) . "</p>";
                                
                                if (method_exists($first, 'get_content')) {
                                    $content = $first->get_content();
                                    echo "<p>get_content()返回: " . gettype($content) . "</p>";
                                    
                                    if (is_object($content)) {
                                        echo "<p>Content类名: " . get_class($content) . "</p>";
                                        echo "<p>Content方法: " . implode(", ", get_class_methods($content)) . "</p>";
                                        
                                        if (method_exists($content, 'get_parts')) {
                                            $parts = $content->get_parts();
                                            echo "<p>get_parts()返回: " . gettype($parts) . "</p>";
                                            
                                            if (is_object($parts)) {
                                                echo "<p>Parts类名: " . get_class($parts) . "</p>";
                                                echo "<p>Parts方法: " . implode(", ", get_class_methods($parts)) . "</p>";
                                                
                                                if (method_exists($parts, 'get_parts')) {
                                                    $parts_array = $parts->get_parts();
                                                    echo "<p>parts->get_parts()返回: " . gettype($parts_array) . ", 数量: " . (is_array($parts_array) ? count($parts_array) : 'N/A') . "</p>";
                                                    
                                                    if (is_array($parts_array) && !empty($parts_array)) {
                                                        $first_part = $parts_array[0];
                                                        echo "<p>第一个part类型: " . gettype($first_part) . "</p>";
                                                        
                                                        if (is_object($first_part)) {
                                                            echo "<p>类名: " . get_class($first_part) . "</p>";
                                                            echo "<p>方法: " . implode(", ", get_class_methods($first_part)) . "</p>";
                                                            
                                                            // 特殊测试：使用反射直接访问数据
                                                            try {
                                                                $reflection = new ReflectionClass($first_part);
                                                                $data_prop = $reflection->getProperty('data');
                                                                $data_prop->setAccessible(true);
                                                                $data = $data_prop->getValue($first_part);
                                                                
                                                                echo "<p>通过反射获取data属性: " . gettype($data) . "</p>";
                                                                if (is_array($data)) {
                                                                    echo "<p>数组键: " . implode(", ", array_keys($data)) . "</p>";
                                                                    
                                                                    if (isset($data['text'])) {
                                                                        echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                                        echo "<h5>找到text字段内容:</h5>";
                                                                        echo nl2br(safe_html_output(substr($data['text'], 0, 500)));
                                                                        if (strlen($data['text']) > 500) echo "...(截断)";
                                                                        echo "</div>";
                                                                        
                                                                        // 单独使用这个提取的文本测试评分提取
                                                                        if (function_exists('extract_score_from_response')) {
                                                                            $direct_score = extract_score_from_response($data['text']);
                                                                            echo "<p>直接对text字段提取评分: " . round($direct_score * 10, 1) . "/10</p>";
                                                                        }
                                                                    }
                                                                }
                                                            } catch (Exception $e) {
                                                                echo "<p style='color:red'>反射获取data属性失败: " . safe_html_output($e->getMessage()) . "</p>";
                                                            }
                                                            
                                                            if (method_exists($first_part, 'get_text')) {
                                                                $text = $first_part->get_text();
                                                                echo "<p>get_text()返回: " . gettype($text) . "</p>";
                                                                echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                                echo "<h5>提取的文本内容:</h5>";
                                                                echo nl2br(safe_html_output(substr($text, 0, 500)));
                                                                if (strlen($text) > 500) echo "...(截断)";
                                                                echo "</div>";
                                                            } else {
                                                                echo "<p>注意: get_text()方法不存在</p>";
                                                            }
                                                            
                                                            if (method_exists($first_part, 'get_data')) {
                                                                $data = $first_part->get_data();
                                                                echo "<p>get_data()返回: " . gettype($data) . "</p>";
                                                                if (is_array($data)) {
                                                                    echo "<p>数组键: " . implode(", ", array_keys($data)) . "</p>";
                                                                    if (isset($data['text'])) {
                                                                        echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                                        echo "<h5>data['text']内容:</h5>";
                                                                        echo nl2br(safe_html_output(substr($data['text'], 0, 500)));
                                                                        if (strlen($data['text']) > 500) echo "...(截断)";
                                                                        echo "</div>";
                                                                    }
                                                                }
                                                            } else {
                                                                echo "<p>注意: get_data()方法不存在</p>";
                                                            }
                                                            
                                                            // 尝试to_array()方法
                                                            if (method_exists($first_part, 'to_array')) {
                                                                $array_data = $first_part->to_array();
                                                                echo "<p>to_array()返回: " . gettype($array_data) . "</p>";
                                                                if (is_array($array_data)) {
                                                                    echo "<p>数组键: " . implode(", ", array_keys($array_data)) . "</p>";
                                                                    if (isset($array_data['text'])) {
                                                                        echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                                                        echo "<h5>array_data['text']内容:</h5>";
                                                                        echo nl2br(safe_html_output(substr($array_data['text'], 0, 500)));
                                                                        if (strlen($array_data['text']) > 500) echo "...(截断)";
                                                                        echo "</div>";
                                                                    }
                                                                }
                                                            } else {
                                                                echo "<p>注意: to_array()方法不存在</p>";
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        echo "<p style='color:red'>获取Candidates内部结构出错: " . safe_html_output($e->getMessage()) . "</p>";
                    }
                }
            } catch (Exception $e) {
                echo "<p style='color:red'>对象调试过程中出错: " . safe_html_output($e->getMessage()) . "</p>";
            }
        }
        
        // 添加日志监听
        if ($debug_level >= 3) {
            echo "<h4>提取过程调试日志:</h4>";
            echo "<pre id='debug_log' style='background-color:#e6ffe6;padding:10px;max-height:200px;overflow:auto'>";
            echo "等待日志记录...\n";
            echo "</pre>";
            
            // 使用JavaScript捕获error_log输出
            echo "<script>
                function updateLog() {
                    fetch('?action=get_logs&_=' + Date.now())
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('debug_log').innerText = data;
                            document.getElementById('debug_log').scrollTop = document.getElementById('debug_log').scrollHeight;
                        });
                }
                setInterval(updateLog, 1000);
            </script>";
        }
        
        // 强制数组测试模式
        if ($force_array) {
            echo "<p style='color:orange'><strong>强制数组测试模式：</strong> 将候选项包装在数组中测试提取函数的健壮性</p>";
            
            // 创建一个模拟的嵌套数组结构
            $test_array = array(
                0 => array(
                    'content' => array(
                        'parts' => array(
                            array('text' => '这是一个测试文本，用于验证提取函数的健壮性，评分为7分。')
                        )
                    )
                )
            );
            
            if (is_object($candidates) && method_exists($candidates, 'to_array')) {
                try {
                    $candidates_array = $candidates->to_array();
                    echo "<p>使用to_array()获取的候选项数组</p>";
                    $extracted_text = extract_text_from_candidates($candidates_array, $debug_extraction);
                } catch (Exception $e) {
                    echo "<p>to_array()方法失败，使用模拟数据: " . $e->getMessage() . "</p>";
                    $extracted_text = extract_text_from_candidates($test_array, $debug_extraction);
                }
            } else {
                echo "<p>使用模拟的嵌套数组结构进行测试</p>";
                $extracted_text = extract_text_from_candidates($test_array, $debug_extraction);
            }
        } else {
            // 正常测试模式
            $extracted_text = extract_text_from_candidates($candidates, $debug_extraction);
        }
        
        if ($extracted_text === false) {
            echo "<p style='color:red'>错误: 无法提取文本</p>";
        } else {
            echo "<p style='color:green'>成功提取文本:</p>";
            echo "<div style='background-color:#e6ffe6;padding:10px;'>";
            echo nl2br(safe_html_output($extracted_text));
            echo "</div>";
            
            // 提取评分
            if (function_exists('extract_score_from_response')) {
                $score = extract_score_from_response($extracted_text);
                echo "<p>提取的评分: " . round($score * 10, 1) . "/10</p>";
                
                // 如果在强制数组模式，也测试评分函数的数组处理能力
                if ($force_array) {
                    echo "<h4>测试评分函数处理数组的能力:</h4>";
                    $array_input = array('text' => '这篇文章质量不错，评分为8分');
                    $array_score = extract_score_from_response($array_input);
                    echo "<p>对评分函数传入数组，结果: " . round($array_score * 10, 1) . "/10</p>";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
}

// 6. 测试JSON转换
echo "<h2>6. 测试JSON序列化</h2>";
try {
    $json = safe_json_encode($candidates);
    if (empty($json)) {
        echo "<p style='color:red'>错误: 无法序列化为JSON</p>";
    } else {
        echo "<p style='color:green'>成功序列化为JSON</p>";
        
        // 尝试不同的正则表达式
        $patterns = array(
            '/"text"\s*:\s*"(.*?)(?<!\\\\)"(?:,|})/' => '标准text字段',
            '/"content"\s*:\s*"(.*?)(?<!\\\\)"(?:,|})/' => 'content字段',
            '/"message"\s*:\s*"(.*?)(?<!\\\\)"(?:,|})/' => 'message字段',
            '/"parts"\s*:\s*\[\s*{\s*"text"\s*:\s*"(.*?)(?<!\\\\)"/' => 'parts.text字段',
            '/"text"\s*:\s*"(.*?)"/' => '宽松text字段'
        );
        
        foreach ($patterns as $pattern => $desc) {
            echo "<h4>尝试 $desc 模式:</h4>";
            if (preg_match($pattern, $json, $matches)) {
                echo "<p style='color:green'>匹配成功!</p>";
                if (!empty($matches[1])) {
                    $decoded = json_decode('"' . $matches[1] . '"');
                    echo "<pre style='background-color:#f5f5f5;padding:10px;'>";
                    echo safe_html_output(substr($decoded, 0, 1000));
                    if (strlen($decoded) > 1000) {
                        echo "...(截断)";
                    }
                    echo "</pre>";
                }
            } else {
                echo "<p style='color:orange'>没有匹配</p>";
            }
        }
        
        // 显示更多JSON内容（如果调试级别高）
        if ($debug_level >= 2) {
            echo "<h4>完整JSON:</h4>";
            echo "<pre style='background-color:#f5f5f5;padding:10px;max-height:300px;overflow:auto'>";
            echo safe_html_output(substr($json, 0, 10000));
            if (strlen($json) > 10000) {
                echo "...(截断)";
            }
            echo "</pre>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>错误: " . safe_html_output($e->getMessage()) . "</p>";
}

// 7. 反射测试
if ($debug_level >= 2) {
    echo "<h2>7. 反射分析</h2>";
    try {
        if (is_object($candidates)) {
            $reflector = new ReflectionClass($candidates);
            
            // 显示属性
            echo "<h4>类属性:</h4>";
            $properties = $reflector->getProperties();
            if (!empty($properties)) {
                echo "<ul>";
                foreach ($properties as $property) {
                    $access = '';
                    if ($property->isPublic()) $access = 'public';
                    elseif ($property->isProtected()) $access = 'protected';
                    elseif ($property->isPrivate()) $access = 'private';
                    
                    echo "<li>{$access} \${$property->getName()}</li>";
                }
                echo "</ul>";
                
                // 尝试访问私有属性
                echo "<h4>尝试访问私有属性:</h4>";
                foreach ($properties as $property) {
                    if ($property->isPrivate()) {
                        try {
                            $property->setAccessible(true);
                            $value = $property->getValue($candidates);
                            echo "<p>属性 {$property->getName()} 类型: " . gettype($value) . "</p>";
                            
                            if (is_array($value)) {
                                echo "<p>数组包含 " . count($value) . " 个元素</p>";
                                if (!empty($value) && $debug_level >= 3) {
                                    echo "<pre style='background-color:#f5f5f5;padding:10px;max-height:150px;overflow:auto'>";
                                    echo safe_html_output(print_r($value, true));
                                    echo "</pre>";
                                }
                            }
                        } catch (Exception $e) {
                            echo "<p>无法访问 {$property->getName()}: " . safe_html_output($e->getMessage()) . "</p>";
                        }
                    }
                }
            } else {
                echo "<p>未找到属性</p>";
            }
        } else {
            echo "<p>response不是对象，无法进行反射分析</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>反射分析错误: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>测试完成</h2>";

// 添加一个完整的提取测试
echo "<div style='margin:20px 0; padding:15px; background-color:#f5f5f5; border-radius:5px; border:1px solid #ccc;'>";
echo "<h3>完整提取测试</h3>";

// 直接测试从candidates提取文本并获取评分
if (is_object($candidates) && class_exists('ReflectionClass')) {
    echo "<h4>直接反射提取测试:</h4>";
    try {
        // 1. 使用反射直接获取text数据
        $reflection = new ReflectionClass($candidates);
        $prop = $reflection->getProperty('candidates');
        $prop->setAccessible(true);
        $candidates_array = $prop->getValue($candidates);
        
        if (is_array($candidates_array) && !empty($candidates_array)) {
            $first_candidate = $candidates_array[0];
            
            if (is_object($first_candidate)) {
                $content_reflection = new ReflectionClass($first_candidate);
                $content_prop = $content_reflection->getProperty('content');
                $content_prop->setAccessible(true);
                $content = $content_prop->getValue($first_candidate);
                
                if (is_object($content)) {
                    $parts_reflection = new ReflectionClass($content);
                    $parts_prop = $parts_reflection->getProperty('parts');
                    $parts_prop->setAccessible(true);
                    $parts = $parts_prop->getValue($content);
                    
                    if (is_object($parts)) {
                        $parts_array_reflection = new ReflectionClass($parts);
                        $parts_array_prop = $parts_array_reflection->getProperty('parts');
                        $parts_array_prop->setAccessible(true);
                        $parts_array = $parts_array_prop->getValue($parts);
                        
                        if (is_array($parts_array) && !empty($parts_array)) {
                            $first_part = $parts_array[0];
                            
                            if (is_object($first_part)) {
                                $data_reflection = new ReflectionClass($first_part);
                                $data_prop = $data_reflection->getProperty('data');
                                $data_prop->setAccessible(true);
                                $data = $data_prop->getValue($first_part);
                                
                                if (is_array($data) && isset($data['text'])) {
                                    echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                                    echo "<h5>直接通过反射提取的文本:</h5>";
                                    echo nl2br(safe_html_output(substr($data['text'], 0, 500)));
                                    if (strlen($data['text']) > 500) echo "...(截断)";
                                    echo "</div>";
                                    
                                    // 单独提取评分
                                    if (function_exists('extract_score_from_response')) {
                                        $reflection_score = extract_score_from_response($data['text'], true);
                                        echo "<p>直接使用反射数据评分: " . round($reflection_score * 10, 1) . "/10</p>";
                                    }
                                } else {
                                    echo "<p>未找到data['text']字段</p>";
                                }
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>反射提取失败: " . safe_html_output($e->getMessage()) . "</p>";
    }
}

// 使用to_array()测试
if (is_object($candidates) && method_exists($candidates, 'to_array')) {
    echo "<h4>to_array()方法提取测试:</h4>";
    try {
        $array_data = $candidates->to_array();
        if (is_array($array_data) && isset($array_data['candidates']) && !empty($array_data['candidates'])) {
            $candidate = $array_data['candidates'][0];
            
            if (isset($candidate['content']) && isset($candidate['content']['parts'])) {
                if (isset($candidate['content']['parts'][0]['text'])) {
                    $text = $candidate['content']['parts'][0]['text'];
                    echo "<div style='background-color:#e6ffe6;padding:10px;'>";
                    echo "<h5>使用to_array提取的文本:</h5>";
                    echo nl2br(safe_html_output(substr($text, 0, 500)));
                    if (strlen($text) > 500) echo "...(截断)";
                    echo "</div>";
                    
                    // 单独提取评分
                    if (function_exists('extract_score_from_response')) {
                        $array_score = extract_score_from_response($text, true);
                        echo "<p>使用to_array提取的评分: " . round($array_score * 10, 1) . "/10</p>";
                    }
                } else {
                    echo "<p>未找到content.parts[0].text字段</p>";
                }
            } else {
                echo "<p>未找到正确的content和parts结构</p>";
            }
        } else {
            echo "<p>to_array()返回的数据结构不符合预期</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>to_array()方法失败: " . safe_html_output($e->getMessage()) . "</p>";
    }
}

echo "</div>";

// 添加测试AI审核按钮
echo "<div style='margin:20px 0; padding:15px; background-color:#f0f8ff; border-radius:5px; border:1px solid #ccc;'>";
echo "<h3>测试AI审核功能</h3>";
echo "<form method='post' action='" . admin_url('admin-ajax.php') . "'>";
echo "<input type='hidden' name='action' value='trigger_manual_ai_review'>";
echo "<input type='hidden' name='security' value='" . wp_create_nonce('ai-review-nonce') . "'>";

// 测试文章选择
$posts = get_posts(array(
    'post_type' => 'post',
    'post_status' => array('pending', 'draft', 'publish'),
    'posts_per_page' => 10,
    'orderby' => 'modified',
    'order' => 'DESC'
));

if (!empty($posts)) {
    echo "<div class='form-group' style='margin-bottom:15px;'>";
    echo "<label for='post_id'>选择要测试的文章:</label>";
    echo "<select name='post_id' id='post_id' style='width:100%; padding:5px; margin-top:5px;'>";
    foreach ($posts as $test_post) {
        echo "<option value='" . $test_post->ID . "'>" . $test_post->post_title . " (ID: " . $test_post->ID . ", 状态: " . $test_post->post_status . ")</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin-top:15px;'>";
    echo "<button type='submit' style='padding:8px 15px; background:#4CAF50; color:white; border:none; border-radius:3px; cursor:pointer;'>触发AI审核</button>";
    echo "</div>";
} else {
    echo "<p>没有可测试的文章</p>";
}

echo "</form>";
echo "</div>";

echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
    h1, h2, h3, h4 { color: #333; }
    h2 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 30px; }
    pre { overflow-x: auto; }
    .btn { display: inline-block; padding: 5px 10px; background: #f0f0f0; text-decoration: none; color: #333; border-radius: 3px; }
    .btn:hover { background: #e0e0e0; }
</style>";

// 处理日志获取请求（用于AJAX）
if (isset($_GET['action']) && $_GET['action'] === 'get_logs') {
    // 这里只是一个示例，实际上需要服务器上的日志文件访问权限
    // 在真实场景中，可能需要读取错误日志文件
    echo "这里将显示实时日志...";
    exit;
}
?> 