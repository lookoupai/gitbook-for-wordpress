jQuery(document).ready(function($) {
    // 处理收藏文章的删除
    $('.remove-favorite').on('click', function(e) {
        e.preventDefault();
        var postId = $(this).data('post-id');
        var $item = $(this).closest('.favorite-item');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'remove_favorite',
                post_id: postId,
                nonce: userCenter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $item.fadeOut();
                }
            }
        });
    });

    // 处理通知的已读状态
    $('.notification-item.unread').on('click', function() {
        var $notification = $(this);
        var notificationId = $notification.data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mark_notification_read',
                notification_id: notificationId,
                nonce: userCenter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $notification.removeClass('unread');
                }
            }
        });
    });

    // 个人资料表单提交
    $('#profile-edit-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    alert('个人资料更新成功！');
                } else {
                    alert('更新失败，请重试。');
                }
            },
            complete: function() {
                $submitButton.prop('disabled', false);
            }
        });
    });

    // 评论编辑功能
    $('.edit-comment').on('click', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        $('#comment-text-' + commentId).hide();
        $('#edit-form-' + commentId).show();
    });

    $('.cancel-edit').on('click', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var commentId = form.find('input[name="comment_id"]').val();
        form.hide();
        $('#comment-text-' + commentId).show();
    });

    $('.edit-comment-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var commentId = $form.find('input[name="comment_id"]').val();
        
        $.ajax({
            url: userCenter.ajaxurl,
            type: 'POST',
            data: {
                action: 'edit_comment',
                comment_id: commentId,
                content: $form.find('textarea').val(),
                nonce: userCenter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#comment-text-' + commentId).html(response.data.content).show();
                    $form.hide();
                    alert('评论已更新，等待审核');
                } else {
                    alert(response.data || '更新失败');
                }
            }
        });
    });

    // 评论删除功能
    $('.delete-comment').on('click', function(e) {
        e.preventDefault();
        if (!confirm('确定要删除这条评论吗？')) {
            return;
        }
        
        var $link = $(this);
        var commentId = $link.data('comment-id');
        
        $.ajax({
            url: userCenter.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_comment',
                comment_id: commentId,
                nonce: userCenter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#comment-' + commentId).fadeOut();
                } else {
                    alert(response.data || '删除失败');
                }
            }
        });
    });

    // 头像上传预览
    $('#user_avatar').on('change', function(e) {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.current-avatar img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // 密码强度检测
    $('#new_password').on('keyup', function() {
        var password = $(this).val();
        var strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!]+/)) strength++;

        var meter = $('.password-strength-meter');
        meter.removeClass('weak medium strong');
        
        if (strength <= 2) {
            meter.addClass('weak').text('弱');
        } else if (strength <= 4) {
            meter.addClass('medium').text('中');
        } else {
            meter.addClass('strong').text('强');
        }
    });

    // 确认密码验证
    $('#confirm_password').on('keyup', function() {
        var password = $('#new_password').val();
        var confirm = $(this).val();
        
        if (password !== confirm) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });

    // 修订历史显示/隐藏
    $('.toggle-history').on('click', function(e) {
        e.preventDefault();
        $(this).next('.revision-history').slideToggle();
    });

    // 显示修订差异
    $('.show-diff').on('click', function(e) {
        e.preventDefault();
        var revisionId = $(this).data('revision');
        
        // 添加遮罩层
        $('<div class="modal-overlay"></div>').appendTo('body');
        
        // 显示加载提示
        var $modal = $('<div class="revision-diff-modal"><p>加载中...</p></div>').appendTo('body');
        
        $.ajax({
            url: userCenter.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_revision_diff',
                revision_id: revisionId,
                nonce: userCenter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $modal.html(response.data);
                    // 添加关闭按钮
                    $('<button class="close-modal">关闭</button>').appendTo($modal);
                } else {
                    $modal.html('<p class="error">加载失败：' + (response.data || '未知错误') + '</p>');
                }
            },
            error: function() {
                $modal.html('<p class="error">加载失败，请重试</p>');
            }
        });
    });

    // 关闭修订差异弹窗
    $(document).on('click', '.close-modal, .modal-overlay', function() {
        $('.revision-diff-modal, .modal-overlay').remove();
    });

    // 阻止弹窗内点击事件冒泡
    $(document).on('click', '.revision-diff-modal', function(e) {
        e.stopPropagation();
    });

    // 修订审核处理
    $('.approve-revision').on('click', function(e) {
        e.preventDefault();
        if (!confirm('确定要通过这个修改吗？')) {
            return;
        }
        
        var revisionId = $(this).data('revision');
        $.ajax({
            url: userCenter.ajaxurl,
            type: 'POST',
            data: {
                action: 'approve_revision',
                revision_id: revisionId,
                nonce: userCenter.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    $('.reject-revision').on('click', function(e) {
        e.preventDefault();
        var revisionId = $(this).data('revision');
        var reason = prompt('请输入拒绝原因：');
        if (reason === null) {
            return;
        }
        
        $.ajax({
            url: userCenter.ajaxurl,
            type: 'POST',
            data: {
                action: 'reject_revision',
                revision_id: revisionId,
                reject_reason: reason,
                nonce: userCenter.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
}); 