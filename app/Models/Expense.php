<?php

// app/Models/Expense.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
    'title', 'category', 'amount', 'expense_date', 'expense_time',
    'paid_to', 'status', 'created_by', 'unit_id',   // ← add this
];

    protected $casts = [
        'expense_date' => 'date',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}