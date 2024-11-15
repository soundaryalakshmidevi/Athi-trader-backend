<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'loan'; // Specify the table name

    protected $fillable = [
        'loan_id',
        'user_id',
        'employee_id',
        'category_id',
        'loan_amount',
        'loan_category',
        'loan_date',
        'total_amount',
        'status',
        'image',
        'added_on',
        'updated_on',
        'loan_closed_date',
    ];

    public $timestamps = false; // Disable automatic timestamps if you are managing them manually
}
