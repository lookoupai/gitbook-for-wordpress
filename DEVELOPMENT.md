# GitBook For WordPress 主题开发文档

## 项目架构

主题文件结构： 

theme-root/

├── inc/ # 功能模块目录

│ ├── notifications.php # 通知系统

│ ├── user-center-functions.php # 用户中心功能

│ ├── post-submission.php # 文章投稿功能

│ ├── post-editing.php # 文章编辑功能

│ ├── voting-functions.php # 投票系统功能

│ ├── voting-settings.php # 投票系统设置

│ └── comments.php # 评论系统功能

├── assets/

│ ├── css/

│ │ ├── menu.css # 菜单样式

│ │ └── voting.css # 投票页面样式

│ └── js/

│ └── voting.js # 投票功能脚本

└── functions.php # 主题核心功能和模块加载



## 功能模块说明

### 1. 通知系统 (notifications.php)
- 用户通知的添加、获取、标记已读和删除
- 通知数据表创建和管理
- AJAX 处理通知操作

### 2. 用户中心 (user-center-functions.php)
- 用户资料管理
- 头像上传
- 密码修改
- 自定义头像显示

### 3. 文章投稿 (post-submission.php)
- 文章提交处理
- Markdown 编辑器支持
- 投稿审核流程

### 4. 文章编辑 (post-editing.php)
- 协作编辑功能
- 修订版本管理
- 编辑历史记录

### 5. 投票系统 (voting-functions.php)
- 文章投票功能
- 修改投票功能
- 投票阈值检查
- 管理员决策处理

### 6. 评论系统 (comments.php)
- 评论提交和显示
- 评论编辑功能
- Markdown 支持

## 数据库表结构

### 用户通知表 (wp_user_notifications)

sql
CREATE TABLE wp_user_notifications (
id bigint(20) NOT NULL AUTO_INCREMENT,
user_id bigint(20) NOT NULL,
message text NOT NULL,
type varchar(50) NOT NULL DEFAULT 'info',
is_read tinyint(1) NOT NULL DEFAULT 0,
created_at datetime NOT NULL,
PRIMARY KEY (id),
KEY user_id (user_id)
);


### 投票表 (wp_post_votes)

sql
CREATE TABLE wp_post_votes (
id bigint(20) NOT NULL AUTO_INCREMENT,
post_id bigint(20) NOT NULL,
user_id bigint(20) NOT NULL,
vote_type tinyint(1) NOT NULL,
vote_for varchar(20) DEFAULT 'post',
is_admin_decision tinyint(1) DEFAULT 0,
vote_date datetime DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY vote_unique (post_id, user_id, vote_for),
KEY post_id (post_id),
KEY user_id (user_id)
);


## 开发注意事项

1. 代码规范
   - 遵循 WordPress 编码标准
   - 使用命名空间避免函数名冲突
   - 保持模块化和单一职责原则

2. 安全性
   - 所有文件都需要 ABSPATH 检查
   - 表单操作需要 nonce 验证
   - 用户权限检查
   - 数据存取需要正确的转义和验证

3. 性能优化
   - 按需加载资源
   - 使用事务处理批量操作
   - 合理使用缓存
   - 优化数据库查询

4. 兼容性
   - 支持 WordPress 多版本
   - 响应式设计支持
   - 跨浏览器兼容性

## 更新维护

1. 版本控制
   - 遵循语义化版本号规范
   - 记录所有更改到 CHANGELOG.md

2. 数据库更新
   - 版本升级时检查表结构
   - 提供数据迁移方案
   - 保持向后兼容性

3. 功能扩展
   - 保持模块独立性
   - 使用钩子和过滤器
   - 文档化新增功能

## 常见问题解决

1. 菜单显示问题
   - 检查菜单注册
   - 验证菜单分配
   - 确认 Walker 类实现

2. 投票功能问题
   - 验证用户权限
   - 检查投票记录
   - 确认阈值设置

3. 编辑功能问题
   - 检查修订版本状态
   - 验证编辑权限
   - 确认通知发送

