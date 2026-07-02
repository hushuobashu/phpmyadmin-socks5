# phpmyadmin-socks5

A modified version of phpMyAdmin 5.2.3 with SOCKS5 proxy and SSH tunnel support for MySQL connections, plus built-in SQLite and MongoDB admin modules.

[中文文档](README_zh.md)

## Features

- **SOCKS5 Proxy** — Connect to MySQL through a SOCKS5 proxy via socat
- **SSH Local Forward** — Direct MySQL access via SSH port forwarding
- **SSH Dynamic (SOCKS5)** — SSH dynamic proxy as a SOCKS5 tunnel
- SOCKS5 username/password authentication
- SSH private key and password authentication
- Persistent tunnels — SSH/socat processes survive across HTTP requests, automatically reused for the same connection config
- **SQLite Admin** — Built-in SQLite database browser with query, export, and structure management
- **MongoDB Admin** — Built-in MongoDB admin panel with query, indexes, export, and server info
- Multi-language support (English / Chinese) for SQLite and MongoDB modules
- Non-invasive — behaves exactly like stock phpMyAdmin when proxy/tunnel is not configured

## Quick Start

Use PHP's built-in web server for local development:

```bash
cd phpMyAdmin-5.2.3-all-languages
php -S localhost:9999
```

Then open in your browser:

- MySQL (phpMyAdmin): http://localhost:9999/
- SQLite Admin: http://localhost:9999/sqlite/
- MongoDB Admin: http://localhost:9999/mongodb/

## Requirements

- PHP 7.2+
- `socat` 1.8+ (required for SOCKS5 proxy mode and SSH dynamic mode)
- `ssh` (required for SSH tunnel mode, usually pre-installed)
- `sshpass` (only required for SSH password authentication)
- PHP `sqlite3` extension (for SQLite module)
- PHP `mongodb` extension (for MongoDB module)

## Configuration

Add the following to your `config.inc.php`.

### Method 1: SOCKS5 Proxy

For scenarios where a SOCKS5 proxy server is already available.

```php
$cfg['Servers'][$i]['host'] = 'your-mysql-host';
$cfg['Servers'][$i]['port'] = '3306';

$cfg['Servers'][$i]['socks5_proxy'] = '127.0.0.1:1080';
$cfg['Servers'][$i]['socks5_user'] = '';    // optional
$cfg['Servers'][$i]['socks5_pass'] = '';    // optional
```

### Method 2: SSH Local Forward

For accessing MySQL on an internal network through a jump host. The most direct and efficient approach.

```php
$cfg['Servers'][$i]['host'] = '10.0.0.100';       // target MySQL (reachable from jump host)
$cfg['Servers'][$i]['port'] = '3306';

$cfg['Servers'][$i]['ssh_tunnel'] = 'local';
$cfg['Servers'][$i]['ssh_host'] = 'jump.example.com';
$cfg['Servers'][$i]['ssh_port'] = 22;
$cfg['Servers'][$i]['ssh_user'] = 'deploy';
$cfg['Servers'][$i]['ssh_key'] = '/path/to/private_key';
```

### Method 3: SSH Dynamic (SOCKS5)

For scenarios requiring a dynamic proxy through a jump host.

```php
$cfg['Servers'][$i]['host'] = '10.0.0.100';
$cfg['Servers'][$i]['port'] = '3306';

$cfg['Servers'][$i]['ssh_tunnel'] = 'dynamic';
$cfg['Servers'][$i]['ssh_host'] = 'jump.example.com';
$cfg['Servers'][$i]['ssh_port'] = 22;
$cfg['Servers'][$i]['ssh_user'] = 'deploy';
$cfg['Servers'][$i]['ssh_password'] = 'mypassword';   // requires sshpass
```

### SSH Authentication

```php
// Private key (recommended)
$cfg['Servers'][$i]['ssh_key'] = '/home/www/.ssh/id_rsa';

// Password (requires sshpass)
$cfg['Servers'][$i]['ssh_password'] = 'your-password';

// Extra SSH arguments
$cfg['Servers'][$i]['ssh_extra_args'] = '-o StrictHostKeyChecking=no';
```

### Priority

`ssh_tunnel` > `socks5_proxy`. If both are configured, the SSH tunnel takes precedence.

## How It Works

```
# SOCKS5 proxy mode
phpMyAdmin → Unix socket → socat → SOCKS5 proxy → MySQL server

# SSH Local Forward mode
phpMyAdmin → Unix socket → SSH tunnel → MySQL server

# SSH Dynamic mode
phpMyAdmin → Unix socket → socat → SSH SOCKS5 proxy → MySQL server
```

## Based On

- [phpMyAdmin 5.2.3](https://www.phpmyadmin.net/) (all-languages)

## SQLite Module

The built-in SQLite admin module supports:

- Browse databases and tables
- View and edit table structure
- Query with SQL editor (CodeMirror)
- Export tables (SQL / CSV)
- Multi-server support via `config.inc.php`
- English / Chinese UI

### SQLite Configuration

```php
$cfg['SQLite'][0] = [
    'verbose'  => 'My Local DB',
    'mode'     => 'file',
    'path'     => '/path/to/database.sqlite',
];

$cfg['SQLite'][1] = [
    'verbose'  => 'Another DB',
    'mode'     => 'file',
    'path'     => '/path/to/another.db',
];
```

## MongoDB Module

The built-in MongoDB admin module supports:

- Browse databases, collections, and documents
- Query with Find and Aggregate (JSON editor)
- Index management (create / drop)
- Collection statistics
- Export (JSON / CSV)
- Server info dashboard
- SSH tunnel and SOCKS5 proxy support
- Multi-server support via `config.inc.php`
- English / Chinese UI

### MongoDB Configuration

```php
$cfg['MongoDB'][1] = [
    'verbose'       => 'Local MongoDB',
    'host'          => '127.0.0.1',
    'port'          => 27017,
    'username'      => '',
    'password'      => '',
    'auth_database' => 'admin',
];

// Connect via URI
$cfg['MongoDB'][2] = [
    'verbose' => 'Atlas Cluster',
    'uri'     => 'mongodb+srv://user:pass@cluster.mongodb.net/mydb',
];

// Connect via SSH tunnel
$cfg['MongoDB'][3] = [
    'verbose'    => 'Production (via SSH)',
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
