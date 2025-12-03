<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsRelatedData extends Model
{
    use HasFactory;

    protected $connection = 'memory';
    protected $table = 'news_related_data';

    protected $fillable = [
        'news_id',
        'weather_id',
        'stock_id',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function weather(): BelongsTo
    {
        return $this->belongsTo(Weather::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
