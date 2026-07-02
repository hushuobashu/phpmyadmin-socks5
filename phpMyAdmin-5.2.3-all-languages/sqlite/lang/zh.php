<?php
return [
    // 登录
    'login_title' => 'SQLite 管理 - 登录',
    'sqlite_admin' => 'SQLite 管理',
    'server' => '服务器',
    'password' => '访问密码',
    'connect' => '连接',
    'invalid_password' => '密码错误。',
    'invalid_server' => '无效的服务器选择。',
    'dirs_not_exist' => '配置的目录均不存在。',

    // 导航
    'mysql' => 'MySQL',
    'mongodb' => 'MongoDB',
    'logout' => '退出',
    'loading' => '加载中...',
    'databases' => '数据库',
    'browse' => '浏览',
    'structure' => '结构',
    'query' => '查询',
    'export' => '导出',
    'drop' => '删除',
    'prev' => '上一页',
    'next' => '下一页',

    // 按钮
    'create_database' => '创建数据库',
    'create_table' => '创建表',
    'add_column' => '+ 添加列',
    'execute' => '执行',
    'import' => '导入',
    'insert' => '插入',
    'save' => '保存',
    'cancel' => '取消',
    'edit' => '编辑',
    'delete' => '删除',
    'back_to_tables' => '返回表列表',
    'insert_row' => '插入行',

    // 表头
    'name' => '名称',
    'size' => '大小',
    'path' => '路径',
    'actions' => '操作',
    'type' => '类型',
    'not_null' => '非空',
    'default_val' => '默认值',
    'pk' => '主键',
    'unique' => '唯一',
    'columns' => '列',
    'column' => '列',
    'value' => '值',
    'null_val' => 'NULL',
    'yes' => '是',

    // 表单
    'table_name' => '表名',
    'column_name' => '列名',
    'new_db_name' => '新数据库名称',
    'format' => '格式',
    'paste_sql_here' => '在此粘贴 SQL...',

    // 信息/结果
    'no_databases_found' => '在配置的目录中未找到 SQLite 数据库文件。',
    'no_tables_found' => '未找到表。',
    'no_data_found' => '没有数据。',
    'query_no_results' => '查询无结果。',
    'rows_returned' => '返回 %d 行，耗时 %s 毫秒',
    'query_success' => '查询执行成功。影响 %d 行。（%s 毫秒）',
    'max_export_rows' => 'SQL/CSV 导出每表最多 10,000 行。',
    'rows' => '行',

    // 确认对话框
    'confirm_drop_table' => "确定删除表 '%s'？",
    'confirm_delete_row' => '确定删除此行？',

    // 成功消息
    'db_created' => '数据库 "%s" 创建成功。',
    'table_created' => '表 "%s" 创建成功。',
    'table_dropped' => '表 "%s" 已删除。',
    'row_deleted' => '行已删除。',
    'row_inserted' => '行已插入。',
    'row_updated' => '行已更新。',
    'sql_imported' => 'SQL 文件导入成功。',
    'sql_executed' => 'SQL 执行成功。',

    // 错误消息
    'db_name_required' => '数据库名称为必填项。',
    'invalid_directory' => '无效的目录。',
    'column_required' => '至少需要一列。',
    'file_read_error' => '读取上传文件失败或文件为空。',
    'no_sql_provided' => '未提供文件或 SQL。',
    'row_not_found' => '未找到该行。',
    'not_authenticated' => '未认证',
    'post_required' => '需要 POST 请求',
    'missing_params' => '缺少必需参数',
    'csrf_mismatch' => 'CSRF 令牌不匹配',
    'error_scanning' => '扫描 %s 时出错：%s',

    // 页面标题
    'tables_in' => '表列表 -',
    'structure_title' => '结构：',
    'query_title' => '查询',
    'export_title' => '导出',
    'import_into' => '导入到',
    'edit_row' => '编辑行',
    'create_statement' => 'CREATE 语句',
    'indexes' => '索引',

    // 导出
    'upload_sql_file' => '上传 SQL 文件',
    'paste_sql' => '粘贴 SQL',
    'sql_dump_label' => 'SQL 转储（CREATE + INSERT 语句）',
    'csv_label' => 'CSV',
    'csv_first_table' => '（仅第一个表）',
    'download_db' => '下载 .db 文件',
];
