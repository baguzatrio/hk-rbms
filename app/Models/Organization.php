<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\LogOptions;

class Organization extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Organization')
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function beforeActivityLogged(Activity $activity, string $eventName)
    {
        if ($eventName === 'created') {
            $activity->description = "Membuat organisasi: {$this->name}";
        }

        if ($eventName === 'updated') {
            $activity->description = "Mengubah organisasi: {$this->name}";
        }

        if ($eventName === 'deleted') {
            $activity->description = "Menghapus organisasi: {$this->name}";
        }
    }

    protected $fillable = ['name', 'shortname'];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }
}
