<?php

namespace SimpleMDB\Backup\Storage;

use SimpleMDB\Backup\BackupException;

/**
 * Local file system storage for backups
 */
class LocalStorage implements StorageInterface
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->ensureDirectoryExists();
    }

    /**
     * Store backup data
     */
    public function store(string $path, $data, array $metadata = []): string
    {
        try {
            $fullPath = $this->basePath . '/' . ltrim($path, '/');
            $directory = dirname($fullPath);
            
            // Create directory if it doesn't exist
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Write data to file
            $bytesWritten = file_put_contents($fullPath, $data);
            
            if ($bytesWritten === false) {
                throw BackupException::storageFailed('store', "Failed to write to file: $fullPath");
            }
            
            // Store metadata if provided
            if (!empty($metadata)) {
                $metadataPath = $fullPath . '.meta';
                file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            }
            
            return $path;
            
        } catch (\Exception $e) {
            throw BackupException::storageFailed('store', $e->getMessage());
        }
    }

    /**
     * Retrieve backup data
     */
    public function retrieve(string $id): ?string
    {
        try {
            $fullPath = $this->basePath . '/' . ltrim($id, '/');
            
            if (!file_exists($fullPath)) {
                return null;
            }
            
            $data = file_get_contents($fullPath);
            
            if ($data === false) {
                throw BackupException::storageFailed('retrieve', "Failed to read file: $fullPath");
            }
            
            return $data;
            
        } catch (\Exception $e) {
            throw BackupException::storageFailed('retrieve', $e->getMessage());
        }
    }

    /**
     * Delete backup
     */
    public function delete(string $id): bool
    {
        try {
            $fullPath = $this->basePath . '/' . ltrim($id, '/');
            
            if (!file_exists($fullPath)) {
                return true; // Already deleted
            }
            
            // Delete main file
            $result = unlink($fullPath);
            
            // Delete metadata file if it exists
            $metadataPath = $fullPath . '.meta';
            if (file_exists($metadataPath)) {
                unlink($metadataPath);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if backup exists
     */
    public function exists(string $id): bool
    {
        $fullPath = $this->basePath . '/' . ltrim($id, '/');
        return file_exists($fullPath);
    }

    /**
     * Get backup metadata
     */
    public function getMetadata(string $id): array
    {
        try {
            $fullPath = $this->basePath . '/' . ltrim($id, '/');
            $metadataPath = $fullPath . '.meta';
            
            if (!file_exists($metadataPath)) {
                return [];
            }
            
            $metadataJson = file_get_contents($metadataPath);
            if ($metadataJson === false) {
                return [];
            }
            
            $metadata = json_decode($metadataJson, true);
            return $metadata ?? [];
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * List all backups
     */
    public function list(): array
    {
        try {
            if (!is_dir($this->basePath)) {
                return [];
            }
            
            $files = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->basePath)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && !str_ends_with($file->getFilename(), '.meta')) {
                    $relativePath = substr($file->getPathname(), strlen($this->basePath) + 1);
                    $files[] = [
                        'id' => $relativePath,
                        'path' => $file->getPathname(),
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime()
                    ];
                }
            }
            
            return $files;
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get storage statistics
     */
    public function getStats(): array
    {
        try {
            $files = $this->list();
            $totalSize = array_sum(array_column($files, 'size'));
            $totalFiles = count($files);
            
            $diskFree = disk_free_space($this->basePath);
            $diskTotal = disk_total_space($this->basePath);
            
            return [
                'total_files' => $totalFiles,
                'total_size' => $totalSize,
                'formatted_size' => $this->formatBytes($totalSize),
                'disk_free' => $diskFree,
                'disk_total' => $diskTotal,
                'disk_used_percent' => $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 2) : 0,
                'storage_path' => $this->basePath
            ];
            
        } catch (\Exception $e) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'formatted_size' => '0 B',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ensure base directory exists
     */
    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->basePath)) {
            if (!mkdir($this->basePath, 0755, true)) {
                throw BackupException::storageFailed('init', "Failed to create storage directory: {$this->basePath}");
            }
        }
        
        if (!is_writable($this->basePath)) {
            throw BackupException::storageFailed('init', "Storage directory is not writable: {$this->basePath}");
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }

    /**
     * Get base path
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }
} 