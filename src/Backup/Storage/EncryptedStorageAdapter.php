<?php

namespace SimpleMDB\Backup\Storage;

use SimpleMDB\Backup\BackupException;

/**
 * Encrypted storage adapter that wraps any existing storage adapter
 * 
 * This adapter provides transparent encryption/decryption of backup data
 * while maintaining full backward compatibility. Users can wrap any
 * existing storage adapter to add encryption without changing their code.
 */
class EncryptedStorageAdapter implements StorageInterface
{
    private StorageInterface $baseStorage;
    private string $encryptionKey;
    private string $cipher;

    public function __construct(
        StorageInterface $baseStorage,
        string $encryptionKey,
        string $cipher = 'aes-256-cbc'
    ) {
        $this->baseStorage = $baseStorage;
        $this->encryptionKey = $encryptionKey;
        $this->cipher = $cipher;
        
        // Validate cipher is supported
        if (!in_array($this->cipher, openssl_get_cipher_methods())) {
            throw BackupException::invalidConfiguration("Unsupported cipher: {$cipher}");
        }
        
        // Validate key length for chosen cipher
        $this->validateKeyLength();
    }

    /**
     * Store encrypted backup data
     */
    public function store(string $path, $data, array $metadata = []): string
    {
        try {
            // Encrypt the data
            $encryptedData = $this->encrypt($data);
            
            // Add encryption metadata
            $encryptionMetadata = [
                'encrypted' => true,
                'cipher' => $this->cipher,
                'encrypted_at' => date('Y-m-d H:i:s'),
                'original_size' => strlen($data),
                'encrypted_size' => strlen($encryptedData)
            ];
            
            $mergedMetadata = array_merge($metadata, $encryptionMetadata);
            
            // Store encrypted data using base storage
            return $this->baseStorage->store($path, $encryptedData, $mergedMetadata);
            
        } catch (\Exception $e) {
            throw BackupException::storageFailed('encrypt_store', $e->getMessage());
        }
    }

    /**
     * Retrieve and decrypt backup data
     */
    public function retrieve(string $id): ?string
    {
        try {
            // Get encrypted data from base storage
            $encryptedData = $this->baseStorage->retrieve($id);
            
            if ($encryptedData === null) {
                return null;
            }
            
            // Check if data is actually encrypted
            $metadata = $this->getMetadata($id);
            if (!isset($metadata['encrypted']) || !$metadata['encrypted']) {
                // Data is not encrypted, return as-is
                return $encryptedData;
            }
            
            // Decrypt the data
            return $this->decrypt($encryptedData);
            
        } catch (\Exception $e) {
            throw BackupException::storageFailed('decrypt_retrieve', $e->getMessage());
        }
    }

    /**
     * Delete backup (delegates to base storage)
     */
    public function delete(string $id): bool
    {
        return $this->baseStorage->delete($id);
    }

    /**
     * Check if backup exists (delegates to base storage)
     */
    public function exists(string $id): bool
    {
        return $this->baseStorage->exists($id);
    }

    /**
     * Get backup metadata including encryption info
     */
    public function getMetadata(string $id): array
    {
        return $this->baseStorage->getMetadata($id);
    }

    /**
     * List all backups (delegates to base storage)
     */
    public function list(): array
    {
        return $this->baseStorage->list();
    }

    /**
     * Get storage statistics including encryption overhead
     */
    public function getStats(): array
    {
        $baseStats = $this->baseStorage->getStats();
        
        // Add encryption-specific stats
        $baseStats['encryption'] = [
            'enabled' => true,
            'cipher' => $this->cipher,
            'key_length' => strlen($this->encryptionKey) * 8 . ' bits'
        ];
        
        return $baseStats;
    }

    /**
     * Encrypt data using configured cipher
     */
    private function encrypt(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($data, $this->cipher, $this->encryptionKey, 0, $iv);
        
        if ($encrypted === false) {
            throw BackupException::storageFailed('encryption', 'Failed to encrypt data');
        }
        
        // Prepend IV to encrypted data for decryption
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data using configured cipher
     */
    private function decrypt(string $encryptedData): string
    {
        try {
            $data = base64_decode($encryptedData);
            
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            
            $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->encryptionKey, 0, $iv);
            
            if ($decrypted === false) {
                throw BackupException::storageFailed('decryption', 'Failed to decrypt data');
            }
            
            return $decrypted;
            
        } catch (\Exception $e) {
            throw BackupException::storageFailed('decryption', $e->getMessage());
        }
    }

    /**
     * Validate encryption key length for chosen cipher
     */
    private function validateKeyLength(): void
    {
        $requiredLength = match(strtolower($this->cipher)) {
            'aes-128-cbc' => 16,
            'aes-192-cbc' => 24,
            'aes-256-cbc' => 32,
            'aes-128-gcm' => 16,
            'aes-192-gcm' => 24,
            'aes-256-gcm' => 32,
            default => 32 // Default to 256-bit
        };
        
        if (strlen($this->encryptionKey) !== $requiredLength) {
            throw BackupException::invalidConfiguration(
                "Invalid key length for {$this->cipher}. Expected {$requiredLength} bytes, got " . 
                strlen($this->encryptionKey)
            );
        }
    }

    /**
     * Generate secure encryption key
     */
    public static function generateKey(string $cipher = 'aes-256-cbc'): string
    {
        $keyLength = match(strtolower($cipher)) {
            'aes-128-cbc', 'aes-128-gcm' => 16,
            'aes-192-cbc', 'aes-192-gcm' => 24,
            'aes-256-cbc', 'aes-256-gcm' => 32,
            default => 32
        };
        
        return random_bytes($keyLength);
    }

    /**
     * Get base64 encoded key for storage
     */
    public static function encodeKey(string $key): string
    {
        return base64_encode($key);
    }

    /**
     * Decode base64 encoded key
     */
    public static function decodeKey(string $encodedKey): string
    {
        return base64_decode($encodedKey);
    }

    /**
     * Get the base storage adapter
     */
    public function getBaseStorage(): StorageInterface
    {
        return $this->baseStorage;
    }

    /**
     * Get cipher being used
     */
    public function getCipher(): string
    {
        return $this->cipher;
    }
} 