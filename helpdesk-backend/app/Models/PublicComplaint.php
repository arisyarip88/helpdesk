<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicComplaint extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'category_id', 'description',
        'attachment_path', 'attachment_name', 'status', 'ip_address',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
