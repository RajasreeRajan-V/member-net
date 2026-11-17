<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyFeeSetting extends Model
{
    protected $fillable = ['title', 'amount', 'due_day'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    // Always returns the single settings row, creating a sensible default the first time it's touched
    public static function current(): self
    {
        return static::first() ?? static::create([
            'title'   => 'Monthly Membership Fee',
            'amount'  => 200,
            'due_day' => 10,
        ]);
    }
}