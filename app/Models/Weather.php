<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Weather extends Model
{
    use HasFactory;

    protected $connection = 'memory';
    protected $table = 'weather';

    protected $fillable = [
        'location',
        'temperature',
        'description',
        'fetched_for_date',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'fetched_for_date' => 'date',
    ];

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('fetched_for_date', $date);
    }
}
