<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\ServiceRegistrySeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * One demo account per role so every role can be tested immediately.
     */
    public function run(): void
    {
        $this->call(ServiceRegistrySeeder::class);

        $accounts = [
            // ── Admin ──────────────────────────────────────────────────────
            [
                'name'              => 'Admin',
                'email'             => 'admin@example.com',
                'password'          => 'Admin@Password1',
                'role'              => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
            // ── Student (Step 1 — pending admission, before exam) ─────────
            [
                'name'              => 'Student',
                'email'             => 'student@example.com',
                'password'          => 'Student@Password1',
                'role'              => User::ROLE_STUDENT,
                'admission_status'  => User::ADMISSION_PENDING,
                'enrollment_status' => User::ENROLLMENT_NOT_ENROLLED,
                'clearcheck_passed' => false,
                'email_verified_at' => now(),
            ],
            // ── Student (Step 3 — approved, not yet enrolled) ──────────────
            [
                'name'              => 'Student Approved',
                'email'             => 'student.approved@example.com',
                'password'          => 'Student@Password1',
                'role'              => User::ROLE_STUDENT,
                'admission_status'  => User::ADMISSION_APPROVED,
                'enrollment_status' => User::ENROLLMENT_NOT_ENROLLED,
                'clearcheck_passed' => false,
                'email_verified_at' => now(),
            ],
            // ── Student (Step 4 — enrolled, clearcheck pending) ────────────
            [
                'name'              => 'Student Enrolled',
                'email'             => 'student.enrolled@example.com',
                'password'          => 'Student@Password1',
                'role'              => User::ROLE_STUDENT,
                'admission_status'  => User::ADMISSION_APPROVED,
                'enrollment_status' => User::ENROLLMENT_ENROLLED,
                'clearcheck_passed' => false,
                'email_verified_at' => now(),
            ],
            // ── Student (Step 5 — fully cleared, full access) ──────────────
            [
                'name'              => 'Student Cleared',
                'email'             => 'student.cleared@example.com',
                'password'          => 'Student@Password1',
                'role'              => User::ROLE_STUDENT,
                'admission_status'  => User::ADMISSION_APPROVED,
                'enrollment_status' => User::ENROLLMENT_ENROLLED,
                'clearcheck_passed' => true,
                'email_verified_at' => now(),
            ],
            // ── Instructor ─────────────────────────────────────────────────
            [
                'name'              => 'Instructor',
                'email'             => 'instructor@example.com',
                'password'          => 'Instructor@Password1',
                'role'              => User::ROLE_INSTRUCTOR,
                'email_verified_at' => now(),
            ],
            // ── Cashier ────────────────────────────────────────────────────
            [
                'name'              => 'Cashier',
                'email'             => 'cashier@example.com',
                'password'          => 'Cashier@Password1',
                'role'              => User::ROLE_CASHIER,
                'email_verified_at' => now(),
            ],
            // ── Librarian ──────────────────────────────────────────────────
            [
                'name'              => 'Librarian',
                'email'             => 'librarian@example.com',
                'password'          => 'Librarian@Password1',
                'role'              => User::ROLE_LIBRARIAN,
                'email_verified_at' => now(),
            ],
            // ── Admission Officer ──────────────────────────────────────────
            [
                'name'              => 'Admission Officer',
                'email'             => 'admission@example.com',
                'password'          => 'Admission@Password1',
                'role'              => User::ROLE_ADMISSION_OFFICER,
                'email_verified_at' => now(),
            ],
            // ── Career Officer ─────────────────────────────────────────────
            [
                'name'              => 'Career Officer',
                'email'             => 'career@example.com',
                'password'          => 'Career@Password1',
                'role'              => User::ROLE_CAREER_OFFICER,
                'email_verified_at' => now(),
            ],
            // ── Nurse / Health Officer ─────────────────────────────────────
            [
                'name'              => 'Nurse',
                'email'             => 'nurse@example.com',
                'password'          => 'Nurse@Password1234',
                'role'              => User::ROLE_NURSE,
                'email_verified_at' => now(),
            ],
            // ── Election Officer ───────────────────────────────────────────
            [
                'name'              => 'Election Officer',
                'email'             => 'election@example.com',
                'password'          => 'Election@Password1',
                'role'              => User::ROLE_ELECTION_OFFICER,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account
            );
        }
    }
}
