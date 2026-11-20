<?php

namespace Database\Factories;

use App\Models\AccessGrant;
use App\Models\Permission;
use App\Models\Role;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\User;

/**
 * @extends Factory<AccessGrant>
 */
class AccessGrantFactory extends Factory
{
    /**
     * An open-ended grant of one permission; say where with on().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'permission_id' => fn (): int => (int) Permission::findOrCreate('projects.update', 'web')->getKey(),
            'scope_type' => 'project',
            'scope_id' => 1,
        ];
    }

    public function on(string $scopeType, int $scopeId): static
    {
        return $this->state(['scope_type' => $scopeType, 'scope_id' => $scopeId]);
    }

    public function permission(string $name): static
    {
        return $this->state(['role_id' => null, 'permission_id' => fn (): int => (int) Permission::findOrCreate($name, 'web')->getKey()]);
    }

    public function role(string $name): static
    {
        return $this->state(['permission_id' => null, 'role_id' => fn (): int => (int) Role::findOrCreate($name, 'web')->getKey()]);
    }

    public function between(?CarbonInterface $startsAt, ?CarbonInterface $endsAt): static
    {
        return $this->state(['starts_at' => $startsAt, 'ends_at' => $endsAt]);
    }

    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()]);
    }
}
