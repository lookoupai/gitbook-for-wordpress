jQuery(document).ready(function($) {
    // 处理投票按钮点击
    $('.vote-approve, .vote-reject').click(function() {
        var $button = $(this);
        var postId = $button.data('post-id');
        var vote = $button.hasClass('vote-approve') ? 1 : 0;
        var voteType = $button.data('type');
        
        $.ajax({
            url: votingVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'handle_vote',
                nonce: votingVars.nonce,
                post_id: postId,
                vote: vote,
                vote_type: voteType
            },
            beforeSend: function() {
                $button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('投票失败，请稍后重试');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // 处理管理员决定按钮点击
    $('.admin-approve, .admin-reject').click(function() {
        var $button = $(this);
        var postId = $button.data('post-id');
        var decision = $button.hasClass('admin-approve') ? 1 : 0;
        var voteType = $button.data('type');
        
        if (confirm('确定要' + (decision ? '通过' : '拒绝') + '这篇' + (voteType === 'edit' ? '修改' : '文章') + '吗？')) {
            $.ajax({
                url: votingVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'admin_vote_decision',
                    nonce: votingVars.nonce,
                    post_id: postId,
                    decision: decision,
                    vote_type: voteType
                },
                beforeSend: function() {
                    $button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('操作失败，请稍后重试');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        }
    });

    // 处理查看差异按钮点击
    $('.view-diff-btn').click(function() {
        var $button = $(this);
        var revisionId = $button.data('revision-id');
        var $modal = $('#diff-modal');
        var $modalContent = $('#diff-modal-content');
        
        // 显示加载中
        $modalContent.html('<div class="loading">加载中...</div>');
        $modal.show();
        
        // 获取差异内容
        $.ajax({
            url: votingVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_revision_diff',
                nonce: votingVars.nonce,
                revision_id: revisionId
            },
            success: function(response) {
                if (response.success) {
                    $modalContent.html(response.data);
                } else {
                    $modalContent.html('<div class="error">加载失败：' + response.data + '</div>');
                }
            },
            error: function() {
                $modalContent.html('<div class="error">加载失败，请稍后重试</div>');
            }
        });
    });

    // 处理弹窗关闭
    $('.modal .close').click(function() {
        $(this).closest('.modal').hide();
    });

    // 点击弹窗外部关闭弹窗
    $(window).click(function(e) {
        if ($(e.target).is('.modal')) {
            $('.modal').hide();
        }
    });

    // ESC键关闭弹窗
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            $('.modal').hide();
        }
    });

    // 添加加载中的样式
    $('<style>')
        .text(`
            .loading {
                text-align: center;
                padding: 20px;
                color: #666;
            }
            .error {
                color: #f44336;
                padding: 20px;
                text-align: center;
            }
            .diff-meta {
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            .diff-title {
                font-weight: bold;
                margin-bottom: 10px;
            }
            .diff-content table {
                width: 100%;
                border-collapse: collapse;
            }
            .diff-content td {
                padding: 5px;
                border: 1px solid #ddd;
                vertical-align: top;
            }
            .diff-content .diff-deletedline {
                background: #fdd;
            }
            .diff-content .diff-addedline {
                background: #dfd;
            }
            .diff-content .diff-context {
                color: #666;
            }
        `)
        .appendTo('head');
}); 