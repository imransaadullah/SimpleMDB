<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleMDB\DatabaseFactory;
use SimpleMDB\Backup\BackupManager;
use SimpleMDB\Backup\BackupType;

/**
 * Complete Backup System Example
 * 
 * This example demonstrates the full range of backup capabilities in SimpleMDB v4.1.0,
 * including schema analysis, migration generation, and comprehensive backup strategies.
 */

// Database connection
$db = DatabaseFactory::create(
    'mysqli',
    'localhost',
    'root',
    'password',
    'healthcare_db'
);

// Initialize backup manager
$backupManager = new BackupManager($db, 'backups');

echo "=== SimpleMDB v4.1.0 Complete Backup System Demo ===\n\n";

// ========================================
// 1. SCHEMA BACKUP WITH MIGRATION GENERATION
// ========================================
echo "1. Creating schema backup with expressive migrations...\n";

$schemaBackup = $backupManager->backup('schema_with_migrations')
    ->schemaOnly()
    ->generateMigrations()
    ->useExpressiveSyntax()
    ->compress('gzip')
    ->description('Complete database schema with expressive SimpleMDB migrations')
    ->tag('schema')
    ->tag('migrations')
    ->execute();

if ($schemaBackup->isSuccess()) {
    echo "✓ Schema backup created: {$schemaBackup->getId()}\n";
    echo "  Size: {$schemaBackup->getFormattedSize()}\n";
    echo "  Migrations generated in: backups/migrations/schema_with_migrations/\n";
} else {
    echo "✗ Schema backup failed: {$schemaBackup->getErrorMessage()}\n";
}

// ========================================
// 2. SELECTIVE TABLE BACKUP WITH SPLIT MIGRATIONS
// ========================================
echo "\n2. Creating selective backup with split migration files...\n";

$selectiveBackup = $backupManager->backup('user_data_backup')
    ->full()
    ->includeTables(['users', 'user_profiles', 'user_settings', 'user_sessions'])
    ->generateMigrations()
    ->tablesPerFile(2) // Split into multiple migration files
    ->useExpressiveSyntax()
    ->encrypt('your-encryption-key-here')
    ->compress('gzip')
    ->description('User-related tables with split migrations')
    ->tags(['users', 'selective', 'encrypted'])
    ->execute();

if ($selectiveBackup->isSuccess()) {
    echo "✓ Selective backup created: {$selectiveBackup->getId()}\n";
    echo "  Size: {$selectiveBackup->getFormattedSize()}\n";
    echo "  Split migrations generated\n";
}

// ========================================
// 3. FULL DATABASE BACKUP (NO MIGRATIONS)
// ========================================
echo "\n3. Creating complete database backup...\n";

$fullBackup = $backupManager->backup('complete_database')
    ->full()
    ->compress('bzip2')
    ->description('Complete database backup for disaster recovery')
    ->tag('full')
    ->tag('disaster-recovery')
    ->execute();

if ($fullBackup->isSuccess()) {
    echo "✓ Full backup created: {$fullBackup->getId()}\n";
    echo "  Size: {$fullBackup->getFormattedSize()}\n";
}

// ========================================
// 4. INCREMENTAL BACKUP
// ========================================
echo "\n4. Creating incremental backup...\n";

$incrementalBackup = $backupManager->backup('daily_incremental')
    ->incremental($fullBackup->getId()) // Base it on the full backup
    ->compress('gzip')
    ->description('Daily incremental backup')
    ->tag('incremental')
    ->execute();

if ($incrementalBackup->isSuccess()) {
    echo "✓ Incremental backup created: {$incrementalBackup->getId()}\n";
    echo "  Size: {$incrementalBackup->getFormattedSize()}\n";
}

// ========================================
// 5. DATA-ONLY BACKUP (FOR SEEDING)
// ========================================
echo "\n5. Creating data-only backup for seeding...\n";

$dataBackup = $backupManager->backup('seed_data')
    ->dataOnly()
    ->includeTables(['countries', 'states', 'cities', 'specialties'])
    ->compress('gzip')
    ->description('Reference data for seeding new environments')
    ->tags(['seed', 'reference-data'])
    ->execute();

if ($dataBackup->isSuccess()) {
    echo "✓ Data backup created: {$dataBackup->getId()}\n";
    echo "  Size: {$dataBackup->getFormattedSize()}\n";
}

// ========================================
// 6. LIST ALL BACKUPS
// ========================================
echo "\n6. Listing all backups...\n";

$backups = $backupManager->list();
echo "Found " . count($backups) . " backups:\n";

foreach ($backups as $backup) {
    echo "  • {$backup->getName()} ({$backup->getId()})\n";
    echo "    Type: {$backup->getType()->value}, Size: {$backup->getFormattedSize()}\n";
    echo "    Created: {$backup->getCreatedAt()->format('Y-m-d H:i:s')}\n";
    echo "    Tags: " . implode(', ', $backup->getTags()) . "\n";
    echo "    Description: {$backup->getDescription()}\n\n";
}

// ========================================
// 7. RESTORE EXAMPLES
// ========================================
echo "7. Restore operations...\n";

// Preview restore (don't actually execute)
echo "  Previewing schema restore...\n";
$restorePreview = $backupManager->restore($schemaBackup->getId())
    ->to('healthcare_db_copy')
    ->preview();

echo "  Restore would create " . count($restorePreview['tables']) . " tables\n";

// Actually restore to a different database
echo "  Restoring user data to test database...\n";
$restoreResult = $backupManager->restore($selectiveBackup->getId())
    ->to('test_database')
    ->createSnapshot() // Create a snapshot before restore
    ->execute();

if ($restoreResult->isSuccess()) {
    echo "✓ Restore completed successfully\n";
    echo "  Restored to: test_database\n";
    echo "  Tables restored: " . count($restoreResult->getRestoredTables()) . "\n";
} else {
    echo "✗ Restore failed: {$restoreResult->getMessage()}\n";
}

// ========================================
// 8. BACKUP VERIFICATION
// ========================================
echo "\n8. Verifying backups...\n";

foreach ([$schemaBackup, $fullBackup, $dataBackup] as $backup) {
    $isValid = $backupManager->verify($backup->getId());
    $status = $isValid ? '✓ Valid' : '✗ Invalid';
    echo "  {$backup->getName()}: $status\n";
}

// ========================================
// 9. MIGRATION GENERATION STANDALONE
// ========================================
echo "\n9. Standalone migration generation...\n";

use SimpleMDB\Backup\SchemaAnalyzer;
use SimpleMDB\Backup\MigrationGenerator;

// Analyze current schema
$analyzer = new SchemaAnalyzer($db);
$schemaData = $analyzer->analyzeDatabase();

echo "  Analyzed database: {$schemaData['database']}\n";
echo "  Found " . count($schemaData['tables']) . " tables\n";
echo "  Found " . count($schemaData['relationships']) . " relationships\n";

// Generate migrations with different options
$generator = new MigrationGenerator($schemaData, [
    'expressive_syntax' => true,
    'split_tables' => true,
    'tables_per_file' => 3,
    'use_comments' => true,
    'preserve_order' => true
]);

$migrationFiles = $generator->generateMigrations('migrations/standalone');
echo "  Generated " . count($migrationFiles) . " migration files\n";

foreach ($migrationFiles as $file) {
    echo "    - " . basename($file) . "\n";
}

// ========================================
// 10. ADVANCED FEATURES DEMONSTRATION
// ========================================
echo "\n10. Advanced features...\n";

// Get table creation order
$tableOrder = $analyzer->getTableCreationOrder();
echo "  Table creation order: " . implode(' → ', $tableOrder) . "\n";

// Generate migration for specific table
$usersTable = $schemaData['tables']['users'] ?? null;
if ($usersTable) {
    $singleTableMigration = $generator->generateTableMigration('users', $usersTable);
    echo "  Generated migration for 'users' table\n";
}

// Backup with custom storage options
$customBackup = $backupManager->backup('custom_storage')
    ->schemaOnly()
    ->store('custom://special-location')
    ->generateMigrations()
    ->tablesPerFile(10)
    ->maxFileSize(5 * 1024 * 1024) // 5MB max file size
    ->execute();

if ($customBackup->isSuccess()) {
    echo "✓ Custom storage backup created\n";
}

echo "\n=== Demo completed successfully! ===\n";
echo "\nKey features demonstrated:\n";
echo "• Schema analysis and table dependency resolution\n";
echo "• Expressive migration generation with fluent API\n";
echo "• Multiple backup types (full, schema, data, incremental)\n";
echo "• Compression and encryption support\n";
echo "• Selective table backup and restore\n";
echo "• Split migration files for large schemas\n";
echo "• Backup verification and integrity checking\n";
echo "• Comprehensive metadata management\n";
echo "• Preview and snapshot capabilities\n";

// Example of generated migration content
echo "\n=== Example Generated Migration ===\n";
echo "File: Migration_20241201_120000_CreateUsersTable.php\n\n";

$sampleMigration = <<<'PHP'
<?php

use SimpleMDB\Migrations\Migration;

/**
 * Auto-generated migration
 * Generated on: 2024-12-01 12:00:00
 */
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        // Create users table - User accounts and authentication
        $this->newTable('users')
            ->engine('InnoDB')
            ->charset('utf8mb4')
            ->collation('utf8mb4_unicode_ci')
            ->comment('User accounts and authentication')
            ->column('id')->bigInt()->notNull()->autoIncrement()->primaryKey()
            ->column('email')->varchar(255)->notNull()->unique()
            ->column('password')->varchar(255)->notNull()
            ->column('first_name')->varchar(100)->notNull()
            ->column('last_name')->varchar(100)->notNull()
            ->column('phone')->varchar(20)
            ->column('email_verified_at')->timestamp()
            ->column('is_active')->boolean()->default(1)
            ->column('created_at')->timestamp()->default(DB::raw('CURRENT_TIMESTAMP'))
            ->column('updated_at')->timestamp()->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
            ->index(['email'])->name('idx_users_email')
            ->index(['created_at'])->name('idx_users_created')
            ->safely()
            ->create();
    }

    public function down(): void
    {
        // Drop tables in reverse dependency order
        $this->dropTable('users');
    }
}
PHP;

echo $sampleMigration;
echo "\n\n"; 