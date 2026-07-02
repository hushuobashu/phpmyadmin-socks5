<?php
return [
    // 登录
    'login_title' => 'MongoDB 管理 - 登录',
    'mongodb_admin' => 'MongoDB 管理',
    'server' => '服务器',
    'host' => '主机',
    'port' => '端口',
    'username' => '用户名',
    'password' => '密码',
    'auth_database' => '认证数据库',
    'connect' => '连接',
    'manual' => '-- 手动 --',
    'optional' => '可选',
    'invalid_server' => '无效的服务器选择。',

    // 导航
    'mysql' => 'MySQL',
    'sqlite' => 'SQLite',
    'logout' => '退出',
    'loading' => '加载中...',
    'databases' => '数据库',
    'server_info' => '服务器信息',

    // 按钮
    'browse' => '浏览',
    'query' => '查询',
    'indexes' => '索引',
    'stats' => '统计',
    'export' => '导出',
    'drop' => '删除',
    'insert_document' => '插入文档',
    'create_collection' => '创建集合',
    'edit' => '编辑',
    'delete' => '删除',
    'execute' => '执行',
    'prev' => '上一页',
    'next' => '下一页',
    'cancel' => '取消',
    'save' => '保存',
    'insert' => '插入',
    'update' => '更新',
    'back_to_collections' => '返回集合列表',

    // 表头
    'name' => '名称',
    'size' => '大小',
    'type' => '类型',
    'actions' => '操作',
    'collections' => '集合',
    'documents' => '文档',

    // 表单标签
    'filter' => '筛选条件',
    'projection' => '投影',
    'sort' => '排序',
    'limit' => '限制',
    'skip' => '跳过',
    'pipeline' => '管道 (JSON 数组)',
    'collection_name' => '新集合名称',

    // 查询
    'find' => '查找',
    'aggregate' => '聚合',
    'query_title' => '查询',
    'documents_returned' => '返回 %d 个文档',

    // 信息/结果
    'no_documents_found' => '没有找到文档。',
    'no_collections_found' => '没有找到集合。',
    'collections_in' => '集合 -',

    // 确认对话框
    'confirm_drop_collection' => "确定删除集合 '%s'？",
    'confirm_delete_document' => '确定删除此文档？',
    'confirm_drop_index' => "确定删除索引 '%s'？",
    'confirm_drop_database' => "确定删除数据库 '%s'？",

    // 成功消息
    'collection_created' => '集合 "%s" 创建成功。',
    'collection_dropped' => '集合 "%s" 已删除。',
    'document_deleted' => '文档已删除。',
    'document_inserted' => '文档已插入。',
    'document_updated' => '文档已更新。',
    'index_created' => '索引创建成功。',
    'index_dropped' => '索引 "%s" 已删除。',

    // 错误消息
    'invalid_json' => '无效的 JSON：%s',
    'document_not_found' => '未找到文档。',
    'csrf_mismatch' => 'CSRF 令牌不匹配',

    // 导出
    'export_title' => '导出',
    'format' => '格式',
    'json_export' => 'JSON（每行一个文档）',
    'csv_export' => 'CSV',
    'max_export_docs' => '最多导出 10,000 个文档。',
    'download' => '下载',

    // 索引
    'indexes_title' => '索引',
    'index_name' => '名称',
    'index_keys' => '键',
    'unique' => '唯一',
    'sparse' => '稀疏',
    'create_index' => '创建索引',
    'drop_index' => '删除',
    'key_field' => '键字段',
    'direction' => '方向',
    'ascending' => '升序 (1)',
    'descending' => '降序 (-1)',
    'text' => '文本',
    'add_key' => '+ 添加键',

    // 集合统计
    'collection_stats' => '集合统计',
    'stat_key' => '统计项',
    'stat_value' => '值',

    // 服务器信息
    'build_info' => '构建信息',
    'connections' => '连接数',
    'opcounters' => '操作计数器',
    'key' => '键',
    'value' => '值',

    // 文档编辑
    'edit_document' => '编辑文档',
    'insert_new_document' => '插入新文档',
    'document_json' => '文档 (JSON)',

    // 页面标题
    'yes' => '是',

    // 服务器信息页
    'server_information' => '服务器信息',
    'version' => '版本',
    'git_version' => 'Git 版本',
    'allocator' => '分配器',
    'javascript_engine' => 'JavaScript 引擎',
    'bits' => '位数',
    'max_bson_size' => '最大 BSON 大小',
    'current' => '当前',
    'available' => '可用',
    'total_created' => '累计创建',
    'server_status' => '服务器状态',
    'uptime' => '运行时间',
    'uptime_seconds' => '%s 秒',
    'process' => '进程',

    // 集合统计页
    'stats_title' => '统计',
    'namespace' => '命名空间',
    'document_count' => '文档数量',
    'avg_doc_size' => '平均文档大小',
    'storage_size' => '存储大小',
    'total_index_size' => '索引总大小',
    'number_of_indexes' => '索引数量',
    'index_sizes' => '索引大小',

    // 索引页
    'indexes_on' => '索引 -',
    'field' => '字段',
    'auto' => '自动',

    // 导出页
    'filter_optional_json' => '筛选条件（可选，JSON）',
    'max_export_note' => '每次最多导出 10,000 个文档。',
];
