<?php
declare(strict_types=1);

class MongoTunnel
{
    public static function tunnelId(string $prefix, array $parts): string
    {
        return $prefix . '_' . md5(implode('|', $parts));
    }

    public static function isProcessAlive(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        return posix_kill($pid, 0);
    }

    /**
     * Start SSH local forward tunnel, returning a local TCP port.
     * MongoDB Driver needs TCP, not Unix sockets.
     */
    public static function startSshLocalForward(array $config): ?array
    {
        $mongoHost = $config['host'];
        $mongoPort = (int) ($config['port'] ?: 27017);

        $id = self::tunnelId('pma_mongo_ssh_local', [
            $config['ssh_host'], (string) ($config['ssh_port'] ?? 22),
            $config['ssh_user'], $mongoHost, (string) $mongoPort,
        ]);
        $pidFile = sys_get_temp_dir() . '/' . $id . '.pid';
        $portFile = sys_get_temp_dir() . '/' . $id . '.port';

        // Reuse existing tunnel
        if (file_exists($pidFile) && file_exists($portFile)) {
            $pid = (int) file_get_contents($pidFile);
            $port = (int) file_get_contents($portFile);
            if (self::isProcessAlive($pid) && $port > 0) {
                $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);
                if ($fp !== false) {
                    fclose($fp);
                    return ['host' => '127.0.0.1', 'port' => $port];
                }
            }
            @unlink($pidFile);
            @unlink($portFile);
        }

        // Find free port
        $sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($sock === false) {
            return null;
        }
        $localAddr = stream_socket_get_name($sock, false);
        fclose($sock);
        $localPort = (int) explode(':', $localAddr)[1];

        $tunnelArgs = sprintf('-L 127.0.0.1:%d:%s:%d', $localPort, escapeshellarg($mongoHost), $mongoPort);
        $cmd = self::buildSshCommand($config, $tunnelArgs);
        shell_exec($cmd);

        // Wait for port
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
            return null;
        }

        // Find PID
        $pid = (int) trim((string) shell_exec(
            'pgrep -f ' . escapeshellarg('127.0.0.1:' . $localPort) . ' 2>/dev/null | head -1'
        ));
        if ($pid > 0) {
            file_put_contents($pidFile, (string) $pid);
        }
        file_put_contents($portFile, (string) $localPort);

        return ['host' => '127.0.0.1', 'port' => $localPort];
    }

    /**
     * Start SSH dynamic SOCKS5 tunnel + socat bridge for MongoDB.
     */
    public static function startSshDynamic(array $config): ?array
    {
        $mongoHost = $config['host'];
        $mongoPort = (int) ($config['port'] ?: 27017);

        // Step 1: Start SSH -D to get a SOCKS5 port
        $sshId = self::tunnelId('pma_mongo_ssh_dyn', [
            $config['ssh_host'], (string) ($config['ssh_port'] ?? 22),
            $config['ssh_user'],
        ]);
        $sshPidFile = sys_get_temp_dir() . '/' . $sshId . '.pid';
        $sshPortFile = sys_get_temp_dir() . '/' . $sshId . '.port';
        $socksPort = 0;

        if (file_exists($sshPidFile) && file_exists($sshPortFile)) {
            $pid = (int) file_get_contents($sshPidFile);
            $socksPort = (int) file_get_contents($sshPortFile);
            if (!self::isProcessAlive($pid) || $socksPort <= 0) {
                @unlink($sshPidFile);
                @unlink($sshPortFile);
                $socksPort = 0;
            }
        }

        if ($socksPort === 0) {
            $sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
            if ($sock === false) {
                return null;
            }
            $localAddr = stream_socket_get_name($sock, false);
            fclose($sock);
            $socksPort = (int) explode(':', $localAddr)[1];

            $tunnelArgs = sprintf('-D 127.0.0.1:%d', $socksPort);
            $cmd = self::buildSshCommand($config, $tunnelArgs);
            shell_exec($cmd);

            // Wait for SOCKS port
            $waited = 0;
            while ($waited < 50) {
                $fp = @fsockopen('127.0.0.1', $socksPort, $errno, $errstr, 0.1);
                if ($fp !== false) {
                    fclose($fp);
                    break;
                }
                usleep(100000);
                $waited++;
            }
            if ($waited >= 50) {
                return null;
            }

            $pid = (int) trim((string) shell_exec(
                'pgrep -f ' . escapeshellarg('127.0.0.1:' . $socksPort) . ' 2>/dev/null | head -1'
            ));
            if ($pid > 0) {
                file_put_contents($sshPidFile, (string) $pid);
            }
            file_put_contents($sshPortFile, (string) $socksPort);
        }

        // Step 2: socat bridge from local TCP port through SOCKS5 to MongoDB
        return self::startSocatBridge('127.0.0.1', $socksPort, $mongoHost, $mongoPort, '', '');
    }

    /**
     * Start socat TCP bridge through a SOCKS5 proxy.
     */
    public static function startSocks5Bridge(array $config): ?array
    {
        $proxyParts = explode(':', $config['socks5_proxy']);
        $proxyHost = $proxyParts[0];
        $proxyPort = (int) ($proxyParts[1] ?? 1080);
        $mongoHost = $config['host'];
        $mongoPort = (int) ($config['port'] ?: 27017);
        $user = $config['socks5_user'] ?? '';
        $pass = $config['socks5_pass'] ?? '';

        return self::startSocatBridge($proxyHost, $proxyPort, $mongoHost, $mongoPort, $user, $pass);
    }

    private static function startSocatBridge(
        string $proxyHost,
        int $proxyPort,
        string $targetHost,
        int $targetPort,
        string $user,
        string $pass
    ): ?array {
        $id = self::tunnelId('pma_mongo_socat', [$proxyHost, (string) $proxyPort, $targetHost, (string) $targetPort]);
        $pidFile = sys_get_temp_dir() . '/' . $id . '.pid';
        $portFile = sys_get_temp_dir() . '/' . $id . '.port';

        // Reuse existing
        if (file_exists($pidFile) && file_exists($portFile)) {
            $pid = (int) file_get_contents($pidFile);
            $port = (int) file_get_contents($portFile);
            if (self::isProcessAlive($pid) && $port > 0) {
                $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);
                if ($fp !== false) {
                    fclose($fp);
                    return ['host' => '127.0.0.1', 'port' => $port];
                }
            }
            @unlink($pidFile);
            @unlink($portFile);
        }

        // Find free port for local listen
        $sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($sock === false) {
            return null;
        }
        $localAddr = stream_socket_get_name($sock, false);
        fclose($sock);
        $localPort = (int) explode(':', $localAddr)[1];

        $socksAddr = sprintf(
            'SOCKS5-CONNECT:%s:%s:%d,socksport=%d',
            $proxyHost,
            $targetHost,
            $targetPort,
            $proxyPort
        );
        if ($user !== '') {
            $socksAddr .= ',socksuser=' . $user;
        }
        if ($pass !== '') {
            $socksAddr .= ',sockspass=' . $pass;
        }

        $cmd = sprintf(
            'socat TCP-LISTEN:%d,fork,reuseaddr,bind=127.0.0.1 %s >/dev/null 2>&1 & echo $!',
            $localPort,
            escapeshellarg($socksAddr)
        );

        $pid = (int) trim((string) shell_exec($cmd));
        if ($pid <= 0) {
            return null;
        }

        file_put_contents($pidFile, (string) $pid);
        file_put_contents($portFile, (string) $localPort);

        // Wait for port
        $waited = 0;
        while ($waited < 30) {
            $fp = @fsockopen('127.0.0.1', $localPort, $errno, $errstr, 0.1);
            if ($fp !== false) {
                fclose($fp);
                return ['host' => '127.0.0.1', 'port' => $localPort];
            }
            usleep(100000);
            $waited++;
        }

        return null;
    }

    public static function buildSshCommand(array $config, string $tunnelArgs): string
    {
        $sshPort = (int) ($config['ssh_port'] ?: 22);

        $cmd = 'ssh -N -f'
            . ' -o ExitOnForwardFailure=yes'
            . ' -o StrictHostKeyChecking=accept-new'
            . ' -o ConnectTimeout=10'
            . ' -o ServerAliveInterval=60'
            . ' -o ServerAliveCountMax=3';
        $cmd .= ' ' . $tunnelArgs;
        $cmd .= ' -p ' . $sshPort;

        if (!empty($config['ssh_key'])) {
            $cmd .= ' -i ' . escapeshellarg($config['ssh_key']);
        }
        if (!empty($config['ssh_extra_args'])) {
            $cmd .= ' ' . $config['ssh_extra_args'];
        }

        $cmd .= ' ' . escapeshellarg($config['ssh_user'] . '@' . $config['ssh_host']);

        if (!empty($config['ssh_password'])) {
            $cmd = 'sshpass -p ' . escapeshellarg($config['ssh_password']) . ' ' . $cmd;
        }

        return $cmd;
    }

    /**
     * Resolve the effective host/port for MongoDB connection based on tunnel config.
     */
    public static function resolve(array $config): array
    {
        if (!empty($config['ssh_tunnel'])) {
            if ($config['ssh_tunnel'] === 'local') {
                $result = self::startSshLocalForward($config);
                if ($result) {
                    return $result;
                }
                throw new \RuntimeException('Failed to establish SSH local forward tunnel');
            } elseif ($config['ssh_tunnel'] === 'dynamic') {
                $result = self::startSshDynamic($config);
                if ($result) {
                    return $result;
                }
                throw new \RuntimeException('Failed to establish SSH dynamic tunnel');
            }
        }

        if (!empty($config['socks5_proxy'])) {
            $result = self::startSocks5Bridge($config);
            if ($result) {
                return $result;
            }
            throw new \RuntimeException('Failed to establish SOCKS5 proxy bridge');
        }

        return ['host' => $config['host'], 'port' => (int) ($config['port'] ?: 27017)];
    }
}
