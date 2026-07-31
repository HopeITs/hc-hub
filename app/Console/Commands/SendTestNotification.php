<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Console\Command;

class SendTestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:test {email? : Email of the user to notify} {--count=1 : How many test notifications to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test database notification(s) to a user to preview the notification badge/dropdown';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Which user email should receive the test notification?');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");

            return self::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));

        for ($i = 0; $i < $count; $i++) {
            $user->notify(new TestNotification);
        }

        $this->info("Sent {$count} test notification(s) to {$user->email}. Unread total: {$user->unreadNotifications()->count()}");

        return self::SUCCESS;
    }
}
