<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    // Specify the table name (optional, if the table name is not the plural of the model name)
    protected $table = 'city';

    // Specify the primary key (optional, if it's not 'id')
    protected $primaryKey = 'city_id';

    // Disable timestamps if you do not want to use created_at and updated_at (optional)
    public $timestamps = true; // This is the default value, so you can omit this line if you want timestamps.

    // Specify the fillable properties for mass assignment
    protected $fillable = [
        'city_name',
        'pincode',
    ];

    // If you want to set hidden properties for JSON serialization (optional)
    protected $hidden = [
        // Add fields to hide when converting to JSON
    ];

}
