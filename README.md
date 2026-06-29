# phpmyadmin-socks5

基于 phpMyAdmin 5.2.3 的修改版本，增加了通过 SOCKS5 代理连接 MySQL 的支持。

## 功能

- 支持为每个 MySQL 服务器实例配置 SOCKS5 代理
- 支持 SOCKS5 用户名/密码认证
- 基于 socat 的 SOCKS5-CONNECT 隧道，对 phpMyAdmin 其他功能无侵入
- 请求结束后自动清理隧道进程和临时 socket 文件

## 依赖

- PHP 7.2+
- socat 1.8+（需支持 `SOCKS5-CONNECT` 地址类型）

## 配置

在 `config.inc.php` 中添加：

```php
$cfg['Servers'][$i]['host'] = 'your-mysql-host';
$cfg['Servers'][$i]['port'] = '3306';

// SOCKS5 代理配置
$cfg['Servers'][$i]['socks5_proxy'] = '127.0.0.1:1080';  // 代理地址:端口
$cfg['Servers'][$i]['socks5_user'] = '';                   // 可选，代理认证用户名
$cfg['Servers'][$i]['socks5_pass'] = '';                   // 可选，代理认证密码
```

不配置 `socks5_proxy` 时行为与原版 phpMyAdmin 完全一致。

## 原理

当检测到 `socks5_proxy` 配置时，phpMyAdmin 会在连接 MySQL 前通过 socat 建立一个本地 Unix socket 隧道：

```
phpMyAdmin → Unix socket → socat → SOCKS5 proxy → MySQL server
```

## 基于

- [phpMyAdmin 5.2.3](https://www.phpmyadmin.net/) (all-languages)
