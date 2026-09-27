<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnansweredChatQuestion extends Model
{
    protected $fillable = [
        'question',
        'question_hash',
        'occurrences',
        'last_asked_at',
        'knowledge_base_id',
        'resolved_at',
    ];

    protected $casts = [
        'occurrences' => 'integer',
        'last_asked_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class);
    }
}