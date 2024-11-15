<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;

class Login extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_type',
        'status',
        'mobile_number',
        'email',
        'updated_on',
        'added_by',
        'updated_by',
        'password',
        'security_password',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Implementing JWTSubject methods
    // public function getJWTIdentifier()
    // {
    //     return (string) $this->getKey();  // Ensure this returns the user's ID
    // }

    // public function getJWTCustomClaims()
    // {
    //     return []; // Add any custom claims if necessary
    // }
}
