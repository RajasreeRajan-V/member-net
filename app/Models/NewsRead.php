<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsRead extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'news_id', 'read_at'];
}