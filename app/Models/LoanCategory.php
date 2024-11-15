<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCategory extends Model
{
    use HasFactory;

    protected $table = 'loan_category'; // Specify the table name if it’s not the plural form of the model name

    protected $fillable = [
        'category_id',
        'category_name',
        'category_type',
        'duration',
        'interest_rate',
        'status',
        'added_on',
        'updated_on',
    ];

    public $timestamps = false; // Disable automatic timestamps if you are managing them manually
}
