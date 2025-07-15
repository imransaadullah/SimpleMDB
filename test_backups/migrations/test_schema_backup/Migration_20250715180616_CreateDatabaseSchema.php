<?php

use SimpleMDB\Migrations\Migration;

/**
 * Auto-generated migration
 * Generated on: 2025-07-15 18:06:16
 */
class CreateDatabaseSchema extends Migration
{
    public function up(): void
    {
        // Create tables in dependency order
        // Create websocket_connections table
        $this->newTable('websocket_connections')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('connection_id')->varchar(100)->notNull()
            ->column('user_id')->int()->unsigned()
            ->column('socket_id')->varchar(100)->notNull()
            ->column('channel')->varchar(100)
            ->column('active')->boolean()->notNull()->default(1)
            ->column('connected_at')->timestamp()->notNull()
            ->column('last_ping')->timestamp()
            ->unique(['connection_id'])->name('connection_id')
            ->safely()
            ->create();

        // Create vital_signs table
        $this->newTable('vital_signs')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('patient_id')->int()->unsigned()->notNull()
            ->column('recorded_by')->int()->unsigned()->notNull()
            ->column('appointment_id')->int()->unsigned()
            ->column('blood_pressure_systolic')->int()
            ->column('blood_pressure_diastolic')->int()
            ->column('heart_rate')->int()
            ->column('temperature')->decimal(4, 1)
            ->column('weight')->decimal(5, 2)
            ->column('height')->decimal(5, 2)
            ->column('oxygen_saturation')->int()
            ->column('respiratory_rate')->int()
            ->column('notes')->text()
            ->column('recorded_at')->timestamp()->notNull()
            ->index(['patient_id'])->name('vital_signs_patient_id_index')
            ->safely()
            ->create();

        // Create users table
        $this->newTable('users')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('unique_id')->varchar(20)->notNull()
            ->column('first_name')->varchar(100)->notNull()
            ->column('last_name')->varchar(100)->notNull()
            ->column('email')->varchar(255)->notNull()
            ->column('password_hash')->varchar(255)->notNull()
            ->column('role')->enum(['admin','doctor','nurse','staff','patient'])->notNull()->default('patient')
            ->column('status')->enum(['active','inactive','suspended'])->notNull()->default('active')
            ->column('encrypted_data')->text()
            ->column('email_verified')->boolean()->notNull()->default(0)
            ->column('mfa_enabled')->boolean()->notNull()->default(0)
            ->column('last_login')->timestamp()
            ->column('failed_login_attempts')->int()->notNull()->default(0)
            ->column('last_failed_login')->timestamp()
            ->column('created_at')->timestamp()->notNull()->default(DB::raw('CURRENT_TIMESTAMP'))
            ->column('updated_at')->timestamp()
            ->unique(['unique_id'])->name('unique_id')
            ->unique(['email'])->name('email')
            ->safely()
            ->create();

        // Create token_blacklist table
        $this->newTable('token_blacklist')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('token')->varchar(255)->notNull()
            ->column('blacklisted_at')->timestamp()->notNull()
            ->column('expires_at')->timestamp()->notNull()
            ->safely()
            ->create();

        // Create prescriptions table
        $this->newTable('prescriptions')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('unique_id')->varchar(20)->notNull()
            ->column('patient_id')->int()->unsigned()->notNull()
            ->column('doctor_id')->int()->unsigned()->notNull()
            ->column('appointment_id')->int()->unsigned()
            ->column('medication_name')->varchar(255)->notNull()
            ->column('dosage')->varchar(100)->notNull()
            ->column('frequency')->varchar(100)->notNull()
            ->column('duration_days')->int()->notNull()
            ->column('instructions')->text()
            ->column('status')->enum(['active','completed','cancelled'])->notNull()->default('active')
            ->column('prescribed_date')->timestamp()->notNull()
            ->column('created_at')->timestamp()->notNull()->default(DB::raw('CURRENT_TIMESTAMP'))
            ->column('updated_at')->timestamp()
            ->unique(['unique_id'])->name('unique_id')
            ->index(['patient_id'])->name('prescriptions_patient_id_index')
            ->safely()
            ->create();

        // Create patients table
        $this->newTable('patients')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('unique_id')->varchar(20)->notNull()
            ->column('user_id')->int()->unsigned()
            ->column('first_name')->varchar(100)->notNull()
            ->column('last_name')->varchar(100)->notNull()
            ->column('email')->varchar(255)
            ->column('phone')->varchar(20)
            ->column('date_of_birth')->date()->notNull()
            ->column('gender')->enum(['male','female','other'])->notNull()
            ->column('address')->text()
            ->column('status')->enum(['active','inactive','deceased'])->notNull()->default('active')
            ->column('encrypted_data')->text()
            ->column('created_at')->timestamp()->notNull()->default(DB::raw('CURRENT_TIMESTAMP'))
            ->column('updated_at')->timestamp()
            ->unique(['unique_id'])->name('unique_id')
            ->index(['user_id'])->name('patients_user_id_index')
            ->safely()
            ->create();

        // Create notifications table
        $this->newTable('notifications')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('user_id')->int()->unsigned()->notNull()
            ->column('type')->varchar(50)->notNull()
            ->column('title')->varchar(200)->notNull()
            ->column('message')->text()->notNull()
            ->column('data')->text()
            ->column('read')->boolean()->notNull()->default(0)
            ->column('read_at')->timestamp()
            ->column('created_at')->timestamp()->notNull()->default(DB::raw('CURRENT_TIMESTAMP'))
            ->column('updated_at')->timestamp()
            ->index(['user_id'])->name('notifications_user_id_index')
            ->safely()
            ->create();

        // Create migrations table
        $this->newTable('migrations')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('migration')->varchar(255)->notNull()->primaryKey()
            ->column('executed_at')->datetime()->notNull()
            ->column('execution_time')->decimal(8, 4)
            ->safely()
            ->create();

        // Create medical_records table
        $this->newTable('medical_records')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('record_number')->varchar(30)->notNull()
            ->column('patient_id')->int()->unsigned()->notNull()
            ->column('patient_unique_id')->varchar(20)->notNull()
            ->column('doctor_id')->int()->unsigned()
            ->column('record_type')->enum(['initial','consultation','procedure','lab_result','prescription','note'])->notNull()
            ->column('title')->varchar(255)->notNull()
            ->column('content')->text()->notNull()
            ->column('attachments')->text()
            ->column('diagnosis_codes')->text()
            ->column('created_by')->int()->unsigned()->notNull()
            ->column('created_at')->timestamp()->notNull()->default(DB::raw('CURRENT_TIMESTAMP'))
            ->column('updated_at')->timestamp()
            ->unique(['record_number'])->name('record_number')
            ->index(['patient_id'])->name('medical_records_patient_id_index')
            ->safely()
            ->create();

        // Create doctors table
        $this->newTable('doctors')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('unique_id')->varchar(20)->notNull()
            ->column('user_id')->int()->unsigned()->notNull()
            ->column('license_number')->varchar(50)->notNull()
            ->column('specialization')->varchar(100)
            ->column('department')->varchar(100)
            ->column('phone')->varchar(20)
            ->column('email')->varchar(255)
            ->column('status')->enum(['active','inactive','on_leave'])->notNull()->default('active')
            ->column('consultation_fee')->decimal(10, 2)->notNull()->default(0)
            ->column('available_hours')->text()
            ->column('created_at')->timestamp()->notNull()->default(DB::raw('CURRENT_TIMESTAMP'))
            ->column('updated_at')->timestamp()
            ->unique(['unique_id'])->name('unique_id')
            ->unique(['license_number'])->name('license_number')
            ->index(['user_id'])->name('doctors_user_id_index')
            ->safely()
            ->create();

        // Create debug_posts table
        $this->newTable('debug_posts')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('title')->varchar(255)->notNull()
            ->column('content')->text()->notNull()
            ->column('status')->enum(['draft','published'])->notNull()
            ->column('author')->varchar(100)
            ->safely()
            ->create();

        // Create backups table
        $this->newTable('backups')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->varchar(255)->notNull()->primaryKey()
            ->column('name')->varchar(255)->notNull()
            ->column('database_name')->varchar(255)->notNull()
            ->column('type')->varchar(50)->notNull()
            ->column('size')->bigInt()->unsigned()->notNull()
            ->column('checksum')->varchar(255)
            ->column('storage_type')->varchar(50)->notNull()->default('local')
            ->column('storage_path')->varchar(255)->notNull()
            ->column('metadata')->text()
            ->column('created_at')->datetime()->notNull()
            ->index(['name', 'created_at'])->name('name_created_index')
            ->index(['type'])->name('type_index')
            ->safely()
            ->create();

        // Create audit_log table
        $this->newTable('audit_log')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('user_id')->int()->unsigned()
            ->column('action')->varchar(100)->notNull()
            ->column('table_name')->varchar(100)
            ->column('record_id')->int()->unsigned()
            ->column('old_values')->text()
            ->column('new_values')->text()
            ->column('ip_address')->varchar(45)
            ->column('user_agent')->text()
            ->column('created_at')->timestamp()->notNull()
            ->safely()
            ->create();

        // Create appointments table
        $this->newTable('appointments')
            ->collation('utf8mb4_0900_ai_ci')
            ->column('id')->int()->unsigned()->notNull()->autoIncrement()->primaryKey()
            ->column('unique_id')->varchar(20)->notNull()
            ->column('patient_id')->int()->unsigned()->notNull()
            ->column('doctor_id')->int()->unsigned()->notNull()
            ->column('appointment_date')->date()->notNull()
            ->column('appointment_time')->time()->notNull()
            ->column('duration_minutes')->int()->notNull()->default(30)
            ->column('type')->enum(['consultation','follow_up','procedure','emergency'])->notNull()->default('consultation')
            ->column('status')->enum(['scheduled','confirmed','in_progress','completed','cancelled','no_show'])->notNull()->default('scheduled')
            ->column('reason')->varchar(500)
            ->column('notes')->text()
            ->column('consultation_fee')->decimal(10, 2)->notNull()->default(0)
            ->column('created_by')->int()->unsigned()->notNull()
            ->column('created_at')->timestamp()->notNull()->default(DB::raw('CURRENT_TIMESTAMP'))
            ->column('updated_at')->timestamp()
            ->unique(['unique_id'])->name('unique_id')
            ->index(['patient_id'])->name('appointments_patient_id_index')
            ->index(['doctor_id'])->name('appointments_doctor_id_index')
            ->safely()
            ->create();

    }

    public function down(): void
    {
        // Drop tables in reverse dependency order
        $this->dropTable('appointments');
        $this->dropTable('audit_log');
        $this->dropTable('backups');
        $this->dropTable('debug_posts');
        $this->dropTable('doctors');
        $this->dropTable('medical_records');
        $this->dropTable('migrations');
        $this->dropTable('notifications');
        $this->dropTable('patients');
        $this->dropTable('prescriptions');
        $this->dropTable('token_blacklist');
        $this->dropTable('users');
        $this->dropTable('vital_signs');
        $this->dropTable('websocket_connections');
    }
}
