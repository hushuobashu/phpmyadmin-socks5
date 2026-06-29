# phpmyadmin-socks5

基于 phpMyAdmin 5.2.3 的修改版本，增加了通过 SOCKS5 代理和 SSH 隧道连接 MySQL 的支持。

## 功能

- **SOCKS5 代理** — 通过 socat 建立 SOCKS5 隧道连接 MySQL
- **SSH Local Forward** — 通过 SSH 端口转发直连远程 MySQL
- **SSH Dynamic (SOCKS5)** — 通过 SSH 动态代理建立 SOCKS5 隧道
- 支持 SOCKS5 用户名/密码认证
- 支持 SSH 私钥认证和密码认证
- 请求结束后自动清理隧道进程和临时 socket 文件
- 对 phpMyAdmin 其他功能无侵入，不配置时行为与原版完全一致

## 依赖

- PHP 7.2+
- `socat` 1.8+（SOCKS5 代理模式和 SSH dynamic 模式需要）
- `ssh`（SSH 隧道模式需要，系统自带）
- `sshpass`（仅 SSH 密码认证时需要）

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
