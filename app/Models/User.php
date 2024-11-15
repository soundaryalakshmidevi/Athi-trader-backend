<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'user_name',
        'aadhar_number',
        'address',
        'landmark',
        'city',
        'pincode',
        'district',
        'user_type',
        'status',
        'mobile_number',
        'email',
        'alter_mobile_number',
        'profile_photo',
        'sign_photo',
        'nominee_photo',
        'nominee_sign',
        'ref_name',
        'ref_user_id',
        'ref_aadhar_number',
        'qualification',
        'designation',
        'updated_on',
        'added_by',
        'updated_by',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
