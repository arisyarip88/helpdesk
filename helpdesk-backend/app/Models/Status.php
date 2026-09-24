<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'statuses';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relasi One-to-Many ke Model User
     */
    public function users()
    {
        return $this->hasMany(User::class, 'status_id', 'id');
    }
}