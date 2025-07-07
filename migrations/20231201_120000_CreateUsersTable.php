<?php

use SimpleMDB\Migrations\Migration;

class Migration_20231201_120000_CreateUsersTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('users', function($table) {
            $table->integer('id', unsigned: true, autoIncrement: true)->primaryKey('id');
            $table->string('name', 100);
            $table->string('email', 150)->unique(['email']);
            $table->string('password', 255);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('status', 'active');
            $table->timestamps();
        });

        // Add index for email lookups
        $this->addIndex('users', ['email'], 'users_email_index');

        // Insert default admin user
        $this->insert('users', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'status' => 'active',
            'created_at' => $this->now(),
            'updated_at' => $this->now()
        ]);
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('users');
    }
} 