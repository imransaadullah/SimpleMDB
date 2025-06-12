<?php
namespace SimpleMDB;

use Exception;
use PDO;
use PDOException;

/**
 * Class SimplePDO
 * PDO implementation of SimpleMySQLi with identical interface
 */
class SimplePDO implements DatabaseInterface
{
    private $pdo;
    private $stmt;
    private $defaultFetchType;
    private const ALLOWED_FETCH_TYPES_BOTH = [
        'assoc',
        'obj',
        'num',
        'col'
    ];
    private const ALLOWED_FETCH_TYPES_FETCH_ALL = [
        'keyPair',
        'keyPairArr',
        'group',
        'groupCol',
        'groupObj'
    ];

    /**
     * SimplePDO constructor with SSL support
     *
     * @param string $host Hostname or IP address
     * @param string $username Database username
     * @param string $password Database password
     * @param string $dbName Database name
     * @param string $charset (optional) Default character encoding
     * @param string $defaultFetchType (optional) Default fetch type
     * @param array $sslOptions (optional) SSL configuration options:
     *               'enable' => bool (true to enable SSL)
     *               'key' => string (path to SSL key file)
     *               'cert' => string (path to SSL certificate file)
     *               'ca' => string (path to SSL CA certificate file)
     *               'verify_cert' => bool (true to verify server certificate)
     * @throws SimplePDOException If connection fails or invalid SSL config
     */
    public function __construct(
        string $host, 
        string $username, 
        string $password, 
        string $dbName, 
        string $charset = 'utf8mb4', 
        string $defaultFetchType = 'assoc',
        array $sslOptions = []
    ) {
        $this->defaultFetchType = $defaultFetchType;

        if (!in_array($defaultFetchType, self::ALLOWED_FETCH_TYPES_BOTH, true)) {
            $allowedComma = implode("','", self::ALLOWED_FETCH_TYPES_BOTH);
            throw new SimplePDOException("The variable 'defaultFetchType' must be '$allowedComma'. You entered '$defaultFetchType'");
        }

        $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Add SSL options if enabled
        if (!empty($sslOptions['enable'])) {
            $ssl = [
                PDO::MYSQL_ATTR_SSL_CA => $sslOptions['ca'] ?? null,
                // PDO::MYSQL_ATTR_SSL_CERT => $sslOptions['cert'] ?? null,
                // PDO::MYSQL_ATTR_SSL_KEY => $sslOptions['key'] ?? null,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => $sslOptions['verify_cert'] ?? true,
            ];

            // Remove null values
            $ssl = array_filter($ssl, function($value) {
                return $value !== null;
            });

            $options = array_merge($options, $ssl);
        }

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
            
            // Verify SSL if enabled
            if (!empty($sslOptions['enable'])) {
                $stmt = $this->pdo->query("SHOW STATUS LIKE 'Ssl_cipher'");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (empty($result['Value'])) {
                    throw new SimplePDOException("SSL connection could not be established");
                }
            }
        } catch (PDOException $e) {
            throw new SimplePDOException("Connection failed: " . $e->getMessage());
        }
    }

    public function getPDO() {
        return $this->pdo;
    }

    public function instance(): mixed
    {
        return $this->pdo;
    }


    /**
     * Prepare an SQL query
     */
    public function prepare(string $sql): SimplePDO
    {
        $this->stmt = $this->pdo->prepare($sql);
        return $this;
    }

    /**
     * Generate insert statement
     */
    public static function insert(string $table, array $data): array | bool
    {
        if (empty($data) || empty($table)) {
            return false;
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        return [
            'sql' => "INSERT INTO $table ($fields) VALUES ($placeholders)",
            'values' => array_values($data)
        ];
    }

    /**
     * Generate select statement
     */
    public static function select_all_query(string $table, array $fields = [], string $adjuncts = ""): array
    {
        $sql = "SELECT " . (empty($fields) ? '*' : implode(', ', $fields)) . " FROM $table";
        if ($adjuncts) {
            $sql .= " $adjuncts";
        }
        return ['sql' => $sql];
    }

    /**
     * Update data
     */
    public function update(string $table, array $data = [], string $adjuncts = "", array $adjunctValues = []): self | bool
    {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "$key = ?";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE $table SET $setClause";
        if ($adjuncts) {
            $sql .= " $adjuncts";
        }
        
        $values = array_merge(array_values($data), $adjunctValues);
        $this->query($sql, $values);
        
        return $this->affectedRows() > 0 ? $this : false;
    }

    /**
     * Generate update statement without executing
     */
    public function update2(string $table, array $data = [], string $adjuncts = "", array $adjunctValues = []): array
    {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "$key = ?";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE $table SET $setClause";
        if ($adjuncts) {
            $sql .= " $adjuncts";
        }
        
        $values = array_merge(array_values($data), $adjunctValues);
        return ['sql' => $sql, 'values' => $values];
    }

    /**
     * Read single row
     */
    public function read_data(string $table, array $fields = [], string $adjunct = "", array $adjunctValues = [])
    {
        $sql = self::select_all_query($table, $fields, $adjunct);
        $stmt = $this->query($sql['sql'], $adjunctValues);
        return $stmt->fetch('assoc');
    }

    /**
     * Read multiple rows
     */
    public function read_data_all(string $table, array $fields = [], string $adjunct = "", array $adjunctValues = []): array | bool
    {
        $sql = self::select_all_query($table, $fields, $adjunct);
        $stmt = $this->query($sql['sql'], $adjunctValues);
        $result = $stmt->fetchAll('assoc');
        return $result ?: false;
    }

    /**
     * Write data
     */
    public function write_data(string $table, array $data): self | bool
    {
        $sql = self::insert($table, $data);
        $this->query($sql['sql'], $sql['values']);
        return $this->affectedRows() > 0 ? $this : false;
    }

    /**
     * Execute query
     */
    public function query(string $sql, array|string|int $values = [], string $types = ''): self
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        $this->stmt = $this->pdo->prepare($sql);
        $this->stmt->execute($values);
        return $this;
    }

    /**
     * Execute prepared statement
     */
    public function execute(array $values = []): self
    {
        $this->stmt->execute($values);
        return $this;
    }

    /**
     * Generate placeholders for IN clause
     */
    public function whereIn(array $inArr): string
    {
        return implode(',', array_fill(0, count($inArr), '?'));
    }

    /**
     * Get row count
     */
    public function numRows(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Get affected rows
     */
    public function affectedRows(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Get query info
     */
    public function info(): array
    {
        return [
            'Rows matched' => $this->affectedRows(),
            'Changed' => $this->affectedRows(),
            'Warnings' => 0
        ];
    }

    /**
     * Get rows matched (same as affectedRows in PDO)
     */
    public function rowsMatched(): int
    {
        return $this->affectedRows();
    }

    /**
     * Get last insert ID
     */
    public function insertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Fetch single row
     */
    public function fetch(string $fetchType = '', string $className = 'stdClass', array $classParams = [])
    {
        if (!$fetchType) {
            $fetchType = $this->defaultFetchType;
        }

        if (!in_array($fetchType, self::ALLOWED_FETCH_TYPES_BOTH, true)) {
            $allowedComma = implode("','", self::ALLOWED_FETCH_TYPES_BOTH);
            throw new SimplePDOException("The variable 'fetchType' must be '$allowedComma'. You entered '$fetchType'");
        }

        $fetchMode = PDO::FETCH_ASSOC;
        switch ($fetchType) {
            case 'num':
                $fetchMode = PDO::FETCH_NUM;
                break;
            case 'obj':
                $fetchMode = PDO::FETCH_OBJ;
                if ($className !== 'stdClass') {
                    $this->stmt->setFetchMode(PDO::FETCH_CLASS, $className, $classParams);
                    return $this->stmt->fetch();
                }
                break;
            case 'col':
                if ($this->stmt->columnCount() !== 1) {
                    throw new SimplePDOException("The fetch type: '$fetchType' must have exactly 1 column in query");
                }
                return $this->stmt->fetchColumn();
        }

        return $this->stmt->fetch($fetchMode);
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $fetchType = '', string $className = 'stdClass', array $classParams = []): array
    {
        if (!$fetchType) {
            $fetchType = $this->defaultFetchType;
        }

        $comboAllowedTypes = array_merge(self::ALLOWED_FETCH_TYPES_BOTH, self::ALLOWED_FETCH_TYPES_FETCH_ALL);
        if (!in_array($fetchType, $comboAllowedTypes, true)) {
            $allowedComma = implode("','", $comboAllowedTypes);
            throw new SimplePDOException("The variable 'fetchType' must be '$allowedComma'. You entered '$fetchType'");
        }

        switch ($fetchType) {
            case 'num':
                return $this->stmt->fetchAll(PDO::FETCH_NUM);
            case 'assoc':
                return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            case 'obj':
                if ($className !== 'stdClass') {
                    return $this->stmt->fetchAll(PDO::FETCH_CLASS, $className, $classParams);
                }
                return $this->stmt->fetchAll(PDO::FETCH_OBJ);
            case 'col':
                if ($this->stmt->columnCount() !== 1) {
                    throw new SimplePDOException("The fetch type: '$fetchType' must have exactly 1 column in query");
                }
                return $this->stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            case 'keyPair':
                if ($this->stmt->columnCount() !== 2) {
                    throw new SimplePDOException("The fetch type: '$fetchType' must have exactly two columns in query");
                }
                return $this->stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            case 'keyPairArr':
                return $this->stmt->fetchAll(PDO::FETCH_UNIQUE);
            case 'group':
                return $this->stmt->fetchAll(PDO::FETCH_GROUP);
            case 'groupCol':
                if ($this->stmt->columnCount() !== 2) {
                    throw new SimplePDOException("The fetch type: '$fetchType' must have exactly two columns in query");
                }
                return $this->stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
            case 'groupObj':
                $this->stmt->setFetchMode(PDO::FETCH_CLASS, $className);
                return $this->stmt->fetchAll(PDO::FETCH_GROUP);
            default:
                return [];
        }
    }

    /**
     * Transaction with multiple queries
     */
    public function atomicQuery($sql, array $values, array $types = []): void
    {
        try {
            $this->pdo->beginTransaction();

            $isArray = is_array($sql);
            $countValues = count($values);

            if ($isArray && count($sql) !== $countValues) {
                throw new SimplePDOException("SQL and values arrays must have same count");
            }

            for ($x = 0; $x < $countValues; $x++) {
                $currSQL = $isArray ? $sql[$x] : $sql;
                $stmt = $this->pdo->prepare($currSQL);
                $stmt->execute($values[$x]);
                
                if ($stmt->rowCount() < 1) {
                    throw new SimplePDOException("Query did not succeed: $currSQL");
                }
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Transaction with callback
     */
    public function transaction(callable $callback): void
    {
        try {
            $this->pdo->beginTransaction();
            $callback($this);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }


    /**
     * Free result
     */
    public function freeResult(): self
    {
        $this->stmt->closeCursor();
        return $this;
    }

    /**
     * Close statement
     */
    public function closeStmt(): self
    {
        $this->stmt = null;
        return $this;
    }

    /**
     * Close connection
     */
    public function close(): void
    {
        $this->pdo = null;
    }

    /**
     * Database backup
     */
    public static function exportDatabase(string $host, string $user, string $password, string $database, string $targetFolderPath): bool
    {
        $backupName = $database . '_backup_' . date('Ymd_His') . '.sql';
        $targetFilePath = rtrim($targetFolderPath, '/') . '/' . $backupName;
        $command = 'mysqldump --host=' . $host . ' --user=' . $user . ' --password=' . $password . ' --databases ' . $database . ' > ' . $targetFilePath;
        exec($command, $output, $returnStatus);
        return $returnStatus === 0;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function fetchRow(string $sql, array $params = [], ?int $fetchType = null): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch($fetchType ?? PDO::FETCH_ASSOC) ?: null;
    }

    public function fetchColumn(string $sql, array $params = [], ?int $column = 0)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($column);
    }

    public function fetchValue(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn(0);
    }

    public function lastInsertId(): ?string
    {
        return $this->pdo->lastInsertId();
    }

    public function quote(string $value): string
    {
        return $this->pdo->quote($value);
    }

    public function getServerVersion(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function getConnectionStats(): array
    {
        return $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) ? ['status' => 'connected'] : ['status' => 'disconnected'];
    }
}
