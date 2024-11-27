jQuery(document).ready(function($) {
    // 编辑文章表单提交
    $('#edit-post-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        
        // 禁用提交按钮防止重复提交
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: form.serialize() + '&action=edit_post',
            success: function(response) {
                if(response.success) {
                    $('.edit-post-success').text('文章更新成功！').show();
                    $('.edit-post-error').hide();
                } else {
                    $('.edit-post-error').text(response.data.message).show();
                    $('.edit-post-success').hide();
                }
            },
            error: function() {
                $('.edit-post-error').text('发生错误，请稍后重试').show();
                $('.edit-post-success').hide();
            },
            complete: function() {
                // 重新启用提交按钮
                submitButton.prop('disabled', false);
            }
        });
    });

    // 如果使用Markdown编辑器，可以在这里初始化
    if(typeof SimpleMDE !== 'undefined') {
        var simplemde = new SimpleMDE({ element: $("#post_content")[0] });
    }
}); 