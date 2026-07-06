<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Room;
use App\Models\Ticket;
use App\Traits\Auditable;

class Equipment extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'name',
        'serial',
        'room_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
