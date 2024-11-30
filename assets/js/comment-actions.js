jQuery(document).ready(function($) {
    // 评论提交处理
    $('.comment-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('input[type="submit"]');
        var loadingText = '提交中...';
        var originalText = submitButton.val();
        
        // 添加必要的数据
        var formData = new FormData(form[0]);
        formData.append('action', 'handle_ajax_comment');
        formData.append('nonce', commentVars.nonce);
        
        // 添加加载动画
        submitButton.prop('disabled', true)
                   .val(loadingText)
                   .addClass('loading');
        
        $.ajax({
            url: commentVars.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Response:', response);
                
                if(response.success) {
                    try {
                        var newComment = $(response.data.comment_html);
                        $('.comment-list').append(newComment);
                        newComment.hide().fadeIn(800);
                        
                        // 清空表单
                        form.find('textarea').val('');
                        
                        // 显示成功消息
                        showNotification('评论发表成功！', 'success');
                    } catch(e) {
                        console.error('Error processing comment HTML:', e);
                        showNotification('评论已提交，但显示出错。请刷新页面查看。', 'warning');
                    }
                } else {
                    showNotification(response.data.message || '评论提交失败，请重试', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
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
    
    // 评论编辑功能 - 使用事件委托
    $(document).on('click', '.edit-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        var content = $('#comment-content-' + commentId).text().trim();
        
        $('#comment-content-' + commentId).hide();
        $('#comment-edit-form-' + commentId).show()
            .find('textarea').val(content);
    });
    
    // 保存编辑的评论 - 使用事件委托
    $(document).on('click', '.save-comment', function() {
        var commentId = $(this).data('comment-id');
        var content = $('#comment-edit-textarea-' + commentId).val();
        
        $.ajax({
            url: commentVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'edit_comment',
                comment_id: commentId,
                content: content,
                nonce: commentVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#comment-content-' + commentId).html(response.data.content).show();
                    $('#comment-edit-form-' + commentId).hide();
                    
                    if (response.data.pending) {
                        showNotification('评论已更新，等待审核', 'warning');
                    } else {
                        showNotification('评论已更新', 'success');
                    }
                } else {
                    showNotification(response.data || '更新失败', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showNotification('发生错误，请稍后重试', 'error');
            }
        });
    });
    
    // 取消编辑评论 - 使用事件委托
    $(document).on('click', '.cancel-edit', function() {
        var commentId = $(this).data('comment-id');
        $('#comment-content-' + commentId).show();
        $('#comment-edit-form-' + commentId).hide();
    });
    
    // 删除评论
    $('.delete-comment').on('click', function(e) {
        e.preventDefault();
        if (!confirm('确定要删除这条评论吗？')) {
            return;
        }
        
        var $link = $(this);
        var commentId = $link.data('comment-id');
        
        $.ajax({
            url: commentVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_comment',
                comment_id: commentId,
                nonce: commentVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#comment-' + commentId).fadeOut();
                    showNotification('评论已删除', 'success');
                } else {
                    showNotification(response.data || '删除失败', 'error');
                }
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