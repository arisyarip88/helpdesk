<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'nomor_tiket',
        'user_id',
        'department_id',
        'judul',
        'deskripsi',
        'comment',
        'prioritas',
        'status_id',
        'lampiran',
        'terselesaikan_pada'
    ];

    protected $casts = [
        'terselesaikan_pada' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'kode');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }
}