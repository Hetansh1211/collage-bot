<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class RefreshUserCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh user credits to 300 every 8 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = now()->subHours(User::CREDIT_REFRESH_HOURS);

        $refreshedCount = User::whereNull('last_credit_refresh')
            ->orWhere('last_credit_refresh', '<=', $cutoff)
            ->update([
                'credit_points' => User::MAX_CREDIT_POINTS,
                'last_credit_refresh' => now(),
            ]);

        $this->info("Credits refreshed for {$refreshedCount} user(s).");
        return Command::SUCCESS;
    }
}
