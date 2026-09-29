<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketHandlingSetting extends Model
{
    protected $fillable = ['max_hours', 'alert_mode'];

    protected $casts = ['max_hours' => 'integer'];
}
