<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
?>

<div class="profile-edit-container">
    <h2>编辑个人资料</h2>
    
    <!-- 头像上传部分 -->
    <div class="avatar-upload-section">
        <div class="current-avatar">
            <?php echo get_avatar($current_user->ID, 150); ?>
        </div>
        <form id="avatar-upload-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('update_avatar', 'avatar_nonce'); ?>
            <div class="form-group">
                <label for="user_avatar">更换头像</label>
                <input type="file" id="user_avatar" name="user_avatar" accept="image/*">
                <p class="description">支持 jpg、png 格式，文件大小不超过 2MB</p>
            </div>
            <button type="submit" class="button">上传头像</button>
        </form>
    </div>

    <!-- 基本信息表单 -->
    <form id="profile-edit-form" method="post">
        <?php wp_nonce_field('update_profile', 'profile_nonce'); ?>
        
        <div class="form-group">
            <label for="display_name">显示名称</label>
            <input type="text" id="display_name" name="display_name" 
                   value="<?php echo esc_attr($current_user->display_name); ?>">
        </div>
        
        <div class="form-group">
            <label for="user_email">电子邮箱</label>
            <input type="email" id="user_email" name="user_email" 
                   value="<?php echo esc_attr($current_user->user_email); ?>">
        </div>
        
        <div class="form-group">
            <label for="user_description">个人简介</label>
            <textarea id="user_description" name="description"><?php echo esc_textarea(get_user_meta($current_user->ID, 'description', true)); ?></textarea>
        </div>
        
        <button type="submit" class="button button-primary">保存更改</button>
    </form>

    <!-- 密码修改表单 -->
    <div class="password-change-section">
        <h3>修改密码</h3>
        <form id="password-change-form" method="post">
            <?php wp_nonce_field('change_password', 'password_nonce'); ?>
            
            <div class="form-group">
                <label for="current_password">当前密码</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">新密码</label>
                <input type="password" id="new_password" name="new_password" required>
                <div class="password-strength-meter"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">确认新密码</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="button">更改密码</button>
        </form>
    </div>
</div> 