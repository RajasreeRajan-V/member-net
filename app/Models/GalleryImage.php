<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $fillable = ['image', 'created_by', 'is_active'];

    // Who uploaded this image (super admin, district admin or unit admin)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}