<?php
// database.php

class Database
{
    private static $connections = [
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'test2',
                'username' => 'root',
                'password' => 'root',
                'charset' => 'utf8mb4',
            ],
            '101' => [
                'driver' => 'sqlsrv',
                'host' => 'localhost',
                'port' => '1433',
                'database' => 'forge',
                'username' => 'forge',
                'password' => '',
                'charset' => 'utf8',
            ],
            'log' => [
                'driver' => 'sqlsrv',
                'host' => 'localhost',
                'port' => '1433',
                'database' => 'forge',
                'username' => 'forge',
                'password' => '',
                'charset' => 'utf8',
            ],
            '014' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.24',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '009' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.19',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '010' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.20',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '011' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.21',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '017' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.27',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '019' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.29',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '005' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.15',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '015' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.25',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '016' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.26',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '004' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.14',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
            '021' => [
                'driver' => 'sqlsrv',
                'host' => '10.0.0.31',
                'port' => '1433',
                'database' => 'MicroFinance',
                'username' => 'kiriji',
                'password' => 'q4M@i93v',
                'charset' => 'utf8',
            ],
        ],
    ];

    public static function getConnection($bankId)
    {
        if (!isset(self::$connections['connections'][$bankId])) {
            throw new Exception("Invalid bank ID");
        }

        $config = self::$connections['connections'][$bankId];
        $dsn = self::buildDsn($config);

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    private static function buildDsn($config)
    {
        if ($config['driver'] === 'mysql') {
            return "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        } elseif ($config['driver'] === 'sqlsrv') {
            return "sqlsrv:Server={$config['host']},{$config['port']};Database={$config['database']}";
        } else {
            throw new Exception("Unsupported database driver");
        }
    }
}
