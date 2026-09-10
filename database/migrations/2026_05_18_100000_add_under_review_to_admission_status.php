<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Expands the admission_status ENUM on the users table to include 'under_review'.
 *
 * Before: pending | approved | rejected
 * After:  pending | under_review | approved | rejected
 *
 * EntryEase sets admission_status = 'under_review' when a registrar marks an
 * application as "Under Review". Without this value in the DEORIS enum, the status
 * sync to the portal fails.
 *
 * Supports both MySQL (ENUM type) and PostgreSQL (CHECK constraint).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return; // SQLite doesn't strictly enforce CHECK constraints
        }

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE users
                MODIFY COLUMN admission_status
                ENUM('pending', 'under_review', 'approved', 'rejected')
                NOT NULL DEFAULT 'pending'
            ");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Drop the existing CHECK constraint and create a new one
            // with the expanded set of allowed values
            DB::statement("
                ALTER TABLE users
                DROP CONSTRAINT IF EXISTS users_admission_status_check
            ");

            DB::statement("
                ALTER TABLE users
                ADD CONSTRAINT users_admission_status_check
                CHECK (admission_status IN ('pending', 'under_review', 'approved', 'rejected'))
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // Reset any under_review rows back to pending before reverting the constraint
        DB::statement("UPDATE users SET admission_status = 'pending' WHERE admission_status = 'under_review'");

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE users
                MODIFY COLUMN admission_status
                ENUM('pending', 'approved', 'rejected')
                NOT NULL DEFAULT 'pending'
            ");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Restore the original CHECK constraint
            DB::statement("
                ALTER TABLE users
                DROP CONSTRAINT IF EXISTS users_admission_status_check
            ");

            DB::statement("
                ALTER TABLE users
                ADD CONSTRAINT users_admission_status_check
                CHECK (admission_status IN ('pending', 'approved', 'rejected'))
            ");
        }
    }
};
