<?php

namespace SimpleMDB\Seeding;

use SimpleMDB\DatabaseInterface;
use SimpleMDB\Traits\LoggerAwareTrait;
use SimpleMDB\Exceptions\SchemaException;

/**
 * Base seeder class for database seeding
 */
abstract class Seeder
{
    use LoggerAwareTrait;

    protected DatabaseInterface $db;
    protected string $table;
    protected array $dependencies = [];
    protected bool $truncateFirst = false;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Run the seeder
     */
    abstract public function run(): void;

    /**
     * Get seeder name
     */
    public function getName(): string
    {
        return static::class;
    }

    /**
     * Get table name this seeder populates
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get seeder dependencies
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Whether to truncate table before seeding
     */
    public function shouldTruncateFirst(): bool
    {
        return $this->truncateFirst;
    }

    /**
     * Set dependencies
     */
    protected function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }

    /**
     * Set whether to truncate table first
     */
    protected function setTruncateFirst(bool $truncate): void
    {
        $this->truncateFirst = $truncate;
    }

    /**
     * Insert single record
     */
    protected function insert(string $table, array $data): void
    {
        try {
            $this->db->write_data($table, $data);
            $this->log('debug', "Inserted record into $table", [
                'table' => $table,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->log('error', "Failed to insert record into $table", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Insert multiple records
     */
    protected function insertMany(string $table, array $records): void
    {
        if (empty($records)) {
            return;
        }

        try {
            $this->db->transaction(function() use ($table, $records) {
                foreach ($records as $record) {
                    $this->db->write_data($table, $record);
                }
            });

            $this->log('info', "Inserted {count} records into $table", [
                'table' => $table,
                'count' => count($records)
            ]);
        } catch (\Exception $e) {
            $this->log('error', "Failed to insert records into $table", [
                'table' => $table,
                'count' => count($records),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Insert records using batch insert for performance
     */
    protected function insertBatch(string $table, array $records): void
    {
        if (empty($records)) {
            return;
        }

        try {
            $columns = array_keys($records[0]);
            $values = [];
            $params = [];

            foreach ($records as $record) {
                $placeholders = array_fill(0, count($columns), '?');
                $values[] = '(' . implode(', ', $placeholders) . ')';
                $params = array_merge($params, array_values($record));
            }

            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES " . implode(', ', $values);
            $this->db->query($sql, $params);

            $this->log('info', "Batch inserted {count} records into $table", [
                'table' => $table,
                'count' => count($records)
            ]);
        } catch (\Exception $e) {
            $this->log('error', "Failed to batch insert records into $table", [
                'table' => $table,
                'count' => count($records),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Truncate table
     */
    protected function truncate(string $table): void
    {
        try {
            $this->db->query("TRUNCATE TABLE $table");
            $this->log('info', "Truncated table $table");
        } catch (\Exception $e) {
            $this->log('error', "Failed to truncate table $table", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete all records from table
     */
    protected function deleteAll(string $table): void
    {
        try {
            $this->db->query("DELETE FROM $table");
            $this->log('info', "Deleted all records from table $table");
        } catch (\Exception $e) {
            $this->log('error', "Failed to delete records from table $table", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if table exists
     */
    protected function tableExists(string $table): bool
    {
        try {
            $result = $this->db->query("SHOW TABLES LIKE ?", [$table]);
            return $result->numRows() > 0;
        } catch (\Exception $e) {
            $this->log('error', "Failed to check if table exists", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get record count from table
     */
    protected function getRecordCount(string $table): int
    {
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM $table");
            $row = $result->fetch('assoc');
            return (int)$row['count'];
        } catch (\Exception $e) {
            $this->log('error', "Failed to get record count", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Generate random string
     */
    protected function randomString(int $length = 10): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * Generate random email
     */
    protected function randomEmail(): string
    {
        $domains = ['example.com', 'test.org', 'demo.net', 'sample.io'];
        $username = $this->randomString(8);
        $domain = $domains[array_rand($domains)];
        
        return strtolower($username) . '@' . $domain;
    }

    /**
     * Generate random phone number
     */
    protected function randomPhone(): string
    {
        return sprintf(
            '(%03d) %03d-%04d',
            rand(100, 999),
            rand(100, 999),
            rand(1000, 9999)
        );
    }

    /**
     * Generate random date between two dates
     */
    protected function randomDate(string $startDate = '-1 year', string $endDate = 'now'): string
    {
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        
        $randomTimestamp = rand($startTimestamp, $endTimestamp);
        
        return date('Y-m-d H:i:s', $randomTimestamp);
    }

    /**
     * Generate random boolean
     */
    protected function randomBool(): bool
    {
        return rand(0, 1) === 1;
    }

    /**
     * Generate random number between min and max
     */
    protected function randomNumber(int $min = 1, int $max = 100): int
    {
        return rand($min, $max);
    }

    /**
     * Get random item from array
     */
    protected function randomChoice(array $choices)
    {
        return $choices[array_rand($choices)];
    }

    /**
     * Generate timestamp for created_at/updated_at
     */
    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Generate fake data using various generators
     */
    protected function fake(): FakeDataGenerator
    {
        return new FakeDataGenerator();
    }
} 