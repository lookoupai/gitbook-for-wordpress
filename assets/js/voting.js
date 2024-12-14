jQuery(document).ready(function($) {
    // 确保Modal对象存在
    if (typeof bootstrap !== 'undefined') {
        var voteModal = new bootstrap.Modal(document.getElementById('voteReasonModal'));
        
        // 处理投票按钮点击
        $('.vote-approve, .vote-reject').click(function(e) {
            e.preventDefault(); // 阻止默认行为
            var $button = $(this);
            currentVoteData = {
                postId: $button.data('post-id'),
                vote: $button.hasClass('vote-approve') ? 1 : 0,
                voteType: $button.data('type')
            };

            // 根据投票类型显示对应的预设理由
            var isApprove = $button.hasClass('vote-approve');
            $('.preset-reason-select option').show();
            $('.preset-reason-select').val('');
            $('.preset-reason-select ' + (isApprove ? '.reject-reason' : '.approve-reason')).hide();
            
            // 重置理由选择
            $('.reason-type-select[value="preset"]').prop('checked', true);
            $('.preset-reasons').show();
            $('.custom-reason').hide();
            $('.custom-reason-input').val('');

            // 显示弹窗
            voteModal.show();
        });
    } else {
        console.error('Bootstrap is not loaded');
    }

    // 理由类型切换
    $('.reason-type-select').on('change', function() {
        if ($(this).val() === 'preset') {
            $('.preset-reasons').show();
            $('.custom-reason').hide();
        } else {
            $('.preset-reasons').hide();
            $('.custom-reason').show();
        }
    });

    // 处理管理员决定按钮点击
    $('.admin-approve, .admin-reject').click(function() {
        var $button = $(this);
        var postId = $button.data('post-id');
        var decision = $button.hasClass('admin-approve') ? 1 : 0;
        var voteType = $button.data('type');
        
        // 设置当前投票数据
        currentVoteData = {
            postId: postId,
            vote: decision,
            voteType: voteType
        };

        // 根据投票类型显示对应的预设理由
        var isApprove = decision === 1;
        $('.preset-reason-select option').show();
        $('.preset-reason-select').val('');
        $('.preset-reason-select ' + (isApprove ? '.reject-reason' : '.approve-reason')).hide();
        
        // 重置理由选择
        $('.reason-type-select[value="preset"]').prop('checked', true);
        $('.preset-reasons').show();
        $('.custom-reason').hide();
        $('.custom-reason-input').val('');

        // 显示理由弹窗
        voteModal.show();
    });

    // 处理确认投票
    $('.submit-vote').click(function() {
        if (!currentVoteData) return;

        var reasonType = $('.reason-type-select:checked').val();
        var reasonContent = reasonType === 'preset' 
            ? $('.preset-reason-select').val()
            : $('.custom-reason-input').val();
        
        if (!reasonContent) {
            alert('请填写投票理由');
            return;
        }

        // 检查是否是由管理员按钮触发的
        var isAdminDecision = $(document.activeElement).hasClass('admin-approve') || 
                            $(document.activeElement).hasClass('admin-reject');
        var action = isAdminDecision ? 'admin_vote_decision' : 'handle_vote';
        var data = isAdminDecision ? {
            action: 'admin_vote_decision',
            nonce: votingVars.nonce,
            post_id: currentVoteData.postId,
            decision: currentVoteData.vote,
            vote_type: currentVoteData.voteType,
            reason_type: reasonType,
            reason_content: reasonContent
        } : {
            action: 'handle_vote',
            nonce: votingVars.nonce,
            post_id: currentVoteData.postId,
            vote: currentVoteData.vote,
            vote_type: currentVoteData.voteType,
            reason_type: reasonType,
            reason_content: reasonContent
        };

        $.ajax({
            url: votingVars.ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('.submit-vote').prop('disabled', true);
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
                $('.submit-vote').prop('disabled', false);
                voteModal.hide();
            }
        });
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