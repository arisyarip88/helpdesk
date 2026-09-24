<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    // Tentukan primary key baru
    protected $primaryKey = 'kode';

    // Jika kode berbentuk string/varchar, matikan auto-incrementing
    public $incrementing = false;

    // Tentukan tipe data primary key
    protected $keyType = 'string';

    protected $fillable = ['kode', 'nama', 'ketua', 'deskripsi'];

    /**
     * Relasi One-to-Many ke model Ticket
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'department_id', 'kode');
    }
}