<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_updates_enabled',
        'new_products_enabled'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
