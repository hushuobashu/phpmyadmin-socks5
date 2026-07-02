# phpmyadmin-socks5

基于 phpMyAdmin 5.2.3 的修改版本，增加了通过 SOCKS5 代理和 SSH 隧道连接 MySQL 的支持，同时内置 SQLite 和 MongoDB 管理模块。

[English](README.md)

## 功能

- **SOCKS5 代理** — 通过 socat 建立 SOCKS5 隧道连接 MySQL
- **SSH Local Forward** — 通过 SSH 端口转发直连远程 MySQL
- **SSH Dynamic (SOCKS5)** — 通过 SSH 动态代理建立 SOCKS5 隧道
- 支持 SOCKS5 用户名/密码认证
- 支持 SSH 私钥认证和密码认证
- 持久化隧道 — SSH/socat 进程跨 HTTP 请求存活，相同配置自动复用
- **SQLite 管理** — 内置 SQLite 数据库浏览器，支持查询、导出、结构管理
- **MongoDB 管理** — 内置 MongoDB 管理面板，支持查询、索引、导出、服务器信息
- SQLite 和 MongoDB 模块支持中英文双语
- 对 phpMyAdmin 其他功能无侵入，不配置时行为与原版完全一致

## 快速启动

使用 PHP 内置 Web 服务器进行本地开发：

```bash
cd phpMyAdmin-5.2.3-all-languages
php -S localhost:9999
```

然后在浏览器中打开：

- MySQL (phpMyAdmin): http://localhost:9999/
- SQLite 管理: http://localhost:9999/sqlite/
- MongoDB 管理: http://localhost:9999/mongodb/

## 依赖

- PHP 7.2+
- `socat` 1.8+（SOCKS5 代理模式和 SSH dynamic 模式需要）
- `ssh`（SSH 隧道模式需要，系统自带）
- `sshpass`（仅 SSH 密码认证时需要）
- PHP `sqlite3` 扩展（SQLite 模块需要）
- PHP `mongodb` 扩展（MongoDB 模块需要）

## 配置

在 `config.inc.php` 中添加配置。

### 方式一：SOCKS5 代理

适用于已有 SOCKS5 代理服务器的场景。

```php
$cfg['Servers'][$i]['host'] = 'your-mysql-host';
$cfg['Servers'][$i]['port'] = '3306';

$cfg['Servers'][$i]['socks5_proxy'] = '127.0.0.1:1080';
$cfg['Servers'][$i]['socks5_user'] = '';    // 可选
$cfg['Servers'][$i]['socks5_pass'] = '';    // 可选
```

### 方式二：SSH Local Forward

适用于通过跳板机访问内网 MySQL 的场景。最直接高效。

```php
$cfg['Servers'][$i]['host'] = '10.0.0.100';       // 目标 MySQL（跳板机可达）
$cfg['Servers'][$i]['port'] = '3306';

$cfg['Servers'][$i]['ssh_tunnel'] = 'local';
$cfg['Servers'][$i]['ssh_host'] = 'jump.example.com';
$cfg['Servers'][$i]['ssh_port'] = 22;
$cfg['Servers'][$i]['ssh_user'] = 'deploy';
$cfg['Servers'][$i]['ssh_key'] = '/path/to/private_key';
```

### 方式三：SSH Dynamic (SOCKS5)

适用于需要通过跳板机建立动态代理的场景。

```php
$cfg['Servers'][$i]['host'] = '10.0.0.100';
$cfg['Servers'][$i]['port'] = '3306';

$cfg['Servers'][$i]['ssh_tunnel'] = 'dynamic';
$cfg['Servers'][$i]['ssh_host'] = 'jump.example.com';
$cfg['Servers'][$i]['ssh_port'] = 22;
$cfg['Servers'][$i]['ssh_user'] = 'deploy';
$cfg['Servers'][$i]['ssh_password'] = 'mypassword';   // 需要 sshpass
```

### SSH 认证方式

```php
// 私钥认证（推荐）
$cfg['Servers'][$i]['ssh_key'] = '/home/www/.ssh/id_rsa';

// 密码认证（需要 sshpass）
$cfg['Servers'][$i]['ssh_password'] = 'your-password';

// 额外 SSH 参数
$cfg['Servers'][$i]['ssh_extra_args'] = '-o StrictHostKeyChecking=no';
```

### 优先级

`ssh_tunnel` > `socks5_proxy`。如果同时配置了两者，以 SSH 隧道为准。

## 原理

```
# SOCKS5 代理模式
phpMyAdmin → Unix socket → socat → SOCKS5 proxy → MySQL server

# SSH Local Forward 模式
phpMyAdmin → Unix socket → SSH tunnel → MySQL server

# SSH Dynamic 模式
phpMyAdmin → Unix socket → socat → SSH SOCKS5 proxy → MySQL server
```

## 基于

- [phpMyAdmin 5.2.3](https://www.phpmyadmin.net/) (all-languages)

## SQLite 模块

内置 SQLite 管理模块支持：

- 浏览数据库和表
- 查看和编辑表结构
- SQL 查询编辑器（CodeMirror）
- 导出表（SQL / CSV）
- 通过 `config.inc.php` 配置多个数据库
- 中英文界面

### SQLite 配置

```php
$cfg['SQLite'][0] = [
    'verbose'  => '本地数据库',
    'mode'     => 'file',
    'path'     => '/path/to/database.sqlite',
];

$cfg['SQLite'][1] = [
    'verbose'  => '另一个数据库',
    'mode'     => 'file',
    'path'     => '/path/to/another.db',
];
```

## MongoDB 模块

内置 MongoDB 管理模块支持：

- 浏览数据库、集合和文档
- Find 和 Aggregate 查询（JSON 编辑器）
- 索引管理（创建 / 删除）
- 集合统计信息
- 导出（JSON / CSV）
- 服务器信息面板
- SSH 隧道和 SOCKS5 代理支持
- 通过 `config.inc.php` 配置多台服务器
- 中英文界面

### MongoDB 配置

```php
$cfg['MongoDB'][1] = [
    'verbose'       => '本地 MongoDB',
    'host'          => '127.0.0.1',
    'port'          => 27017,
    'username'      => '',
    'password'      => '',
    'auth_database' => 'admin',
];

// 通过 URI 连接
$cfg['MongoDB'][2] = [
    'verbose' => 'Atlas 集群',
    'uri'     => 'mongodb+srv://user:pass@cluster.mongodb.net/mydb',
];

// 通过 SSH 隧道连接
$cfg['MongoDB'][3] = [
    'verbose'    => '生产环境（经 SSH）',
    'host'       => '10.0.0.50',
    'port'       => 27017,
    'username'   => 'admin',
    'password'   => 'secret',
    'ssh_tunnel' => 'local',
    'ssh_host'   => 'jump.example.com',
    'ssh_port'   => 22,
    'ssh_user'   => 'deploy',
    'ssh_key'    => '/path/to/private_key',
];
```
