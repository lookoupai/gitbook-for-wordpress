jQuery(document).ready(function($) {
    // 评论提交处理
    $('.comment-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('input[type="submit"]');
        var loadingText = '提交中...';
        var originalText = submitButton.val();
        
        // 添加加载动画
        submitButton.prop('disabled', true)
                   .val(loadingText)
                   .addClass('loading');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if(response.success) {
                    // 平滑滚动到新评论
                    var newComment = $(response.data.comment_html);
                    $('.comment-list').append(newComment);
                    newComment.hide().fadeIn(800);
                    
                    // 清空表单
                    form.find('textarea').val('');
                    
                    // 显示成功消息
                    showNotification('评论发表成功！', 'success');
                } else {
                    showNotification(response.data.message || '评论提交失败，请重试', 'error');
                }
            },
            error: function() {
                showNotification('发生错误，请稍后重试', 'error');
            },
            complete: function() {
                // 恢复提交按钮
                submitButton.prop('disabled', false)
                           .val(originalText)
                           .removeClass('loading');
            }
        });
    });
    
    // 回复按钮功能
    $('.comment-reply-link').on('click', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        var replyForm = $('#respond');
        var cancelReply = $('<button>', {
            class: 'cancel-reply',
            text: '取消回复',
            type: 'button'
        });
        
        // 平滑滚动到回复表单
        $(this).closest('.comment').after(replyForm);
        replyForm.slideDown(300);
        
        // 添加取消回复按钮
        if(!replyForm.find('.cancel-reply').length) {
            replyForm.find('.form-submit').before(cancelReply);
        }
        
        // 设置父评论ID
        $('#comment_parent').val(commentId);
        
        // 滚动到表单位置
        $('html, body').animate({
            scrollTop: replyForm.offset().top - 100
        }, 500);
    });
    
    // 取消回复
    $(document).on('click', '.cancel-reply', function() {
        var replyForm = $('#respond');
        $('#comment_parent').val('0');
        $(this).remove();
        
        // 将表单移回原位置
        $('#comments').append(replyForm);
    });
    
    // 通知提示函数
    function showNotification(message, type) {
        var notification = $('<div>', {
            class: 'comment-notification ' + type,
            text: message
        });
        
        $('.comments-area').prepend(notification);
        
        notification.fadeIn(300).delay(3000).fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    // 添加文本框自动增高
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}); 