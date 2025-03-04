<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;



class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function Prestataire()
    {
        return $this->hasOne(Prestataire::class);
    }

    public function reclamation()
    {
        return $this->hasMany(Reclamation::class);
    }

    public function annonce()
    {
        return $this->hasMany(Annonce::class);
    }
    public function annonces()
    {
        return $this->hasMany(Annonce::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'phone',
        'role',
        'email',
        'password',
        'banned_at',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
