<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * UserEnrollmentSyncService
 * 
 * Synchronizes enrollment status from EnrollEase to DEORIS User database.
 * Called when StudentEnrolled, EnrollmentApproved, or EnrollmentRejected events arrive.
 */
class UserEnrollmentSyncService
{
    /**
     * Update user enrollment status when StudentEnrolled event received.
     * 
     * @param  array<string, mixed>  $payload
     * @return bool  Whether the update was successful
     */
    public function enrollStudent(array $payload): bool
    {
        try {
            $email = $payload['student_email'] ?? $payload['email'] ?? null;
            $studentId = $payload['user_id'] ?? null;

            if (!$email) {
                Log::warning('[UserEnrollmentSync] StudentEnrolled: Missing email', [
                    'payload' => $payload,
                ]);
                return false;
            }

            $user = $this->findUser($email, $studentId);

            // If user doesn't exist, create them
            if (!$user) {
                $user = $this->createUserFromEnrollment($payload);
                if (!$user) {
                    Log::warning('[UserEnrollmentSync] StudentEnrolled: Could not create user', [
                        'email' => $email,
                        'user_id' => $studentId,
                    ]);
                    return false;
                }
                Log::info('[UserEnrollmentSync] StudentEnrolled: User created', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            $oldStatus = $user->enrollment_status;

            $user->update([
                'enrollment_status' => User::ENROLLMENT_ENROLLED,
                'enrollease_enrollment_id' => $payload['enrollment_id'] ?? null,
                'enrollment_status_synced_at' => now(),
                'previous_enrollment_status' => $oldStatus,
            ]);

            Log::info('[UserEnrollmentSync] StudentEnrolled: Status updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_status' => $oldStatus,
                'new_status' => User::ENROLLMENT_ENROLLED,
                'enrollment_id' => $payload['enrollment_id'] ?? null,
                'synced_at' => $user->enrollment_status_synced_at->toIso8601String(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[UserEnrollmentSync] StudentEnrolled failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return false;
        }
    }

    /**
     * Update user admission status when EnrollmentApproved event received.
     * 
     * @param  array<string, mixed>  $payload
     * @return bool
     */
    public function approveEnrollment(array $payload): bool
    {
        try {
            $email = $payload['student_email'] ?? $payload['email'] ?? null;
            $studentId = $payload['user_id'] ?? null;

            if (!$email) {
                Log::warning('[UserEnrollmentSync] EnrollmentApproved: Missing email', [
                    'payload' => $payload,
                ]);
                return false;
            }

            $user = $this->findUser($email, $studentId);

            if (!$user) {
                $user = $this->createUserFromEnrollment($payload);
                if (!$user) {
                    Log::warning('[UserEnrollmentSync] EnrollmentApproved: Could not create user', [
                        'email' => $email,
                    ]);
                    return false;
                }
            }

            $oldAdmissionStatus = $user->admission_status;

            $user->update([
                'admission_status' => User::ADMISSION_APPROVED,
                'enrollease_enrollment_id' => $payload['enrollment_id'] ?? $user->enrollease_enrollment_id,
                'enrollment_status_synced_at' => now(),
            ]);

            Log::info('[UserEnrollmentSync] EnrollmentApproved: Status updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_admission_status' => $oldAdmissionStatus,
                'new_admission_status' => User::ADMISSION_APPROVED,
                'enrollment_id' => $payload['enrollment_id'] ?? null,
                'synced_at' => $user->enrollment_status_synced_at->toIso8601String(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[UserEnrollmentSync] EnrollmentApproved failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return false;
        }
    }

    /**
     * Update user admission status when EnrollmentRejected event received.
     * 
     * @param  array<string, mixed>  $payload
     * @return bool
     */
    public function rejectEnrollment(array $payload): bool
    {
        try {
            $email = $payload['student_email'] ?? $payload['email'] ?? null;
            $studentId = $payload['user_id'] ?? null;

            if (!$email) {
                Log::warning('[UserEnrollmentSync] EnrollmentRejected: Missing email', [
                    'payload' => $payload,
                ]);
                return false;
            }

            $user = $this->findUser($email, $studentId);

            if (!$user) {
                $user = $this->createUserFromEnrollment($payload);
                if (!$user) {
                    Log::warning('[UserEnrollmentSync] EnrollmentRejected: Could not create user', [
                        'email' => $email,
                    ]);
                    return false;
                }
            }

            $oldAdmissionStatus = $user->admission_status;

            $user->update([
                'admission_status' => User::ADMISSION_REJECTED,
                'enrollease_enrollment_id' => $payload['enrollment_id'] ?? $user->enrollease_enrollment_id,
                'enrollment_status_synced_at' => now(),
            ]);

            Log::info('[UserEnrollmentSync] EnrollmentRejected: Status updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_admission_status' => $oldAdmissionStatus,
                'new_admission_status' => User::ADMISSION_REJECTED,
                'remarks' => $payload['remarks'] ?? null,
                'enrollment_id' => $payload['enrollment_id'] ?? null,
                'synced_at' => $user->enrollment_status_synced_at->toIso8601String(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[UserEnrollmentSync] EnrollmentRejected failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return false;
        }
    }

    /**
     * Find user by email or user_id.
     * 
     * @param  string|null  $email
     * @param  mixed|null   $userId
     * @return User|null
     */
    private function findUser(?string $email, mixed $userId): ?User
    {
        if ($userId) {
            $user = User::find($userId);

            if ($user) {
                return $user;
            }
        }

        if ($email) {
            return User::where('email', $email)->first();
        }

        return null;
    }

    /**
     * Create a new user from enrollment data if they don't exist.
     * 
     * @param  array<string, mixed>  $payload
     * @return User|null
     */
    private function createUserFromEnrollment(array $payload): ?User
    {
        $email = $payload['student_email'] ?? $payload['email'] ?? null;

        if (!$email) {
            return null;
        }

        // Check if user already exists
        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $existing;
        }

        try {
            $user = User::create([
                'name'               => $payload['student_name'] ?? "{$payload['first_name']} {$payload['last_name']}",
                'email'              => $email,
                'student_number'     => $payload['lrn'] ?? null,
                'password'           => str()->password(32),
                'role'               => User::ROLE_STUDENT,
                'admission_status'   => User::ADMISSION_PENDING,
                'enrollment_status'  => User::ENROLLMENT_NOT_ENROLLED,
                'email_verified_at'  => now(),
            ]);

            Log::info('[UserEnrollmentSync] User created from enrollment', [
                'user_id' => $user->id,
                'email' => $user->email,
                'enrollment_id' => $payload['enrollment_id'] ?? null,
            ]);

            return $user;
        } catch (\Throwable $e) {
            Log::error('[UserEnrollmentSync] Failed to create user from enrollment', [
                'error' => $e->getMessage(),
                'email' => $email,
                'enrollment_id' => $payload['enrollment_id'] ?? null,
            ]);
            return null;
        }
    }
}
