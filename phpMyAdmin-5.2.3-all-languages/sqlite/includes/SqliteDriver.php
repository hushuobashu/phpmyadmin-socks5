<?php
declare(strict_types=1);

interface SqliteDriverInterface
{
    public function listFiles(string $directory): array;
    public function query(string $dbPath, string $sql): array;
    public function exec(string $dbPath, string $sql): int;
    public function tableList(string $dbPath): array;
    public function tableInfo(string $dbPath, string $table): array;
    public function indexList(string $dbPath, string $table): array;
    public function createDatabase(string $dbPath): void;
}

class SqliteLocalDriver implements SqliteDriverInterface
{
    public function listFiles(string $directory): array
    {
        $files = [];
        $patterns = ['*.db', '*.sqlite', '*.sqlite3'];

        foreach ($patterns as $pattern) {
            $matches = glob($directory . '/' . $pattern);
            if ($matches === false) {
                continue;
            }
            foreach ($matches as $path) {
                $files[] = [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => (int) filesize($path),
                ];
            }
        }

        usort($files, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $files;
    }

    public function query(string $dbPath, string $sql): array
    {
        $db = new \SQLite3($dbPath, SQLITE3_OPEN_READONLY);
        $db->enableExceptions(true);

        $result = $db->query($sql);
        $rows = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        $result->finalize();
        $db->close();

        return $rows;
    }

    public function exec(string $dbPath, string $sql): int
    {
        $db = new \SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
        $db->enableExceptions(true);
        $db->exec($sql);
        $changes = $db->changes();
        $db->close();

        return $changes;
    }

    public function tableList(string $dbPath): array
    {
        return $this->query($dbPath,
            "SELECT name, type FROM sqlite_master WHERE type IN ('table','view') AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );
    }

    public function tableInfo(string $dbPath, string $table): array
    {
        return $this->query($dbPath, 'PRAGMA table_info(' . $this->quoteIdentifier($table) . ')');
    }

    public function indexList(string $dbPath, string $table): array
    {
        return $this->query($dbPath, 'PRAGMA index_list(' . $this->quoteIdentifier($table) . ')');
    }

    public function createDatabase(string $dbPath): void
    {
        if (file_exists($dbPath)) {
            throw new \RuntimeException('Database file already exists: ' . basename($dbPath));
        }

        $dir = dirname($dbPath);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \RuntimeException('Directory is not writable: ' . $dir);
        }

        $db = new \SQLite3($dbPath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $db->close();
    }

    private function quoteIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}

class SqliteSshDriver implements SqliteDriverInterface
{
    private string $host;
    private int $port;
    private string $user;
    private string $key;
    private string $password;
    private string $proxy;

    public function __construct(array $config)
    {
        $this->host = $config['ssh_host'];
        $this->port = (int) ($config['ssh_port'] ?: 22);
        $this->user = $config['ssh_user'];
        $this->key = $config['ssh_key'] ?? '';
        $this->password = $config['ssh_password'] ?? '';
        $this->proxy = $config['ssh_proxy'] ?? '';
    }

    public function listFiles(string $directory): array
    {
        $remoteCmd = sprintf(
            'find %s -maxdepth 1 \\( -name "*.db" -o -name "*.sqlite" -o -name "*.sqlite3" \\) -type f 2>/dev/null',
            escapeshellarg($directory)
        );

        // Get file sizes with stat - use portable format
        $remoteCmd .= ' -exec ls -ln {} \\; 2>/dev/null';

        $output = $this->sshExec($remoteCmd);
        $files = [];

        foreach (explode("\n", trim($output)) as $line) {
            if (empty($line)) {
                continue;
            }
            // Parse ls -ln output: permissions links owner group size month day time filename
            $parts = preg_split('/\s+/', $line, 9);
            if (count($parts) >= 9) {
                $path = $parts[8];
                $size = (int) $parts[4];
                $files[] = [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => $size,
                ];
            }
        }

        usort($files, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $files;
    }

    public function query(string $dbPath, string $sql): array
    {
        $b64 = base64_encode($sql);
        $remoteCmd = sprintf(
            'echo %s | base64 -d | sqlite3 -json %s',
            escapeshellarg($b64),
            escapeshellarg($dbPath)
        );

        $output = $this->sshExec($remoteCmd);
        $trimmed = trim($output);

        if ($trimmed === '' || $trimmed === '[]') {
            return [];
        }

        $result = json_decode($trimmed, true);
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse sqlite3 JSON output: ' . $trimmed);
        }

        return $result ?: [];
    }

    public function exec(string $dbPath, string $sql): int
    {
        $fullSql = $sql . ";\nSELECT changes();";
        $b64 = base64_encode($fullSql);
        $remoteCmd = sprintf(
            'echo %s | base64 -d | sqlite3 %s',
            escapeshellarg($b64),
            escapeshellarg($dbPath)
        );

        $output = $this->sshExec($remoteCmd);
        $lines = array_filter(explode("\n", trim($output)), function ($l) {
            return $l !== '';
        });

        $lastLine = end($lines);

        return (int) $lastLine;
    }

    public function tableList(string $dbPath): array
    {
        return $this->query($dbPath,
            "SELECT name, type FROM sqlite_master WHERE type IN ('table','view') AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );
    }

    public function tableInfo(string $dbPath, string $table): array
    {
        return $this->query($dbPath, 'PRAGMA table_info("' . str_replace('"', '""', $table) . '")');
    }

    public function indexList(string $dbPath, string $table): array
    {
        return $this->query($dbPath, 'PRAGMA index_list("' . str_replace('"', '""', $table) . '")');
    }

    public function createDatabase(string $dbPath): void
    {
        $checkCmd = sprintf('test -f %s && echo EXISTS || echo OK', escapeshellarg($dbPath));
        $result = trim($this->sshExec($checkCmd));
        if ($result === 'EXISTS') {
            throw new \RuntimeException('Database file already exists: ' . basename($dbPath));
        }

        $createCmd = sprintf('sqlite3 %s "SELECT 1;" && echo OK', escapeshellarg($dbPath));
        $output = trim($this->sshExec($createCmd));
        if (strpos($output, 'OK') === false) {
            throw new \RuntimeException('Failed to create database: ' . $output);
        }
    }

    private function sshExec(string $remoteCmd): string
    {
        $cmd = $this->buildSshPrefix() . ' ' . escapeshellarg($remoteCmd);

        $output = shell_exec($cmd . ' 2>&1');

        if ($output === null) {
            throw new \RuntimeException('SSH command execution failed');
        }

        return $output;
    }

    private function buildSshPrefix(): string
    {
        $controlPath = '/tmp/pma_sqlite_ssh_' . md5($this->host . ':' . $this->port . ':' . $this->user);

        $cmd = 'ssh'
            . ' -o ControlMaster=auto'
            . ' -o ControlPath=' . escapeshellarg($controlPath)
            . ' -o ControlPersist=300'
            . ' -o StrictHostKeyChecking=accept-new'
            . ' -o ConnectTimeout=10'
            . ' -p ' . $this->port;

        if ($this->key !== '') {
            $cmd .= ' -i ' . escapeshellarg($this->key);
        }
        if ($this->proxy !== '') {
            $cmd .= ' -J ' . escapeshellarg($this->proxy);
        }

        $cmd .= ' ' . escapeshellarg($this->user . '@' . $this->host);

        if ($this->password !== '') {
            $cmd = 'sshpass -p ' . escapeshellarg($this->password) . ' ' . $cmd;
        }

        return $cmd;
    }
}
