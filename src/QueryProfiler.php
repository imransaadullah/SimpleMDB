<?php

namespace SimpleMDB;

class QueryProfiler
{
    private array $queries = [];
    private float $startTime;
    private float $endTime;
    private array $explainResults = [];
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
        $this->startTime = microtime(true);
    }

    public function addQuery(string $sql, array $params, float $executionTime, ?array $explainResult = null): void
    {
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'explain' => $explainResult
        ];
    }

    public function explain(SimpleQuery $query): array
    {
        $sql = 'EXPLAIN FORMAT=JSON ' . $query->toSql();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($query->getParams());
        $result = $stmt->fetch('assoc');
        
        $this->explainResults[] = $result;
        return $result;
    }

    public function analyzeQuery(SimpleQuery $query): array
    {
        $explainResult = $this->explain($query);
        
        // Analyze potential issues
        $issues = [];
        
        if ($this->hasFullTableScan($explainResult)) {
            $issues[] = [
                'type' => 'performance',
                'severity' => 'high',
                'message' => 'Query performs a full table scan. Consider adding appropriate indexes.'
            ];
        }
        
        if ($this->hasTemporaryTable($explainResult)) {
            $issues[] = [
                'type' => 'performance',
                'severity' => 'medium',
                'message' => 'Query creates temporary tables. Consider optimizing JOINs or adding indexes.'
            ];
        }
        
        if ($this->hasFileSort($explainResult)) {
            $issues[] = [
                'type' => 'performance',
                'severity' => 'medium',
                'message' => 'Query requires filesort. Consider adding indexes for ORDER BY columns.'
            ];
        }

        return [
            'explain_result' => $explainResult,
            'issues' => $issues,
            'suggestions' => $this->generateOptimizationSuggestions($explainResult)
        ];
    }

    private function hasFullTableScan(array $explainResult): bool
    {
        $json = json_decode($explainResult['EXPLAIN'], true);
        return $this->searchExplainTree($json, function($node) {
            return isset($node['access_type']) && $node['access_type'] === 'ALL';
        });
    }

    private function hasTemporaryTable(array $explainResult): bool
    {
        $json = json_decode($explainResult['EXPLAIN'], true);
        return $this->searchExplainTree($json, function($node) {
            return isset($node['using_temporary_table']) && $node['using_temporary_table'] === true;
        });
    }

    private function hasFileSort(array $explainResult): bool
    {
        $json = json_decode($explainResult['EXPLAIN'], true);
        return $this->searchExplainTree($json, function($node) {
            return isset($node['using_filesort']) && $node['using_filesort'] === true;
        });
    }

    private function searchExplainTree(array $node, callable $predicate): bool
    {
        if ($predicate($node)) {
            return true;
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                if ($this->searchExplainTree($value, $predicate)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function generateOptimizationSuggestions(array $explainResult): array
    {
        $suggestions = [];
        $json = json_decode($explainResult['EXPLAIN'], true);

        // Analyze table access methods
        if ($this->hasFullTableScan($explainResult)) {
            $suggestions[] = [
                'type' => 'index',
                'message' => 'Consider adding indexes for frequently filtered columns'
            ];
        }

        // Analyze JOIN operations
        if ($this->hasNestedLoopJoin($json)) {
            $suggestions[] = [
                'type' => 'join',
                'message' => 'Consider optimizing JOIN conditions or adding composite indexes'
            ];
        }

        // Analyze temporary tables
        if ($this->hasTemporaryTable($explainResult)) {
            $suggestions[] = [
                'type' => 'structure',
                'message' => 'Consider denormalizing or adding covering indexes to avoid temporary tables'
            ];
        }

        return $suggestions;
    }

    private function hasNestedLoopJoin(array $node): bool
    {
        return $this->searchExplainTree($node, function($node) {
            return isset($node['nested_loop']) && $node['nested_loop'] === true;
        });
    }

    public function getQueryCount(): int
    {
        return count($this->queries);
    }

    public function getTotalExecutionTime(): float
    {
        return array_sum(array_column($this->queries, 'execution_time'));
    }

    public function getAverageExecutionTime(): float
    {
        return $this->getTotalExecutionTime() / $this->getQueryCount();
    }

    public function getMaxExecutionTime(): float
    {
        return max(array_column($this->queries, 'execution_time'));
    }

    public function getPeakMemoryUsage(): int
    {
        return max(array_column($this->queries, 'peak_memory'));
    }

    public function getReport(): array
    {
        return [
            'query_count' => $this->getQueryCount(),
            'total_execution_time' => $this->getTotalExecutionTime(),
            'average_execution_time' => $this->getAverageExecutionTime(),
            'max_execution_time' => $this->getMaxExecutionTime(),
            'peak_memory_usage' => $this->getPeakMemoryUsage(),
            'queries' => $this->queries,
            'explain_results' => $this->explainResults
        ];
    }
} 