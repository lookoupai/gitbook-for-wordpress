# GitBook For WordPress 主题开发文档

## 项目架构

主题文件结构：
```
theme-root/
├── inc/                           # 功能模块目录
│   ├── notifications.php          # 通知系统
│   ├── user-center-functions.php  # 用户中心功能
│   ├── post-submission.php        # 文章投稿功能
│   ├── post-editing.php          # 文章编辑功能
│   ├── voting-functions.php      # 投票系统功能
│   ├── voting-settings.php       # 投票系统设置
│   └── comments.php              # 评论系统功能
├── assets/
│   ├── css/                      # 样式文件
│   │   ├── menu.css             # 菜单样式
│   │   ├── voting.css           # 投票页面样式
│   │   ├── comments.css         # 评论样式
│   │   └── login-register.css   # 登录注册样式
│   └── js/
│       ├── voting.js            # 投票功能脚本
│       ├── comment-actions.js   # 评论功能脚本
│       └── markdown-editor.js   # Markdown编辑器
├── template-parts/              # 模板部件
│   └── user/                    # 用户相关模板
│       └── notifications.php    # 通知显示模板
└── functions.php                # 主题核心功能和模块加载
```

## 功能模块说明

### 1. 通知系统 (notifications.php)
- 用户通知的添加、获取、标记已读和删除
- 通知数据表创建和管理
- AJAX 处理通知操作
- 分页显示通知列表
- 全部标记为已读功能

### 2. 用户中心 (user-center-functions.php)
- 用户资料管理
- 头像上传
- 密码修改
- 自定义头像显示
- 文章状态标签管理

### 3. 文章投稿 (post-submission.php)
- 文章提交处理
- Markdown 编辑器支持
- 投稿审核流程
- 分类和标签管理

### 4. 文章编辑 (post-editing.php)
- 协作编辑功能
- 修订版本管理
- 编辑历史记录
- 差异对比功能

### 5. 投票系统 (voting-functions.php)
- 文章投票功能
- 修改投票功能
- 投票阈值检查
- 管理员决策处理
- 投票历史记录

### 6. 评论系统 (comments.php)
- 评论提交和显示
- 评论编辑功能
- Markdown 支持
- 实时预览功能
- 评论审核流程
- 评论通知功能

## 数据库表结构

### 用户通知表 (wp_user_notifications)
```sql
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
```

### 投票表 (wp_post_votes)
```sql
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
```

## 开发注意事项

### 1. 代码规范
- 遵循 WordPress 编码标准
- 使用命名空间避免函数名冲突
- 保持模块化和单一职责原则
- 代码注释清晰完整

### 2. 安全性
- 所有文件都需要 ABSPATH 检查
- 表单操作需要 nonce 验证
- 用户权限检查
- 数据存取需要正确的转义和验证
- XSS 和 CSRF 防护

### 3. 性能优化
- 按需加载资源
- 使用事务处理批量操作
- 合理使用缓存
- 优化数据库查询
- 资源压缩和合并

### 4. 兼容性
- 支持 WordPress 多版本
- 响应式设计支持
- 跨浏览器兼容性
- 移动设备适配

## 更新维护

### 1. 版本控制
- 遵循语义化版本号规范
- 记录所有更改到 CHANGELOG.md
- Git 分支管理规范

### 2. 数据库更新
- 版本升级时检查表结构
- 提供数据迁移方案
- 保持向后兼容性
- 数据备份和恢复

### 3. 功能扩展
- 保持模块独立性
- 使用钩子和过滤器
- 文档化新增功能
- 单元测试覆盖

## 常见问题解决

### 1. 菜单显示问题
- 检查菜单注册
- 验证菜单分配
- 确认 Walker 类实现
- 样式冲突处理

### 2. 投票功能问题
- 验证用户权限
- 检查投票记录
- 确认阈值设置
- 数据一致性检查

### 3. 编辑功能问题
- 检查修订版本状态
- 验证编辑权限
- 确认通知发送
- 差异对比功能

### 4. 评论功能问题
- Markdown 渲染检查
- 评论权限验证
- 编辑和删除功能
- 通知发送确认
- XSS 防护

## 主题激活流程

1. 创建必要的数据表
   - 通知表 (wp_user_notifications)
   - 投票表 (wp_post_votes)

2. 创建默认页面
   - 用户中心页面
   - 投稿页面
   - 编辑页面
   - 投票管理页面

3. 创建默认菜单
   - 顶部菜单
   - 侧边栏菜单

4. 设置默认选项
   - 投票所需票数
   - 通过比例
   - 最小注册月数

## 贡献指南

1. 提交规范
   - 遵循 Git commit 规范
   - 提供完整的测试用例
   - 更新相关文档

2. 代码审查
   - 代码风格检查
   - 功能测试验证
   - 性能影响评估

3. 文档维护
   - 及时更新开发文档
   - 添加新功能说明
   - 更新常见问题

## 联系方式

如有问题或建议，请通过以下方式联系：
- GitHub Issues
- 主题支持论坛
- 开发者邮箱

## 2024-11-27 最近更新

### 1. 评论系统优化
- 优化了评论编辑框样式和交互
- 增加了评论框最小高度(150px)
- 添加了更好的视觉反馈效果
- 改进了响应式设计

### 2. 文章列表改进
- 添加了文章标题链接功能
- 优化了文章列表的显示样式
- 改进了标题和更新时间的显示

### 3. 代码规范
- 统一了行尾符处理（LF/CRLF）
- 添加了 .gitattributes 配置
- 优化了代码结构和注释

### 4. 性能优化
- 优化了资源加载顺序
- 添加了条件加载逻辑
- 改进了样式文件组织

## 文件结构更新

### 新增/修改的文件
```
theme-root/
├── assets/
│   ├── css/
│   │   ├── comments.css      # 优化的评论样式
│   │   └── post-list.css     # 新增的文章列表样式
│   └── js/
│       └── comment-actions.js # 优化的评论交互脚本
├── right-content.php         # 修改的内容显示模板
└── .gitattributes           # 新增的 Git 配置文件
```

## 样式指南

### 1. 评论系统样式
- 使用圆角和阴影提升视觉层次
- 统一的颜色方案
- 响应式布局适配
- 交互动效优化

### 2. 文章列表样式
- 标题链接样式
- 更新时间显示
- 列表项间距和对齐

