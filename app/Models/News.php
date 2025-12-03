<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class News extends Model
{
    use HasFactory;

    protected $connection = 'memory';
    protected $table = 'news';

    protected $fillable = [
        'headline',
        'description',
        'url',
        'source',
        'published_at',
        'fetched_for_date',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'fetched_for_date' => 'date',
    ];

    public function relatedData(): HasMany
    {
        return $this->hasMany(NewsRelatedData::class);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('fetched_for_date', $date);
    }
}
