<?php

namespace App\Models;

use App\Models\Ticket;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\Auditable;

class User extends Authenticatable
{
    // Mantemos as constantes de mapeamento textual para bater com o 'name' do perfil
    public const ROLE_USER = 'user';
    public const ROLE_TECHNICIAN = 'technician';
    public const ROLE_ADMIN = 'admin';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_id',
        'active',
        'api_token',
    ];

    /**
     * The attributes that should be validated on creation.
     */
    protected static function booted(): void
    {
        static::creating(function ($user) {
            // Garantir que o utilizador tem um perfil válido
            if (!$user->profile_id) {
                $defaultProfile = UserProfile::where('name', self::ROLE_USER)->first();
                if ($defaultProfile) {
                    $user->profile_id = $defaultProfile->id;
                }
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'active'            => 'boolean',
    ];

    /**
     * Relação: O utilizador pertence a um perfil específico.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'profile_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * Verifica se o utilizador é Administrador através do relacionamento com a tabela de perfis.
     */
    public function isAdmin(): bool
    {
        return $this->profile?->name === self::ROLE_ADMIN;
    }

    /**
     * Verifica se o utilizador é Técnico através do relacionamento com a tabela de perfis.
     */
    public function isTechnician(): bool
    {
        return $this->profile?->name === self::ROLE_TECHNICIAN;
    }

    /**
     * Verifica se o utilizador é Utilizador Comum através do relacionamento com a tabela de perfis.
     */
    public function isCommon(): bool
    {
        return $this->profile?->name === self::ROLE_USER;
    }
}
