<?php

namespace SimpleMDB;

class QueryDebugger
{
    private array $queries = [];
    private bool $enabled = true;
    private ?string $logFile = null;
    private $formatter = null;
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db, ?string $logFile = null)
    {
        $this->db = $db;
        $this->logFile = $logFile;
        $this->formatter = [$this, 'defaultFormatter'];
    }

    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    public function setFormatter(callable $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    public function addQuery(string $sql, array $params, float $executionTime, ?array $backtrace = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $query = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'timestamp' => microtime(true),
            'backtrace' => $backtrace ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];

        $this->queries[] = $query;

        if ($this->logFile) {
            $this->logQuery($query);
        }
    }

    private function logQuery(array $query): void
    {
        $formatter = $this->formatter;
        $formattedQuery = $formatter($query);
        
        file_put_contents(
            $this->logFile,
            $formattedQuery . PHP_EOL,
            FILE_APPEND
        );
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    public function clear(): void
    {
        $this->queries = [];
    }

    public function defaultFormatter(array $query): string
    {
        $output = [];
        $output[] = date('Y-m-d H:i:s', (int)$query['timestamp']);
        $output[] = sprintf('Execution Time: %.4f seconds', $query['execution_time']);
        
        // Format SQL
        $sql = $this->formatSql($query['sql']);
        
        // Replace placeholders with values
        if (!empty($query['params'])) {
            $sql = $this->interpolateQuery($sql, $query['params']);
        }
        
        $output[] = 'SQL: ' . $sql;
        
        // Add stack trace
        if (!empty($query['backtrace'])) {
            $output[] = 'Stack Trace:';
            foreach (array_slice($query['backtrace'], 1, 5) as $trace) {
                $output[] = sprintf(
                    '  %s%s%s() line %d',
                    isset($trace['class']) ? $trace['class'] : '',
                    isset($trace['type']) ? $trace['type'] : '',
                    $trace['function'],
                    $trace['line'] ?? 0
                );
            }
        }
        
        return implode(PHP_EOL, $output) . PHP_EOL . str_repeat('-', 80);
    }

    private function formatSql(string $sql): string
    {
        $keywords = [
            'SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'ORDER BY', 'GROUP BY',
            'HAVING', 'JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN',
            'LIMIT', 'OFFSET', 'INSERT', 'UPDATE', 'DELETE', 'SET',
            'VALUES', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE', 'BETWEEN',
            'IS NULL', 'IS NOT NULL', 'ASC', 'DESC'
        ];

        // Add line breaks before keywords
        foreach ($keywords as $keyword) {
            $sql = preg_replace('/\b' . $keyword . '\b/i', "\n" . $keyword, $sql);
        }

        // Add proper indentation
        $lines = explode("\n", trim($sql));
        $indentLevel = 0;
        $formattedLines = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Decrease indent for closing parentheses
            if (substr_count($line, ')') > substr_count($line, '(')) {
                $indentLevel = max(0, $indentLevel - 1);
            }

            // Add indentation
            if (!empty($line)) {
                $formattedLines[] = str_repeat('    ', $indentLevel) . $line;
            }

            // Increase indent for opening parentheses
            if (substr_count($line, '(') > substr_count($line, ')')) {
                $indentLevel++;
            }
        }

        return implode("\n", $formattedLines);
    }

    private function interpolateQuery(string $sql, array $params): string
    {
        $keys = [];
        $values = $params;

        // Build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value)) {
                $values[$key] = "'" . addslashes($value) . "'";
            } elseif (is_array($value)) {
                $values[$key] = "'" . implode("','", array_map('addslashes', $value)) . "'";
            } elseif (is_null($value)) {
                $values[$key] = 'NULL';
            } elseif (is_bool($value)) {
                $values[$key] = $value ? '1' : '0';
            }
        }

        return preg_replace($keys, $values, $sql, 1, $count);
    }

    public function explainQuery(SimpleQuery $query): array
    {
        $sql = 'EXPLAIN FORMAT=JSON ' . $query->toSql();
        $params = $query->getParams();

        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $executionTime = microtime(true) - $startTime;

        $result = $stmt->fetch('assoc');
        $explain = json_decode($result['EXPLAIN'], true);

        $this->addQuery($sql, $params, $executionTime);

        return [
            'explain' => $explain,
            'analysis' => $this->analyzeExplainResult($explain)
        ];
    }

    private function analyzeExplainResult(array $explain): array
    {
        $analysis = [
            'warnings' => [],
            'suggestions' => []
        ];

        $this->analyzeNode($explain, $analysis);

        return $analysis;
    }

    private function analyzeNode(array $node, array &$analysis): void
    {
        // Check for full table scans
        if (isset($node['access_type']) && $node['access_type'] === 'ALL') {
            $analysis['warnings'][] = 'Full table scan detected on table: ' . ($node['table_name'] ?? 'unknown');
            $analysis['suggestions'][] = 'Consider adding an index to improve performance';
        }

        // Check for temporary tables
        if (isset($node['using_temporary_table']) && $node['using_temporary_table']) {
            $analysis['warnings'][] = 'Temporary table created';
            $analysis['suggestions'][] = 'Consider optimizing the query to avoid temporary tables';
        }

        // Check for filesorts
        if (isset($node['using_filesort']) && $node['using_filesort']) {
            $analysis['warnings'][] = 'Filesort detected';
            $analysis['suggestions'][] = 'Consider adding an index for ORDER BY columns';
        }

        // Check for poor join types
        if (isset($node['access_type']) && in_array($node['access_type'], ['ALL', 'index'])) {
            if (isset($node['joined_table'])) {
                $analysis['warnings'][] = 'Inefficient join detected on table: ' . ($node['table_name'] ?? 'unknown');
                $analysis['suggestions'][] = 'Consider adding an index on join columns';
            }
        }

        // Recursively analyze child nodes
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $this->analyzeNode($value, $analysis);
            }
        }
    }

    public function getQueryStats(): array
    {
        if (empty($this->queries)) {
            return [
                'total_queries' => 0,
                'total_time' => 0,
                'average_time' => 0,
                'max_time' => 0,
                'min_time' => 0
            ];
        }

        $times = array_column($this->queries, 'execution_time');

        return [
            'total_queries' => count($this->queries),
            'total_time' => array_sum($times),
            'average_time' => array_sum($times) / count($times),
            'max_time' => max($times),
            'min_time' => min($times)
        ];
    }

    public function getSlowestQueries(int $limit = 10): array
    {
        $queries = $this->queries;
        usort($queries, function($a, $b) {
            return $b['execution_time'] <=> $a['execution_time'];
        });

        return array_slice($queries, 0, $limit);
    }

    public function getDuplicateQueries(): array
    {
        $duplicates = [];
        $seen = [];

        foreach ($this->queries as $query) {
            $key = $query['sql'] . serialize($query['params']);
            if (isset($seen[$key])) {
                if (!isset($duplicates[$key])) {
                    $duplicates[$key] = [
                        'sql' => $query['sql'],
                        'params' => $query['params'],
                        'count' => 2,
                        'total_time' => $seen[$key]['execution_time'] + $query['execution_time']
                    ];
                } else {
                    $duplicates[$key]['count']++;
                    $duplicates[$key]['total_time'] += $query['execution_time'];
                }
            } else {
                $seen[$key] = $query;
            }
        }

        return $duplicates;
    }
} 