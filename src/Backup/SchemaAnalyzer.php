<?php

namespace SimpleMDB\Backup;

use SimpleMDB\SimpleMySQLi;
use SimpleMDB\Exceptions\SchemaException;

/**
 * SchemaAnalyzer - Analyzes existing database schemas for migration generation
 * 
 * This class provides comprehensive schema analysis capabilities, extracting
 * detailed information about tables, columns, indexes, and constraints that
 * can be used to generate expressive SimpleMDB migrations.
 */
class SchemaAnalyzer
{
    private SimpleMySQLi $db;
    private string $database;
    private array $analyzedTables = [];
    private array $tableRelationships = [];

    public function __construct(SimpleMySQLi $db, ?string $database = null)
    {
        $this->db = $db;
        $this->database = $database ?? $this->getCurrentDatabase();
    }

    /**
     * Analyze all tables in the database
     */
    public function analyzeDatabase(): array
    {
        $tables = $this->getTables();
        $this->analyzedTables = [];
        $this->tableRelationships = [];

        foreach ($tables as $table) {
            $this->analyzedTables[$table] = $this->analyzeTable($table);
        }

        $this->analyzeRelationships();
        
        return [
            'database' => $this->database,
            'tables' => $this->analyzedTables,
            'relationships' => $this->tableRelationships,
            'analyzed_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Analyze specific tables
     */
    public function analyzeTables(array $tableNames): array
    {
        $this->analyzedTables = [];
        
        foreach ($tableNames as $table) {
            if ($this->tableExists($table)) {
                $this->analyzedTables[$table] = $this->analyzeTable($table);
            }
        }
        
        $this->analyzeRelationships();
        
        return $this->analyzedTables;
    }

    /**
     * Analyze a single table structure
     */
    public function analyzeTable(string $tableName): array
    {
        if (!$this->tableExists($tableName)) {
            throw new SchemaException("Table '{$tableName}' does not exist");
        }

        return [
            'name' => $tableName,
            'columns' => $this->getColumns($tableName),
            'indexes' => $this->getIndexes($tableName),
            'foreign_keys' => $this->getForeignKeys($tableName),
            'engine' => $this->getTableEngine($tableName),
            'charset' => $this->getTableCharset($tableName),
            'collation' => $this->getTableCollation($tableName),
            'comment' => $this->getTableComment($tableName),
            'auto_increment' => $this->getAutoIncrementValue($tableName),
            'row_count' => $this->getRowCount($tableName)
        ];
    }

    /**
     * Get all table names in the database
     */
    private function getTables(): array
    {
        // Note: SHOW TABLES is a MySQL administrative command, keeping as raw SQL
        $result = $this->db->query("SHOW TABLES FROM `{$this->database}`");
        $tables = [];
        
        while ($row = $result->fetch('num')) {
            $tables[] = $row[0];
        }
        
        return $tables;
    }

    /**
     * Get detailed column information
     */
    private function getColumns(string $tableName): array
    {
        // Use SimpleMDB's expressive query builder for INFORMATION_SCHEMA
        $results = $this->db->queryBuilder()
            ->select([
                'COLUMN_NAME',
                'DATA_TYPE', 
                'COLUMN_TYPE',
                'IS_NULLABLE',
                'COLUMN_DEFAULT',
                'EXTRA',
                'CHARACTER_MAXIMUM_LENGTH',
                'NUMERIC_PRECISION',
                'NUMERIC_SCALE',
                'COLUMN_COMMENT',
                'ORDINAL_POSITION'
            ])
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->orderBy('ORDINAL_POSITION')
            ->execute($this->db, 'assoc');

        $columns = [];

        foreach ($results as $row) {
            $columnName = $row['COLUMN_NAME'];
            $columns[$columnName] = [
                'name' => $columnName,
                'type' => $this->normalizeColumnType($row),
                'nullable' => $row['IS_NULLABLE'] === 'YES',
                'default' => $this->parseDefaultValue($row['COLUMN_DEFAULT']),
                'auto_increment' => strpos($row['EXTRA'], 'auto_increment') !== false,
                'primary_key' => false, // Will be set when analyzing indexes
                'unique' => false, // Will be set when analyzing indexes
                'comment' => $row['COLUMN_COMMENT'] ?: null,
                'position' => (int) $row['ORDINAL_POSITION'],
                'raw_type' => $row['COLUMN_TYPE']
            ];
        }

        // Mark primary key columns
        $primaryKeys = $this->getPrimaryKeyColumns($tableName);
        foreach ($primaryKeys as $pkColumn) {
            if (isset($columns[$pkColumn])) {
                $columns[$pkColumn]['primary_key'] = true;
            }
        }

        return $columns;
    }

    /**
     * Get table indexes
     */
    private function getIndexes(string $tableName): array
    {
        // Note: SHOW INDEX is a MySQL administrative command, keeping as raw SQL
        $sql = "SHOW INDEX FROM `{$tableName}`";
        $result = $this->db->query($sql);
        $indexes = [];

        while ($row = $result->fetch('assoc')) {
            $indexName = $row['Key_name'];
            
            if (!isset($indexes[$indexName])) {
                $indexes[$indexName] = [
                    'name' => $indexName,
                    'type' => $this->getIndexType($row),
                    'unique' => $row['Non_unique'] == 0,
                    'primary' => $indexName === 'PRIMARY',
                    'columns' => [],
                    'comment' => $row['Index_comment'] ?: null
                ];
            }
            
            $indexes[$indexName]['columns'][] = [
                'name' => $row['Column_name'],
                'length' => $row['Sub_part'],
                'order' => $row['Collation'] === 'D' ? 'DESC' : 'ASC'
            ];
        }

        return array_values($indexes);
    }

    /**
     * Get foreign key constraints
     */
    private function getForeignKeys(string $tableName): array
    {
        // First get the key column usage information
        $keyResults = $this->db->queryBuilder()
            ->select([
                'CONSTRAINT_NAME',
                'COLUMN_NAME',
                'REFERENCED_TABLE_SCHEMA',
                'REFERENCED_TABLE_NAME',
                'REFERENCED_COLUMN_NAME'
            ])
            ->from('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->where('REFERENCED_TABLE_NAME IS NOT NULL')
            ->orderBy('CONSTRAINT_NAME')
            ->orderBy('ORDINAL_POSITION')
            ->execute($this->db, 'assoc');

        // Get the referential constraints for update/delete rules
        $constraintResults = $this->db->queryBuilder()
            ->select([
                'CONSTRAINT_NAME',
                'UPDATE_RULE',
                'DELETE_RULE'
            ])
            ->from('INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->execute($this->db, 'assoc');

        // Index constraint rules by constraint name
        $constraintRules = [];
        foreach ($constraintResults as $rule) {
            $constraintRules[$rule['CONSTRAINT_NAME']] = [
                'on_update' => strtolower($rule['UPDATE_RULE']),
                'on_delete' => strtolower($rule['DELETE_RULE'])
            ];
        }

        $foreignKeys = [];

        foreach ($keyResults as $row) {
            $constraintName = $row['CONSTRAINT_NAME'];
            
            if (!isset($foreignKeys[$constraintName])) {
                $rules = $constraintRules[$constraintName] ?? ['on_update' => 'restrict', 'on_delete' => 'restrict'];
                $foreignKeys[$constraintName] = [
                    'name' => $constraintName,
                    'local_columns' => [],
                    'foreign_table' => $row['REFERENCED_TABLE_NAME'],
                    'foreign_columns' => [],
                    'on_update' => $rules['on_update'],
                    'on_delete' => $rules['on_delete']
                ];
            }
            
            $foreignKeys[$constraintName]['local_columns'][] = $row['COLUMN_NAME'];
            $foreignKeys[$constraintName]['foreign_columns'][] = $row['REFERENCED_COLUMN_NAME'];
        }

        return array_values($foreignKeys);
    }

    /**
     * Analyze relationships between tables
     */
    private function analyzeRelationships(): void
    {
        $this->tableRelationships = [];
        
        foreach ($this->analyzedTables as $tableName => $tableInfo) {
            foreach ($tableInfo['foreign_keys'] as $fk) {
                $this->tableRelationships[] = [
                    'from_table' => $tableName,
                    'to_table' => $fk['foreign_table'],
                    'type' => $this->determineRelationshipType($tableName, $fk),
                    'constraint' => $fk['name'],
                    'local_columns' => $fk['local_columns'],
                    'foreign_columns' => $fk['foreign_columns']
                ];
            }
        }
    }

    /**
     * Get creation order for tables based on dependencies
     */
    public function getTableCreationOrder(): array
    {
        $tables = array_keys($this->analyzedTables);
        $dependencies = [];
        
        // Build dependency graph
        foreach ($this->analyzedTables as $tableName => $tableInfo) {
            $dependencies[$tableName] = [];
            foreach ($tableInfo['foreign_keys'] as $fk) {
                if ($fk['foreign_table'] !== $tableName) { // Avoid self-references
                    $dependencies[$tableName][] = $fk['foreign_table'];
                }
            }
        }
        
        // Topological sort
        return $this->topologicalSort($tables, $dependencies);
    }

    /**
     * Helper methods
     */
    private function getCurrentDatabase(): string
    {
        // Use direct query for DATABASE() function (doesn't need FROM clause)
        $result = $this->db->query("SELECT DATABASE() as db_name")->fetch('assoc');
        
        if (!$result || !$result['db_name']) {
            throw new SchemaException("No database selected");
        }
        
        return $result['db_name'];
    }

    private function tableExists(string $tableName): bool
    {
        // Use SimpleMDB's expressive query for table existence check
        $results = $this->db->queryBuilder()
            ->select(['1 as exists_check'])
            ->from('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->execute($this->db, 'assoc');
        
        return count($results) > 0;
    }

    private function normalizeColumnType(array $columnInfo): array
    {
        $type = strtolower($columnInfo['DATA_TYPE']);
        $fullType = $columnInfo['COLUMN_TYPE'];
        
        $typeInfo = ['base_type' => $type];
        
        // Extract length/precision
        if (preg_match('/\(([^)]+)\)/', $fullType, $matches)) {
            $params = explode(',', $matches[1]);
            $typeInfo['length'] = trim($params[0]);
            if (isset($params[1])) {
                $typeInfo['scale'] = trim($params[1]);
            }
        }
        
        // Handle specific types
        if ($columnInfo['CHARACTER_MAXIMUM_LENGTH']) {
            $typeInfo['length'] = $columnInfo['CHARACTER_MAXIMUM_LENGTH'];
        }
        
        if ($columnInfo['NUMERIC_PRECISION']) {
            $typeInfo['precision'] = $columnInfo['NUMERIC_PRECISION'];
            if ($columnInfo['NUMERIC_SCALE']) {
                $typeInfo['scale'] = $columnInfo['NUMERIC_SCALE'];
            }
        }
        
        // Check for unsigned
        if (strpos($fullType, 'unsigned') !== false) {
            $typeInfo['unsigned'] = true;
        }
        
        return $typeInfo;
    }

    private function parseDefaultValue($default)
    {
        if ($default === null) {
            return null;
        }
        
        if ($default === 'CURRENT_TIMESTAMP') {
            return ['function' => 'CURRENT_TIMESTAMP'];
        }
        
        if (is_numeric($default)) {
            return strpos($default, '.') !== false ? (float) $default : (int) $default;
        }
        
        return $default;
    }

    private function getPrimaryKeyColumns(string $tableName): array
    {
        // Note: SHOW INDEX is a MySQL administrative command, keeping as raw SQL
        $sql = "SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'PRIMARY'";
        $result = $this->db->query($sql);
        $columns = [];
        
        while ($row = $result->fetch('assoc')) {
            $columns[] = $row['Column_name'];
        }
        
        return $columns;
    }

    private function getIndexType(array $indexRow): string
    {
        if ($indexRow['Key_name'] === 'PRIMARY') {
            return 'primary';
        }
        
        if ($indexRow['Non_unique'] == 0) {
            return 'unique';
        }
        
        if ($indexRow['Index_type'] === 'FULLTEXT') {
            return 'fulltext';
        }
        
        return 'index';
    }

    private function getTableEngine(string $tableName): string
    {
        // Use SimpleMDB's expressive query for table engine lookup
        $results = $this->db->queryBuilder()
            ->select(['ENGINE'])
            ->from('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->execute($this->db, 'assoc');
        
        $row = $results[0] ?? null;
        return $row['ENGINE'] ?? 'InnoDB';
    }

    private function getTableCharset(string $tableName): string
    {
        // Use SimpleMDB's expressive query for table collation lookup
        $results = $this->db->queryBuilder()
            ->select(['TABLE_COLLATION'])
            ->from('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->execute($this->db, 'assoc');
        
        $row = $results[0] ?? null;
        
        if ($row && $row['TABLE_COLLATION']) {
            return explode('_', $row['TABLE_COLLATION'])[0];
        }
        
        return 'utf8mb4';
    }

    private function getTableCollation(string $tableName): string
    {
        // Use SimpleMDB's expressive query for table collation lookup
        $results = $this->db->queryBuilder()
            ->select(['TABLE_COLLATION'])
            ->from('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->execute($this->db, 'assoc');
        
        $row = $results[0] ?? null;
        return $row['TABLE_COLLATION'] ?? 'utf8mb4_unicode_ci';
    }

    private function getTableComment(string $tableName): ?string
    {
        // Use SimpleMDB's expressive query for table comment lookup
        $results = $this->db->queryBuilder()
            ->select(['TABLE_COMMENT'])
            ->from('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->execute($this->db, 'assoc');
        
        $row = $results[0] ?? null;
        return $row['TABLE_COMMENT'] ?: null;
    }

    private function getAutoIncrementValue(string $tableName): ?int
    {
        // Use SimpleMDB's expressive query for auto increment value lookup
        $results = $this->db->queryBuilder()
            ->select(['AUTO_INCREMENT'])
            ->from('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA = ?', [$this->database])
            ->where('TABLE_NAME = ?', [$tableName])
            ->execute($this->db, 'assoc');
        
        $row = $results[0] ?? null;
        return $row['AUTO_INCREMENT'] ? (int) $row['AUTO_INCREMENT'] : null;
    }

    private function getRowCount(string $tableName): int
    {
        // Use SimpleMDB's expressive query builder for counting records
        $results = $this->db->queryBuilder()
            ->select(['COUNT(*) as count'])
            ->from($tableName)
            ->execute($this->db, 'assoc');
        
        $row = $results[0] ?? null;
        return (int) ($row['count'] ?? 0);
    }

    private function determineRelationshipType(string $tableName, array $foreignKey): string
    {
        // Simple heuristic - could be enhanced
        if (count($foreignKey['local_columns']) === 1) {
            return 'belongs_to';
        }
        
        return 'has_many';
    }

    private function topologicalSort(array $nodes, array $dependencies): array
    {
        $result = [];
        $visited = [];
        $temp = [];
        
        foreach ($nodes as $node) {
            if (!isset($visited[$node])) {
                $this->topologicalSortVisit($node, $dependencies, $visited, $temp, $result);
            }
        }
        
        return array_reverse($result);
    }

    private function topologicalSortVisit(string $node, array $dependencies, array &$visited, array &$temp, array &$result): void
    {
        if (isset($temp[$node])) {
            // Circular dependency - add to result anyway
            return;
        }
        
        if (isset($visited[$node])) {
            return;
        }
        
        $temp[$node] = true;
        
        foreach ($dependencies[$node] ?? [] as $dependency) {
            $this->topologicalSortVisit($dependency, $dependencies, $visited, $temp, $result);
        }
        
        unset($temp[$node]);
        $visited[$node] = true;
        $result[] = $node;
    }
} 