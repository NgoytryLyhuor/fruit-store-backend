<?php

namespace App\Jobs;

use App\Models\Fruit;
use App\Models\User;
use App\Mail\NewProductNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendProductNotificationToUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fruitId;
    public $userId;
    public $tries = 3;
    public $timeout = 60; // 1 minute
    public $maxExceptions = 3;

    public function __construct($fruitId, $userId)
    {
        $this->fruitId = $fruitId;
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            // Fetch fresh models to avoid stale data
            $fruit = Fruit::find($this->fruitId);
            $user = User::find($this->userId);

            if (!$fruit || !$user) {
                Log::warning('Fruit or User not found', [
                    'fruit_id' => $this->fruitId,
                    'user_id' => $this->userId
                ]);
                return;
            }

            if (!$user->email) {
                Log::warning('User has no email', ['user_id' => $this->userId]);
                return;
            }

            Log::info('Processing notification for user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'fruit_id' => $fruit->id
            ]);

            // Create database notification
            $user->notifications()->create([
                'type' => 'new_product',
                'message' => "New product available: {$fruit->name}",
                'data' => json_encode([
                    'product_id' => $fruit->id,
                    'product_name' => $fruit->name,
                    'product_price' => $fruit->price,
                    'product_image' => $fruit->image_url
                ])
            ]);

            // Send email notification
            Mail::to($user->email)->send(new NewProductNotification($fruit, $user));

            Log::info('Successfully notified user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'fruit_id' => $fruit->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify user', [
                'user_id' => $this->userId,
                'fruit_id' => $this->fruitId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SendProductNotificationToUser job failed permanently', [
            'user_id' => $this->userId,
            'fruit_id' => $this->fruitId,
            'error' => $exception->getMessage()
        ]);
    }
}
