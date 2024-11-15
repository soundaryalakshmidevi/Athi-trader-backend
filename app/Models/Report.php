<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'report'; // Specify the table name

    protected $fillable = [
        'file_name',
        'generated_by',
    ];

    // Optionally, you can set the timestamps property
    public $timestamps = false; // Disable automatic timestamps if you are managing them manually
}
