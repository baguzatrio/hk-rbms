<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'floor', 'capacity',
        'description', 'is_active', 'photo_path',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
    ];

    /**
     * Auto-generate slug dari name jika tidak disediakan.
     */
    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            if (empty($room->slug)) {
                $room->slug = Str::slug($room->name);
            }
        });
    }

    public function books(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * Scope: hanya ruangan aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
