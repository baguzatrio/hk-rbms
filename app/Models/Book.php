<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id', 'requester_id', 'title',
        'start_date', 'end_date', 'start_hour', 'finish_hour',
        'status', 'notes', 'remarks',
        'approved_by', 'approved_at', 'cancelled_at',
        'pic_name', 'participants',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'approved_at'  => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // ── Relasi ──────────────────────────────────────────

    public function room(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function requester(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ──────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    // ── Helpers ─────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Cek apakah booking ini overlap dengan range tanggal & jam tertentu.
     * Digunakan untuk conflict detection saat membuat booking baru.
     */
    public static function hasConflict(
        int $roomId,
        string $startDate,
        string $endDate,
        string $startHour,
        string $finishHour,
        ?int $excludeId = null
    ): bool {
        return static::where('room_id', $roomId)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->where(function ($q) use ($startHour, $finishHour) {
                $q->where(function ($q2) use ($startHour, $finishHour) {
                    $q2->where('start_hour', '<', $finishHour)
                       ->where('finish_hour', '>', $startHour);
                });
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
