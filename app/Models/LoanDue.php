<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDue extends Model
{
    use HasFactory;

    protected $table = 'loan_due'; // Specify the table name

    protected $fillable = [
        'loan_id',
        'user_id',
        'next_amount',    // Now handled as decimal in the database
        'pending_amount',
        'due_amount',    // Now handled as decimal in the database
        'paid_amount',   // Nullable, so can be empty initially
        'due_date',
        'paid_on',
        'collection_by',
        'status',        // Default is 'unpaid', as set in the migration
    ];

    // Relationships (if needed)
    // For example, if `loan_id` is a foreign key, you can define the relationship
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id'); // Assuming 'loan_id' is the column name in 'loan' table
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); // Assuming 'id' is the primary key in the 'users' table
    }
}
