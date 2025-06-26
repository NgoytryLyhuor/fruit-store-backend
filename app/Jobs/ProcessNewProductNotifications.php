<?php

namespace App\Jobs;

use App\Models\Fruit;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNewProductNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fruit;
    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $maxExceptions = 3;

    public function __construct(Fruit $fruit)
    {
        $this->fruit = $fruit;
    }

    public function handle()
    {
        Log::info('Starting notification job for new product', [
            'fruit_id' => $this->fruit->id,
            'fruit_name' => $this->fruit->name
        ]);

        // Get users with notification preferences enabled - using pluck for better performance
        $userIds = User::whereHas('notificationSettings', function($query) {
                $query->where('new_products_enabled', true);
            })
            ->whereNotNull('email')
            ->pluck('id');

        Log::info('Users to notify', ['count' => $userIds->count()]);

        if ($userIds->isEmpty()) {
            Log::info('No users to notify');
            return;
        }

        // Process in chunks to avoid memory issues and rate limiting
        $userIds->chunk(25)->each(function($chunk, $chunkIndex) {
            foreach($chunk as $index => $userId) {
                // Dispatch individual notification job for each user
                // Add staggered delay to prevent overwhelming mail server
                $delay = now()->addSeconds(($chunkIndex * 25 + $index) * 2);

                SendProductNotificationToUser::dispatch($this->fruit->id, $userId)
                    ->onQueue('emails')
                    ->delay($delay);
            }
        });

        Log::info('Finished dispatching notification jobs', [
            'fruit_id' => $this->fruit->id,
            'total_jobs' => $userIds->count()
        ]);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('ProcessNewProductNotifications job failed', [
            'fruit_id' => $this->fruit->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
