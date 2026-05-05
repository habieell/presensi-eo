<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Notification\NotificationDispatcher;

class UserObserver
{
    public function __construct(private NotificationDispatcher $notif) {}

    public function created(User $user): void
    {
        ActivityLogger::user(
            userId: $user->id,
            action: 'created',
            after: $user->only(['name', 'email', 'role', 'nik', 'position']),
            notificationSent: true,
        );

        // Notify admin
        $this->notif->profileChanged($user, 'NEW_USER', null, $user->name, 'created');
    }

    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        unset($changes['updated_at'], $changes['remember_token'], $changes['email_verified_at'], $changes['password']);

        if (empty($changes)) return;

        $original = collect($user->getOriginal())->only(array_keys($changes))->all();

        foreach ($changes as $field => $newValue) {
            $oldValue = $original[$field] ?? null;
            ActivityLogger::user(
                userId: $user->id,
                action: 'updated',
                field: $field,
                before: $oldValue,
                after: $newValue,
                notificationSent: true,
            );

            // Send WA notification ke admin per field penting
            if (in_array($field, ['name', 'email', 'phone', 'position', 'nik', 'role', 'is_active', 'avatar'])) {
                $this->notif->profileChanged($user, $field, $oldValue, $newValue, 'updated');
            }
        }
    }

    public function deleted(User $user): void
    {
        ActivityLogger::user(
            userId: $user->id,
            action: 'deleted',
            before: $user->only(['name', 'email']),
            notificationSent: true,
        );

        $this->notif->profileChanged($user, 'DELETED', $user->name, null, 'deleted');
    }
}
