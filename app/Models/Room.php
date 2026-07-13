<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\LogOptions;

class Room extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Room')
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function beforeActivityLogged(Activity $activity, string $eventName)
    {
        if ($eventName === 'created') {
            $activity->description = "Membuat ruangan: {$this->name}";
        }

        if ($eventName === 'updated') {
            $activity->description = "Mengubah ruangan: {$this->name}";
        }

        if ($eventName === 'deleted') {
            $activity->description = "Menghapus ruangan: {$this->name}";
        }
    }

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
