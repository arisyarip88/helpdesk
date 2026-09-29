<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'nomor_tiket',
        'user_id',
        'category_id',
        'judul',
        'deskripsi',
        'comment',
        'prioritas',
        'status_id',
        'resolved_by',
        'rating',
        'lampiran',
        'terselesaikan_pada',
    ];

    protected $casts = [
        'terselesaikan_pada' => 'datetime',
        'rating' => 'integer',
    ];

    protected $appends = ['department'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getDepartmentAttribute(): ?Department
    {
        return $this->category?->department;
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(TicketWarning::class);
    }

    public function scopeOverdue(Builder $query, int $maxHours): Builder
    {
        $cutoff = now()->subHours($maxHours);
        $driver = DB::connection()->getDriverName();

        return $query->where(function (Builder $builder) use ($cutoff, $driver, $maxHours) {
            $builder->where(function (Builder $unresolved) use ($cutoff) {
                $unresolved->whereNull('terselesaikan_pada')
                    ->whereNotIn('status_id', [4, 5])
                    ->where('created_at', '<=', $cutoff);
            })->orWhere(function (Builder $resolved) use ($driver, $maxHours) {
                $resolved->whereNotNull('terselesaikan_pada');

                if ($driver === 'sqlite') {
                    $resolved->whereRaw(
                        '(julianday(terselesaikan_pada) - julianday(created_at)) * 24 > ?',
                        [$maxHours]
                    );
                } elseif ($driver === 'pgsql') {
                    $resolved->whereRaw(
                        'EXTRACT(EPOCH FROM (terselesaikan_pada - created_at)) > ?',
                        [$maxHours * 3600]
                    );
                } else {
                    $resolved->whereRaw(
                        'TIMESTAMPDIFF(SECOND, created_at, terselesaikan_pada) > ?',
                        [$maxHours * 3600]
                    );
                }
            });
        });
    }
}
