<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class Reservation extends Model
{
    protected $fillable = ['session_id', 'seat_id', 'user_id', 'status', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(Screening::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'confirmed')
              ->orWhere(function ($q) {
                  $q->where('status', 'pending')
                    ->where('expires_at', '>', now());
              });
    }
}
