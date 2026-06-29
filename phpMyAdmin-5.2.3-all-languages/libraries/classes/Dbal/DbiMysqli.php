<?php
/**
 * Interface to the MySQL Improved extension (MySQLi)
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use mysqli;
use mysqli_stmt;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Utilities;

use function __;
use function defined;
use function escapeshellarg;
use function explode;
use function fclose;
use function file_exists;
use function fsockopen;
use function is_resource;
use function mysqli_connect_errno;
use function mysqli_connect_error;
use function mysqli_get_client_info;
use function mysqli_init;
use function mysqli_report;
use function proc_open;
use function proc_terminate;
use function register_shutdown_function;
use function sprintf;
use function stream_socket_get_name;
use function stream_socket_server;
use function stripos;
use function sys_get_temp_dir;
use function trigger_error;
use function uniqid;
use function unlink;
use function usleep;

use const E_USER_ERROR;
use const E_USER_WARNING;
use const MYSQLI_CLIENT_COMPRESS;
use const MYSQLI_CLIENT_SSL;
use const MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
use const MYSQLI_OPT_LOCAL_INFILE;
use const MYSQLI_OPT_SSL_VERIFY_SERVER_CERT;
use const MYSQLI_REPORT_OFF;
use const MYSQLI_STORE_RESULT;
use const MYSQLI_USE_RESULT;
use const PHP_VERSION_ID;

/**
 * Interface to the MySQL Improved extension (MySQLi)
 */
class DbiMysqli implements DbiExtension
{
    /** @var array{proc: resource, socket: string}[] */
    private static $socatProcesses = [];

    /** @var bool */
    private static $shutdownRegistered = false;
    /**
     * connects to the database server
     *
     * @param string $user     mysql user name
     * @param string $password mysql user password
     * @param array  $server   host/port/socket/persistent
     *
     * @return mysqli|bool false on error or a mysqli object on success
     */
    public function connect($user, $password, array $server)
    {
        if ($server) {
            $server['host'] = empty($server['host'])
                ? 'localhost'
                : $server['host'];
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        $mysqli = mysqli_init();

        if ($mysqli === false) {
            return false;
        }

        $client_flags = 0;

        /* Optionally compress connection */
        if ($server['compress'] && defined('MYSQLI_CLIENT_COMPRESS')) {
            $client_flags |= MYSQLI_CLIENT_COMPRESS;
        }

        /* Optionally enable SSL */
        if ($server['ssl']) {
            $client_flags |= MYSQLI_CLIENT_SSL;
            if (
                ! empty($server['ssl_key']) ||
                ! empty($server['ssl_cert']) ||
                ! empty($server['ssl_ca']) ||
                ! empty($server['ssl_ca_path']) ||
                ! empty($server['ssl_ciphers'])
            ) {
                $mysqli->ssl_set(
                    $server['ssl_key'] ?? '',
                    $server['ssl_cert'] ?? '',
                    $server['ssl_ca'] ?? '',
                    $server['ssl_ca_path'] ?? '',
                    $server['ssl_ciphers'] ?? ''
                );
            }

            /*
             * disables SSL certificate validation on mysqlnd for MySQL 5.6 or later
             * @link https://bugs.php.net/bug.php?id=68344
             * @link https://github.com/phpmyadmin/phpmyadmin/pull/11838
             */
            if (! $server['ssl_verify']) {
                $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, (int) $server['ssl_verify']);
                $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
        }

        if ($GLOBALS['cfg']['PersistentConnections']) {
            $host = 'p:' . $server['host'];
        } else {
            $host = $server['host'];
        }

        // SSH tunnel: launch ssh to create a tunnel before connecting
        if (! empty($server['ssh_tunnel']) && ! empty($server['ssh_host'])) {
            if ($server['ssh_tunnel'] === 'local') {
                $sshSocketPath = $this->startSshLocalTunnel($server);
                if ($sshSocketPath === false) {
                    return false;
                }

                $host = 'localhost';
                $server['socket'] = $sshSocketPath;
                $server['port'] = 0;
            } elseif ($server['ssh_tunnel'] === 'dynamic') {
                $dynamicPort = $this->startSshDynamicTunnel($server);
                if ($dynamicPort === false) {
                    return false;
                }

                $server['socks5_proxy'] = '127.0.0.1:' . $dynamicPort;
            }
        }

        // SOCKS5 proxy: launch socat to create a local Unix socket tunnel
        $socatSocketPath = null;
        if (! empty($server['socks5_proxy'])) {
            $socatSocketPath = $this->startSocks5Tunnel($server);
            if ($socatSocketPath === false) {
                return false;
            }

            $host = 'localhost';
            $server['socket'] = $socatSocketPath;
            $server['port'] = 0;
        }

        if ($server['hide_connection_errors']) {
            $return_value = @$mysqli->real_connect(
                $host,
                $user,
                $password,
                '',
                $server['port'],
                (string) $server['socket'],
                $client_flags
            );
        } else {
            $return_value = $mysqli->real_connect(
                $host,
                $user,
                $password,
                '',
                $server['port'],
                (string) $server['socket'],
                $client_flags
            );
        }

        if ($return_value === false) {
            /*
             * Switch to SSL if server asked us to do so, unfortunately
             * there are more ways MySQL server can tell this:
             *
             * - MySQL 8.0 and newer should return error 3159
             * - #2001 - SSL Connection is required. Please specify SSL options and retry.
             * - #9002 - SSL connection is required. Please specify SSL options and retry.
             */
            // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $error_number = $mysqli->connect_errno;
            $error_message = $mysqli->connect_error;
            // phpcs:enable
            if (
                ! $server['ssl']
                && ($error_number == 3159
                    || (($error_number == 2001 || $error_number == 9002)
                        && stripos($error_message, 'SSL Connection is required') !== false))
            ) {
                trigger_error(
                    __('SSL connection enforced by server, automatically enabling it.'),
                    E_USER_WARNING
                );
                $server['ssl'] = true;

                return self::connect($user, $password, $server);
            }

            if ($error_number === 1045 && $server['hide_connection_errors']) {
                trigger_error(
                    sprintf(
                        __(
                            'Error 1045: Access denied for user. Additional error information'
                            . ' may be available, but is being hidden by the %s configuration directive.'
                        ),
                        '[code][doc@cfg_Servers_hide_connection_errors]'
                        . '$cfg[\'Servers\'][$i][\'hide_connection_errors\'][/doc][/code]'
                    ),
                    PHP_VERSION_ID < 80400 ? E_USER_ERROR : E_USER_WARNING
                );
            }

            return false;
        }

        $mysqli->options(MYSQLI_OPT_LOCAL_INFILE, (int) defined('PMA_ENABLE_LDI'));

        return $mysqli;
    }

    /**
     * selects given database
     *
     * @param string|DatabaseName $databaseName database name to select
     * @param mysqli              $link         the mysqli object
     */
    public function selectDb($databaseName, $link): bool
    {
        return $link->select_db((string) $databaseName);
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query   query to execute
     * @param mysqli $link    mysqli object
     * @param int    $options query options
     *
     * @return MysqliResult|false
     */
    public function realQuery(string $query, $link, int $options)
    {
        $method = MYSQLI_STORE_RESULT;
        if ($options == ($options | DatabaseInterface::QUERY_UNBUFFERED)) {
            $method = MYSQLI_USE_RESULT;
        }

        $result = $link->query($query, $method);
        if ($result === false) {
            return false;
        }

        return new MysqliResult($result);
    }

    /**
     * Run the multi query and output the results
     *
     * @param mysqli $link  mysqli object
     * @param string $query multi query statement to execute
     */
    public function realMultiQuery($link, $query): bool
    {
        return $link->multi_query($query);
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param mysqli $link the mysqli object
     */
    public function moreResults($link): bool
    {
        return $link->more_results();
    }

    /**
     * Prepare next result from multi_query
     *
     * @param mysqli $link the mysqli object
     */
    public function nextResult($link): bool
    {
        return $link->next_result();
    }

    /**
     * Store the result returned from multi query
     *
     * @param mysqli $link the mysqli object
     *
     * @return MysqliResult|false false when empty results / result set when not empty
     */
    public function storeResult($link)
    {
        $result = $link->store_result();

        return $result === false ? false : new MysqliResult($result);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param mysqli $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link)
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $link->host_info;
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param mysqli $link mysql link
     *
     * @return string version of the MySQL protocol used
     */
    public function getProtoInfo($link)
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $link->protocol_version;
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo()
    {
        return mysqli_get_client_info();
    }

    /**
     * Returns last error message or an empty string if no errors occurred.
     *
     * @param mysqli|false|null $link mysql link
     */
    public function getError($link): string
    {
        $GLOBALS['errno'] = 0;

        if ($link !== null && $link !== false) {
            $error_number = $link->errno;
            $error_message = $link->error;
        } else {
            $error_number = mysqli_connect_errno();
            $error_message = (string) mysqli_connect_error();
        }

        if ($error_number === 0 || $error_message === '') {
            return '';
        }

        // keep the error number for further check after
        // the call to getError()
        $GLOBALS['errno'] = $error_number;

        return Utilities::formatError($error_number, $error_message);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param mysqli $link the mysqli object
     *
     * @return int|string
     * @psalm-return int|numeric-string
     */
    public function affectedRows($link)
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $link->affected_rows;
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param mysqli $link   database link
     * @param string $string string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString($link, $string)
    {
        return $link->real_escape_string($string);
    }

    /**
     * Prepare an SQL statement for execution.
     *
     * @param mysqli $link  database link
     * @param string $query The query, as a string.
     *
     * @return mysqli_stmt|false A statement object or false.
     */
    public function prepare($link, string $query)
    {
        return $link->prepare($query);
    }

    /**
     * Start a socat SOCKS5 tunnel and return the local Unix socket path.
     *
     * @param array $server server connection parameters
     *
     * @return string|false socket path on success, false on failure
     */
    private function startSocks5Tunnel(array $server)
    {
        $proxyParts = explode(':', $server['socks5_proxy']);
        $proxyHost = $proxyParts[0];
        $proxyPort = $proxyParts[1] ?? '1080';

        $mysqlHost = $server['host'];
        $mysqlPort = ! empty($server['port']) ? (int) $server['port'] : 3306;

        $socketPath = sys_get_temp_dir() . '/pma_socks5_' . uniqid('', true) . '.sock';

        $socksAddr = sprintf(
            'SOCKS5-CONNECT:%s:%s:%d,socksport=%s',
            $proxyHost,
            $mysqlHost,
            $mysqlPort,
            $proxyPort
        );

        if (! empty($server['socks5_user'])) {
            $socksAddr .= ',socksuser=' . $server['socks5_user'];
        }

        if (! empty($server['socks5_pass'])) {
            $socksAddr .= ',sockspass=' . $server['socks5_pass'];
        }

        $cmd = sprintf(
            'socat UNIX-LISTEN:%s,fork,reuseaddr %s',
            escapeshellarg($socketPath),
            escapeshellarg($socksAddr)
        );

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptorspec, $pipes);

        if (! is_resource($proc)) {
            trigger_error(
                __('Failed to start socat for SOCKS5 proxy tunnel.'),
                E_USER_WARNING
            );

            return false;
        }

        // Wait for socket file to appear
        $waited = 0;
        while (! file_exists($socketPath) && $waited < 50) {
            usleep(100000); // 100ms
            $waited++;
        }

        if (! file_exists($socketPath)) {
            proc_terminate($proc);
            trigger_error(
                __('Timeout waiting for socat SOCKS5 tunnel socket.'),
                E_USER_WARNING
            );

            return false;
        }

        self::$socatProcesses[] = ['proc' => $proc, 'socket' => $socketPath];

        if (! self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'cleanupSocatProcesses']);
            self::$shutdownRegistered = true;
        }

        return $socketPath;
    }

    public static function cleanupSocatProcesses(): void
    {
        foreach (self::$socatProcesses as $entry) {
            if (is_resource($entry['proc'])) {
                proc_terminate($entry['proc']);
            }

            if (! empty($entry['socket']) && file_exists($entry['socket'])) {
                @unlink($entry['socket']);
            }
        }

        self::$socatProcesses = [];
    }

    /**
     * Build the SSH command string with authentication and common options.
     *
     * @param array  $server     server connection parameters
     * @param string $tunnelArgs the -L or -D argument string
     *
     * @return string full command string
     */
    private function buildSshCommand(array $server, string $tunnelArgs): string
    {
        $sshPort = ! empty($server['ssh_port']) ? (int) $server['ssh_port'] : 22;

        $cmd = 'ssh -N -o ExitOnForwardFailure=yes -o StrictHostKeyChecking=accept-new';
        $cmd .= ' ' . $tunnelArgs;
        $cmd .= ' -p ' . $sshPort;

        if (! empty($server['ssh_key'])) {
            $cmd .= ' -i ' . escapeshellarg($server['ssh_key']);
        }

        if (! empty($server['ssh_extra_args'])) {
            $cmd .= ' ' . $server['ssh_extra_args'];
        }

        $cmd .= ' ' . escapeshellarg($server['ssh_user'] . '@' . $server['ssh_host']);

        if (! empty($server['ssh_password'])) {
            $cmd = 'sshpass -p ' . escapeshellarg($server['ssh_password']) . ' ' . $cmd;
        }

        return $cmd;
    }

    /**
     * Start an SSH local forward tunnel and return the local Unix socket path.
     *
     * @param array $server server connection parameters
     *
     * @return string|false socket path on success, false on failure
     */
    private function startSshLocalTunnel(array $server)
    {
        $mysqlHost = $server['host'];
        $mysqlPort = ! empty($server['port']) ? (int) $server['port'] : 3306;

        $socketPath = sys_get_temp_dir() . '/pma_ssh_local_' . uniqid('', true) . '.sock';

        $tunnelArgs = sprintf(
            '-L %s:%s:%d',
            escapeshellarg($socketPath),
            escapeshellarg($mysqlHost),
            $mysqlPort
        );

        $cmd = $this->buildSshCommand($server, $tunnelArgs);

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptorspec, $pipes);

        if (! is_resource($proc)) {
            trigger_error(
                __('Failed to start SSH local forward tunnel.'),
                E_USER_WARNING
            );

            return false;
        }

        $waited = 0;
        while (! file_exists($socketPath) && $waited < 50) {
            usleep(100000);
            $waited++;
        }

        if (! file_exists($socketPath)) {
            proc_terminate($proc);
            trigger_error(
                __('Timeout waiting for SSH local forward tunnel socket.'),
                E_USER_WARNING
            );

            return false;
        }

        self::$socatProcesses[] = ['proc' => $proc, 'socket' => $socketPath];

        if (! self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'cleanupSocatProcesses']);
            self::$shutdownRegistered = true;
        }

        return $socketPath;
    }

    /**
     * Start an SSH dynamic SOCKS5 tunnel and return the local port number.
     *
     * @param array $server server connection parameters
     *
     * @return int|false local port on success, false on failure
     */
    private function startSshDynamicTunnel(array $server)
    {
        // Find a free port
        $sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($sock === false) {
            trigger_error(
                __('Failed to find a free port for SSH dynamic tunnel.'),
                E_USER_WARNING
            );

            return false;
        }

        $localAddr = stream_socket_get_name($sock, false);
        fclose($sock);
        $localPort = (int) explode(':', $localAddr)[1];

        $tunnelArgs = sprintf('-D 127.0.0.1:%d', $localPort);

        $cmd = $this->buildSshCommand($server, $tunnelArgs);

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptorspec, $pipes);

        if (! is_resource($proc)) {
            trigger_error(
                __('Failed to start SSH dynamic SOCKS5 tunnel.'),
                E_USER_WARNING
            );

            return false;
        }

        // Wait for the SOCKS5 port to become reachable
        $waited = 0;
        while ($waited < 50) {
            $fp = @fsockopen('127.0.0.1', $localPort, $errno, $errstr, 0.1);
            if ($fp !== false) {
                fclose($fp);
                break;
            }

            usleep(100000);
            $waited++;
        }

        if ($waited >= 50) {
            proc_terminate($proc);
            trigger_error(
                __('Timeout waiting for SSH dynamic SOCKS5 tunnel.'),
                E_USER_WARNING
            );

            return false;
        }

        self::$socatProcesses[] = ['proc' => $proc, 'socket' => ''];

        if (! self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'cleanupSocatProcesses']);
            self::$shutdownRegistered = true;
        }

        return $localPort;
    }
}
