jQuery(document).ready(function($) {
    // 清理指定类型的缓存
    function clearCache(type, button, status) {
        button.prop('disabled', true);
        status.html('<span style="color:#666;">正在清理缓存...</span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_article_tabs_cache',
                type: type,
                nonce: articleTabsSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    status.html('<span style="color:green;">✓ ' + response.data + '</span>');
                } else {
                    status.html('<span style="color:red;">✗ ' + response.data + '</span>');
                }
            },
            error: function() {
                status.html('<span style="color:red;">✗ 清理失败，请重试</span>');
            },
            complete: function() {
                button.prop('disabled', false);
                setTimeout(function() {
                    status.html('');
                }, 3000);
            }
        });
    }
    
    // 为每个清理按钮绑定事件
    $('#clear-latest-cache').on('click', function() {
        clearCache('latest', $(this), $('#clear-latest-status'));
    });
    
    $('#clear-updated-cache').on('click', function() {
        clearCache('updated', $(this), $('#clear-updated-status'));
    });
    
    $('#clear-popular-cache').on('click', function() {
        clearCache('popular', $(this), $('#clear-popular-status'));
    });
    
    $('#clear-rss-cache').on('click', function() {
        clearCache('rss', $(this), $('#clear-rss-status'));
    });
    
    $('#clear-all-cache').on('click', function() {
        clearCache('all', $(this), $('#clear-all-status'));
    });
    
    // 修改分类过滤选项切换的处理
    $('input[name$="[category_filter]"]').each(function() {
        // 页面加载时初始化显示状态
        var wrapper = $(this).closest('.tab-category-settings').find('.category-select-wrapper');
        if ($(this).is(':checked')) {
            if ($(this).val() === 'none') {
                wrapper.hide();
            } else {
                wrapper.show();
            }
        }
    }).on('change', function() {
        var wrapper = $(this).closest('.tab-category-settings').find('.category-select-wrapper');
        if ($(this).val() === 'none') {
            wrapper.slideUp();
        } else {
            wrapper.slideDown();
        }
    });

    // 为所有分类过滤radio按钮添加change事件
    $('.tab-category-settings input[type="radio"]').on('change', function() {
        var wrapper = $(this).closest('.tab-category-settings').find('.category-select-wrapper');
        
        if ($(this).val() === 'none') {
            wrapper.slideUp();
        } else {
            wrapper.slideDown();
        }
    });

    // 初始化排序功能
    if($('#tabs-order-list').length) {
        $('#tabs-order-list').sortable({
            handle: '.dashicons-menu',
            update: function(event, ui) {
                // 获取排序后的顺序
                const order = $(this).sortable('toArray', {attribute: 'data-id'});
                
                // 保存排序
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'save_tabs_order',
                        order: order,
                        nonce: articleTabsSettings.nonce
                    },
                    success: function(response) {
                        if(response.success) {
                            // 显示成功提示
                            const notice = $('<div class="notice notice-success"><p>排序已保存</p></div>')
                                .insertAfter('#tabs-order-list')
                                .fadeOut(2000, function() {
                                    $(this).remove();
                                });
                        } else {
                            // 添加错误提示
                            console.error('保存失败:', response.data);
                            alert('保存失败: ' + response.data);
                        }
                    }
                });
            }
        });
    }
}); 