<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 1. Import Trait Sanctum

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // 2. Tambahkan HasApiTokens di sini

    protected $fillable = [
        'username',
        'role_id',
      
        'department_id',
        'name',
        'email',
        'tlp',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'kode');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    
}