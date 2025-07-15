<?php

namespace SimpleMDB\Backup;

use SimpleMDB\Exceptions\SchemaException;

/**
 * MigrationGenerator - Generates SimpleMDB migration files from schema analysis
 * 
 * This class converts analyzed database schemas into expressive SimpleMDB migration
 * files, supporting the full range of SimpleMDB's fluent API for table creation,
 * indexing, and constraint management.
 */
class MigrationGenerator
{
    private array $schemaData;
    private array $options;
    private string $migrationTemplate;
    private string $namespace;

    public function __construct(array $schemaData = [], array $options = [])
    {
        $this->schemaData = $schemaData;
        $this->options = array_merge([
            'expressive_syntax' => true,
            'split_tables' => false,
            'tables_per_file' => 5,
            'namespace' => '',
            'class_prefix' => 'Create',
            'use_comments' => true,
            'preserve_order' => true,
            'generate_indexes' => true,
            'generate_foreign_keys' => true
        ], $options);
        
        $this->namespace = $this->options['namespace'];
        $this->initializeTemplate();
    }

    /**
     * Generate migration files for all tables
     */
    public function generateMigrations(string $outputDir = 'migrations'): array
    {
        if (empty($this->schemaData['tables'])) {
            throw new SchemaException("No table data found in schema analysis");
        }

        $tables = $this->schemaData['tables'];
        $createdFiles = [];

        if ($this->options['split_tables']) {
            $createdFiles = $this->generateSplitMigrations($tables, $outputDir);
        } else {
            $createdFiles[] = $this->generateSingleMigration($tables, $outputDir);
        }

        return $createdFiles;
    }

    /**
     * Generate a single migration file for all tables
     */
    public function generateSingleMigration(array $tables, string $outputDir): string
    {
        $timestamp = date('YmdHis');
        $className = $this->options['class_prefix'] . 'DatabaseSchema';
        $filename = "Migration_{$timestamp}_{$className}.php";
        $filepath = rtrim($outputDir, '/') . '/' . $filename;

        // Get table creation order
        $orderedTables = $this->getOrderedTables($tables);
        
        $upMethod = $this->generateUpMethod($orderedTables);
        $downMethod = $this->generateDownMethod(array_reverse(array_keys($orderedTables)));
        
        $content = $this->renderMigration($className, $upMethod, $downMethod);
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        file_put_contents($filepath, $content);
        
        return $filepath;
    }

    /**
     * Generate separate migration files for groups of tables
     */
    public function generateSplitMigrations(array $tables, string $outputDir): array
    {
        $orderedTables = $this->getOrderedTables($tables);
        $tableChunks = array_chunk($orderedTables, $this->options['tables_per_file'], true);
        $createdFiles = [];
        $baseTimestamp = time();

        foreach ($tableChunks as $index => $chunk) {
            $timestamp = date('YmdHis', $baseTimestamp + $index);
            $className = $this->options['class_prefix'] . 'Schema' . sprintf('%02d', $index + 1);
            $filename = "Migration_{$timestamp}_{$className}.php";
            $filepath = rtrim($outputDir, '/') . '/' . $filename;

            $upMethod = $this->generateUpMethod($chunk);
            $downMethod = $this->generateDownMethod(array_reverse(array_keys($chunk)));
            
            $content = $this->renderMigration($className, $upMethod, $downMethod);
            
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            file_put_contents($filepath, $content);
            $createdFiles[] = $filepath;
        }

        return $createdFiles;
    }

    /**
     * Generate migration content for a single table
     */
    public function generateTableMigration(string $tableName, array $tableData): string
    {
        if (!isset($this->schemaData['tables'][$tableName])) {
            throw new SchemaException("Table '{$tableName}' not found in schema data");
        }

        $timestamp = date('YmdHis');
        $className = $this->options['class_prefix'] . $this->toPascalCase($tableName) . 'Table';
        
        $tableCode = $this->generateTableCreation($tableName, $tableData);
        
        $upMethod = "    public function up(): void\n    {\n{$tableCode}\n    }";
        $downMethod = "    public function down(): void\n    {\n        \$this->dropTable('{$tableName}');\n    }";
        
        return $this->renderMigration($className, $upMethod, $downMethod);
    }

    /**
     * Generate the up() method content
     */
    private function generateUpMethod(array $tables): string
    {
        $code = "    public function up(): void\n    {\n";
        
        if ($this->options['use_comments']) {
            $code .= "        // Create tables in dependency order\n";
        }
        
        foreach ($tables as $tableName => $tableData) {
            $code .= $this->generateTableCreation($tableName, $tableData);
            $code .= "\n";
        }
        
        $code .= "    }";
        
        return $code;
    }

    /**
     * Generate the down() method content
     */
    private function generateDownMethod(array $tableNames): string
    {
        $code = "    public function down(): void\n    {\n";
        
        if ($this->options['use_comments']) {
            $code .= "        // Drop tables in reverse dependency order\n";
        }
        
        foreach ($tableNames as $tableName) {
            $code .= "        \$this->dropTable('{$tableName}');\n";
        }
        
        $code .= "    }";
        
        return $code;
    }

    /**
     * Generate table creation code
     */
    private function generateTableCreation(string $tableName, array $tableData): string
    {
        $code = "";
        
        if ($this->options['use_comments']) {
            $comment = $tableData['comment'] ? " - {$tableData['comment']}" : "";
            $code .= "        // Create {$tableName} table{$comment}\n";
        }
        
        if ($this->options['expressive_syntax']) {
            $code .= $this->generateExpressiveTableCreation($tableName, $tableData);
        } else {
            $code .= $this->generateStandardTableCreation($tableName, $tableData);
        }
        
        return $code;
    }

    /**
     * Generate expressive table creation syntax
     */
    private function generateExpressiveTableCreation(string $tableName, array $tableData): string
    {
        $code = "        \$this->newTable('{$tableName}')\n";
        
        // Add table options
        if ($tableData['engine'] !== 'InnoDB') {
            $code .= "            ->engine('{$tableData['engine']}')\n";
        }
        
        if ($tableData['charset'] !== 'utf8mb4') {
            $code .= "            ->charset('{$tableData['charset']}')\n";
        }
        
        if ($tableData['collation'] !== 'utf8mb4_unicode_ci') {
            $code .= "            ->collation('{$tableData['collation']}')\n";
        }
        
        if ($tableData['comment']) {
            $escapedComment = str_replace("'", "\'", $tableData['comment']);
            $code .= "            ->comment('{$escapedComment}')\n";
        }
        
        // Add columns
        foreach ($tableData['columns'] as $columnName => $columnData) {
            $code .= $this->generateExpressiveColumn($columnName, $columnData);
        }
        
        // Add indexes
        if ($this->options['generate_indexes']) {
            foreach ($tableData['indexes'] as $index) {
                if (!$index['primary']) { // Primary keys are handled in column definitions
                    $code .= $this->generateExpressiveIndex($index);
                }
            }
        }
        
        // Add foreign keys
        if ($this->options['generate_foreign_keys']) {
            foreach ($tableData['foreign_keys'] as $fk) {
                $code .= $this->generateExpressiveForeignKey($fk);
            }
        }
        
        $code .= "            ->safely()\n";
        $code .= "            ->create();\n";
        
        return $code;
    }

    /**
     * Generate expressive column definition
     */
    private function generateExpressiveColumn(string $columnName, array $columnData): string
    {
        $type = $columnData['type'];
        $code = "            ->column('{$columnName}')";
        
        // Handle different column types
        switch ($type['base_type']) {
            case 'varchar':
                $length = $type['length'] ?? 255;
                $code .= "->varchar({$length})";
                break;
                
            case 'char':
                $length = $type['length'] ?? 1;
                $code .= "->char({$length})";
                break;
                
            case 'text':
                $code .= "->text()";
                break;
                
            case 'longtext':
                $code .= "->longText()";
                break;
                
            case 'int':
                if (isset($type['length'])) {
                    $code .= "->int({$type['length']})";
                } else {
                    $code .= "->int()";
                }
                break;
                
            case 'bigint':
                $code .= "->bigInt()";
                break;
                
            case 'tinyint':
                if (isset($type['length']) && $type['length'] == 1) {
                    $code .= "->boolean()";
                } else {
                    $code .= "->tinyInt()";
                }
                break;
                
            case 'decimal':
                $precision = $type['precision'] ?? 10;
                $scale = $type['scale'] ?? 2;
                $code .= "->decimal({$precision}, {$scale})";
                break;
                
            case 'float':
                $code .= "->float()";
                break;
                
            case 'double':
                $code .= "->double()";
                break;
                
            case 'date':
                $code .= "->date()";
                break;
                
            case 'datetime':
                $code .= "->datetime()";
                break;
                
            case 'timestamp':
                $code .= "->timestamp()";
                break;
                
            case 'time':
                $code .= "->time()";
                break;
                
            case 'json':
                $code .= "->json()";
                break;
                
            case 'enum':
                // Extract enum values from raw type
                if (preg_match("/enum\((.+)\)/", $columnData['raw_type'], $matches)) {
                    $enumValues = $matches[1];
                    $code .= "->enum([{$enumValues}])";
                } else {
                    $code .= "->varchar(255)"; // Fallback
                }
                break;
                
            default:
                $code .= "->varchar(255)"; // Safe fallback
        }
        
        // Add column modifiers
        if (!empty($type['unsigned'])) {
            $code .= "->unsigned()";
        }
        
        if (!$columnData['nullable']) {
            $code .= "->notNull()";
        }
        
        if ($columnData['auto_increment']) {
            $code .= "->autoIncrement()";
        }
        
        if ($columnData['primary_key']) {
            $code .= "->primaryKey()";
        }
        
        if ($columnData['unique']) {
            $code .= "->unique()";
        }
        
        // Handle default values
        if ($columnData['default'] !== null) {
            $default = $columnData['default'];
            if (is_array($default) && isset($default['function'])) {
                $code .= "->default(DB::raw('{$default['function']}'))";
            } elseif (is_string($default)) {
                $escapedDefault = str_replace("'", "\'", $default);
                $code .= "->default('{$escapedDefault}')";
            } else {
                $code .= "->default({$default})";
            }
        }
        
        if ($columnData['comment']) {
            $escapedComment = str_replace("'", "\'", $columnData['comment']);
            $code .= "->comment('{$escapedComment}')";
        }
        
        $code .= "\n";
        
        return $code;
    }

    /**
     * Generate expressive index definition
     */
    private function generateExpressiveIndex(array $indexData): string
    {
        $columns = array_map(function($col) { return $col['name']; }, $indexData['columns']);
        $columnList = "'" . implode("', '", $columns) . "'";
        
        switch ($indexData['type']) {
            case 'unique':
                $code = "            ->unique([{$columnList}])";
                break;
                
            case 'fulltext':
                $code = "            ->fulltext([{$columnList}])";
                break;
                
            default:
                $code = "            ->index([{$columnList}])";
        }
        
        if ($indexData['name'] !== 'PRIMARY') {
            $code .= "->name('{$indexData['name']}')";
        }
        
        $code .= "\n";
        
        return $code;
    }

    /**
     * Generate expressive foreign key definition
     */
    private function generateExpressiveForeignKey(array $fkData): string
    {
        $localColumns = "'" . implode("', '", $fkData['local_columns']) . "'";
        $foreignColumns = "'" . implode("', '", $fkData['foreign_columns']) . "'";
        
        $code = "            ->foreign([{$localColumns}])\n";
        $code .= "            ->references([{$foreignColumns}])\n";
        $code .= "            ->on('{$fkData['foreign_table']}')\n";
        
        if ($fkData['on_update'] !== 'restrict') {
            $code .= "            ->onUpdate('" . strtoupper($fkData['on_update']) . "')\n";
        }
        
        if ($fkData['on_delete'] !== 'restrict') {
            $code .= "            ->onDelete('" . strtoupper($fkData['on_delete']) . "')\n";
        }
        
        return $code;
    }

    /**
     * Generate standard table creation syntax (backward compatibility)
     */
    private function generateStandardTableCreation(string $tableName, array $tableData): string
    {
        $code = "        \$this->createTable('{$tableName}', function(\$table) {\n";
        
        foreach ($tableData['columns'] as $columnName => $columnData) {
            $code .= $this->generateStandardColumn($columnName, $columnData);
        }
        
        $code .= "        });\n";
        
        return $code;
    }

    /**
     * Generate standard column definition
     */
    private function generateStandardColumn(string $columnName, array $columnData): string
    {
        $type = $columnData['type'];
        
        switch ($type['base_type']) {
            case 'varchar':
                $length = $type['length'] ?? 255;
                $definition = "\$table->varchar('{$columnName}', {$length})";
                break;
                
            case 'int':
                $definition = "\$table->int('{$columnName}')";
                break;
                
            default:
                $definition = "\$table->varchar('{$columnName}', 255)";
        }
        
        if ($columnData['auto_increment']) {
            $definition .= "->autoIncrement()";
        }
        
        if ($columnData['primary_key']) {
            $definition .= "->primaryKey()";
        }
        
        return "            {$definition};\n";
    }

    /**
     * Get tables in dependency order
     */
    private function getOrderedTables(array $tables): array
    {
        if (!$this->options['preserve_order']) {
            return $tables;
        }
        
        // Build dependency graph from foreign keys
        $dependencies = [];
        foreach ($tables as $tableName => $tableData) {
            $dependencies[$tableName] = [];
            foreach ($tableData['foreign_keys'] as $fk) {
                if ($fk['foreign_table'] !== $tableName && isset($tables[$fk['foreign_table']])) {
                    $dependencies[$tableName][] = $fk['foreign_table'];
                }
            }
        }
        
        // Perform topological sort
        $orderedNames = $this->topologicalSort(array_keys($tables), $dependencies);
        
        // Return tables in dependency order
        $orderedTables = [];
        foreach ($orderedNames as $tableName) {
            if (isset($tables[$tableName])) {
                $orderedTables[$tableName] = $tables[$tableName];
            }
        }
        
        return $orderedTables;
    }

    /**
     * Topological sort for table dependencies
     */
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

    /**
     * Recursive helper for topological sort
     */
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

    /**
     * Render the complete migration file
     */
    private function renderMigration(string $className, string $upMethod, string $downMethod): string
    {
        $namespace = $this->namespace ? "namespace {$this->namespace};\n\n" : "";
        $timestamp = date('Y-m-d H:i:s');
        
        return str_replace([
            '{{namespace}}',
            '{{className}}',
            '{{timestamp}}',
            '{{upMethod}}',
            '{{downMethod}}'
        ], [
            $namespace,
            $className,
            $timestamp,
            $upMethod,
            $downMethod
        ], $this->migrationTemplate);
    }

    /**
     * Initialize the migration template
     */
    private function initializeTemplate(): void
    {
        $this->migrationTemplate = '<?php

{{namespace}}use SimpleMDB\Migrations\Migration;

/**
 * Auto-generated migration
 * Generated on: {{timestamp}}
 */
class {{className}} extends Migration
{
{{upMethod}}

{{downMethod}}
}
';
    }

    /**
     * Convert string to PascalCase
     */
    private function toPascalCase(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_'));
    }
} 