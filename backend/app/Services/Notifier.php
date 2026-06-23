<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Lightweight notification dispatcher. Persists a database notification and
 * broadcasts it live on the user's private channel. `data` carries the display
 * payload the frontend renders: title, body, and an optional url/icon.
 */
class Notifier
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function send(User $user, string $type, array $data): DatabaseNotification
    {
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'data' => $data,
        ]);

        NotificationCreated::dispatch($notification);

        return $notification;
    }

    /**
     * Send the same notification to many users (e.g. all of a store's staff).
     *
     * @param  iterable<User>  $users
     * @param  array<string, mixed>  $data
     */
    public static function sendMany(iterable $users, string $type, array $data): void
    {
        foreach ($users as $user) {
            self::send($user, $type, $data);
        }
    }
}
