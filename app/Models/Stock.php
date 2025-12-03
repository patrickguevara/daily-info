<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $connection = 'memory';
    protected $table = 'stocks';

    protected $fillable = [
        'company_name',
        'ticker_symbol',
        'price',
        'fetched_for_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'fetched_for_date' => 'date',
    ];

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('fetched_for_date', $date);
    }
}
