<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $table = 'knowledge_bases';

    protected $fillable = [
        'question',
        'keywords',
        'response_type',
        'answer',
        'options',
        'is_active',
        'department_id',
    ];

    /**
     * Konversi tipe data otomatis (Array PHP <-> JSON MySQL)
     */
    protected $casts = [
        'keywords' => 'array',
        'options' => 'array',
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'kode');
    }
}
