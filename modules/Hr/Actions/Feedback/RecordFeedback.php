<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Feedback;

use App\Models\Role;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Core\Models\User;
use Modules\Hr\Models\InAppFeedback;

/**
 * Stores one in-app feedback note with exactly the context the form shows:
 * the page it was sent from, the language and the sender's active role. A
 * person may send five notes an hour.
 */
final class RecordFeedback
{
    public const array Categories = ['problem', 'idea', 'question'];

    public const int MaxLength = 2000;

    public const int PerHour = 5;

    /**
     * Whether this person may send another note now.
     */
    public function allows(User $user): bool
    {
        return ! RateLimiter::tooManyAttempts($this->key($user), self::PerHour);
    }

    public function handle(User $user, ?string $category, string $message, ?string $routeName): InAppFeedback
    {
        RateLimiter::hit($this->key($user), 3600);

        $feedback = new InAppFeedback;
        $feedback->user_id = (int) $user->id;
        $feedback->category = in_array($category, self::Categories, true) ? $category : null;
        $feedback->message = mb_substr(trim($message), 0, self::MaxLength);
        $feedback->route_name = $routeName !== null ? mb_substr($routeName, 0, 255) : null;
        $feedback->locale = app()->getLocale();
        $feedback->role_name = $user->active_role_id === null ? null : Role::query()->whereKey($user->active_role_id)->value('name');
        $feedback->save();

        return $feedback;
    }

    private function key(User $user): string
    {
        return 'hr-feedback:'.$user->id;
    }
}
