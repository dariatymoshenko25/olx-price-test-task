<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = ['ad_id', 'email', 'email_verified_at'];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
