<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Equipment;
use App\Traits\Auditable;
use App\Models\Ticket;

class Room extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'name',
        'location',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
