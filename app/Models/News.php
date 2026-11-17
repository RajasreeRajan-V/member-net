<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'content',
        'image',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────
    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
    public function reads()
{
    return $this->hasMany(\App\Models\NewsRead::class, 'news_id');
}
}