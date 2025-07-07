<?php

namespace SimpleMDB;

use SimpleMDB\Traits\LoggerAwareTrait;
use SimpleMDB\Traits\EventDispatcherAwareTrait;

class BatchOperations
{
    use LoggerAwareTrait;
    use EventDispatcherAwareTrait;

    private DatabaseInterface $db;
    private int $batchSize;
    private QuerySanitizer $sanitizer;

    public function __construct(DatabaseInterface $db, int $batchSize = 1000)
    {
        $this->db = $db;
        $this->batchSize = $batchSize;
        $this->sanitizer = new QuerySanitizer();
    }

    public function batchInsert(string $table, array $columns, array $records): array
    {
        $results = [
            'total' => count($records),
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Validate columns
        $columns = array_map([$this->sanitizer, 'sanitize'], $columns, array_fill(0, count($columns), 'sql'));

        // Process in batches
        foreach (array_chunk($records, $this->batchSize) as $batch) {
            $placeholders = [];
            $values = [];

            foreach ($batch as $record) {
                // Validate record has all required columns
                if (count($record) !== count($columns)) {
                    $results['failed']++;
                    $results['errors'][] = "Record has incorrect number of columns";
                    continue;
                }

                // Sanitize values
                $record = array_map([$this->sanitizer, 'sanitize'], $record, array_fill(0, count($record), 'sql'));
                $values = array_merge($values, array_values($record));
                $placeholders[] = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            }

            if (empty($placeholders)) {
                continue;
            }

            try {
                $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES " . implode(',', $placeholders);
                $this->log('debug','Batch insert',['sql'=>$sql,'rows'=>count($values)]);
                $this->db->prepare($sql)->execute($values);
                $results['successful'] += $this->db->affectedRows();
            } catch (\Exception $e) {
                $this->log('error','Batch insert error',['exception'=>$e]);
                $results['failed'] += count($batch);
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    public function batchUpdate(string $table, array $data, array $conditions, array $records): array
    {
        $results = [
            'total' => count($records),
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Sanitize column names
        $data = array_map([$this->sanitizer, 'sanitize'], array_keys($data), array_fill(0, count($data), 'sql'));
        $conditions = array_map([$this->sanitizer, 'sanitize'], array_keys($conditions), array_fill(0, count($conditions), 'sql'));

        // Process in batches
        foreach (array_chunk($records, $this->batchSize) as $batch) {
            try {
                $cases = [];
                $values = [];
                $whereIn = [];

                foreach ($batch as $record) {
                    $whereParts = [];
                    foreach ($conditions as $column) {
                        if (!isset($record[$column])) {
                            throw new \InvalidArgumentException("Missing condition column: {$column}");
                        }
                        $whereParts[] = $record[$column];
                        $whereIn[] = $record[$column];
                    }

                    $caseWhen = [];
                    foreach ($data as $column) {
                        if (!isset($record[$column])) {
                            throw new \InvalidArgumentException("Missing data column: {$column}");
                        }
                        $values[] = $record[$column];
                        $caseWhen[] = '?';
                    }

                    $cases[] = "WHEN " . implode(' AND ', array_map(fn($col, $val) => "{$col} = ?", $conditions, $whereParts)) . 
                              " THEN (" . implode(',', $caseWhen) . ")";
                    $values = array_merge($values, $whereParts);
                }

                if (empty($cases)) {
                    continue;
                }

                $sql = "UPDATE {$table} SET ";
                foreach ($data as $i => $column) {
                    if ($i > 0) {
                        $sql .= ", ";
                    }
                    $sql .= "{$column} = CASE " . implode(' ', $cases) . " ELSE {$column} END";
                }
                $sql .= " WHERE " . implode(' OR ', array_map(
                    fn($col) => "{$col} IN (" . implode(',', array_fill(0, count($whereIn), '?')) . ")",
                    $conditions
                ));

                $this->db->prepare($sql)->execute(array_merge($values, $whereIn));
                $results['successful'] += $this->db->affectedRows();
            } catch (\Exception $e) {
                $this->log('error','Batch update error',['exception'=>$e]);
                $results['failed'] += count($batch);
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    public function batchDelete(string $table, array $conditions): array
    {
        $results = [
            'total' => count($conditions),
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Process in batches
        foreach (array_chunk($conditions, $this->batchSize) as $batch) {
            try {
                $placeholders = [];
                $values = [];

                foreach ($batch as $condition) {
                    $whereParts = [];
                    foreach ($condition as $column => $value) {
                        $column = $this->sanitizer->sanitize($column, 'sql');
                        $whereParts[] = "{$column} = ?";
                        $values[] = $value;
                    }
                    $placeholders[] = '(' . implode(' AND ', $whereParts) . ')';
                }

                if (empty($placeholders)) {
                    continue;
                }

                $sql = "DELETE FROM {$table} WHERE " . implode(' OR ', $placeholders);
                $this->db->prepare($sql)->execute($values);
                $results['successful'] += $this->db->affectedRows();
            } catch (\Exception $e) {
                $this->log('error','Batch delete error',['exception'=>$e]);
                $results['failed'] += count($batch);
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    public function upsert(string $table, array $columns, array $records, array $uniqueColumns): array
    {
        $results = [
            'total' => count($records),
            'inserted' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Validate and sanitize columns
        $columns = array_map([$this->sanitizer, 'sanitize'], $columns, array_fill(0, count($columns), 'sql'));
        $uniqueColumns = array_map([$this->sanitizer, 'sanitize'], $uniqueColumns, array_fill(0, count($uniqueColumns), 'sql'));

        // Process in batches
        foreach (array_chunk($records, $this->batchSize) as $batch) {
            try {
                $placeholders = [];
                $values = [];
                $updateParts = [];

                foreach ($batch as $record) {
                    if (count($record) !== count($columns)) {
                        $results['failed']++;
                        $results['errors'][] = "Record has incorrect number of columns";
                        continue;
                    }

                    $record = array_map([$this->sanitizer, 'sanitize'], $record, array_fill(0, count($record), 'sql'));
                    $values = array_merge($values, array_values($record));
                    $placeholders[] = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
                }

                if (empty($placeholders)) {
                    continue;
                }

                // Build UPDATE part for non-unique columns
                foreach (array_diff($columns, $uniqueColumns) as $column) {
                    $updateParts[] = "{$column} = VALUES({$column})";
                }

                $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") 
                        VALUES " . implode(',', $placeholders) . "
                        ON DUPLICATE KEY UPDATE " . implode(',', $updateParts);

                $this->db->prepare($sql)->execute($values);

                $affectedRows = $this->db->affectedRows();
                $results['inserted'] += floor($affectedRows / 2);
                $results['updated'] += $affectedRows - floor($affectedRows / 2);
            } catch (\Exception $e) {
                $this->log('error','Batch upsert error',['exception'=>$e]);
                $results['failed'] += count($batch);
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    public function setBatchSize(int $size): self
    {
        $this->batchSize = $size;
        return $this;
    }

    public function transaction(callable $callback): mixed
    {
        try {
            $this->db->beginTransaction();
            $result = $callback($this);
            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
} 