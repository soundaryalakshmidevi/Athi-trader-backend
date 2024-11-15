<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'Transaction';

    protected $fillable = [
        'loan_id',
        'user_id',
        'employee_id',
        'category_id',
        'loan_amount',
        'loan_category',
        'loan_date',
        'total_amount',
        'image',
        'status',
        'loan_closed_date',
        'next_amount',
        'pending_amount',
        'due_amount',
        'paid_amount',
        'due_date',
        'paid_on',
        'collection_by',
        'payment_status',
    ];

    // If the timestamps field is not the default, you need to specify them
    public $timestamps = true;

    // You can add relationships or custom methods here if needed
}
