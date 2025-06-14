<?php
namespace SimpleMDB;

interface DatabaseInterface {
    // Connection methods
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
    public function inTransaction(): bool;
    public function transaction(callable $callback): void;
    public function isConnected(): bool;
    public function instance(): mixed;
    
    // Query execution
    public function prepare(string $sql): self;
    public function query(string $sql, array|string|int $values = [], string $types = ''): self;
    public function execute(array $values = []): self;
    
    // Data retrieval
    public function fetchAll(string $fetchType = '', string $className = 'stdClass', array $classParams = []): array;
    public function fetch(string $fetchType = '', string $className = 'stdClass', array $classParams = []);
    public function fetchRow(string $sql, array $params = [], ?int $fetchType = null): ?array;
    public function fetchColumn(string $sql, array $params = [], ?int $column = 0);
    public function fetchValue(string $sql, array $params = []);
    
    // Data manipulation
    public static function insert(string $table, array $data): array | bool;
    public static function select_all_query(string $table, array $fields = [], string $adjuncts = ""): array;
    public function update(string $table, array $data = [], string $adjuncts = "", array $adjunctValues = []): self | bool;
    public function update2(string $table, array $data = [], string $adjuncts = "", array $adjunctValues = []): array;
    public function read_data(string $table, array $fields = [], string $adjunct = "", array $adjunctValues = []);
    public function read_data_all(string $table, array $fields = [], string $adjunct = "", array $adjunctValues = []): array | bool;
    public function write_data(string $table, array $data): self | bool;
    public function delete(string $table, string $adjunct = "", array $adjunctValues = []): self | bool;
    
    // Helper methods
    public function whereIn(array $inArr): string;
    public function numRows(): int;
    public function affectedRows(): int;
    public function info(): array;
    public function rowsMatched(): int;
    public function insertId(): int;
    public function lastInsertId(): ?string;
    public function quote(string $value): string;
    
    // Resource management
    public function freeResult(): self;
    public function closeStmt(): self;
    public function close(): void;
    
    // Metadata
    public function getServerVersion(): string;
    public function getConnectionStats(): array;
    
    // Utility methods
    public static function exportDatabase(string $host, string $user, string $password, string $database, string $targetFolderPath): bool;
}
