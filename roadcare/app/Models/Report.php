<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = ['reference', 'user_id', 'title', 'description', 'category', 'severity', 'city', 'address', 'latitude', 'longitude', 'photo_path', 'status', 'department', 'resolved_at'];
    protected $hidden = ['photo_path'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float', 'resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ReportUpdate::class)->orderBy('id');
    }
}
