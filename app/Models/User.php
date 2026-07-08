<?php

namespace App\Models;

use App\Models\Ticket;
use App\Models\Userprofile as UserProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** @var string */
    protected $table = 'users';

    /** @var array<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_id',
        'active',
        'api_token',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
        '_tokens',
        '_password_hash',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'active'            => 'boolean',
    ];

    // Role constants - mapped to profile names for consistency
    public const ROLE_USER      = 'user';
    public const ROLE_TECHNICIAN= 'technician';
    public const ROLE_ADMIN     = 'admin';

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany */
    protected function getTicketsRelation(): BelongsTo
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany */
    protected function getAssignedTicketsRelation(): BelongsTo
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function profile()
    {
        return $this->belongsTo(UserProfile::class, 'profile_id');
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
    public function isCommonUser(): bool
    {
        return $this->profile?->name === self::ROLE_USER;
    }

    /**
     * Obtém todas as constantes de role disponíveis para o utilizador.
     */
    public static function getAvailableRoles(): array
    {
        return [self::ROLE_USER, self::ROLE_TECHNICIAN, self::ROLE_ADMIN];
    }

    /**
     * Verifica se um perfil é válido (tem nome correspondente a uma role).
     */
    protected function isValidProfile(string $profileName): bool
    {
        return in_array($profileName, [self::ROLE_USER, self::ROLE_TECHNICIAN, self::ROLE_ADMIN]);
    }

    /**
     * Garante que o utilizador tem um perfil válido ao criar.
     */
    protected static function booting(): void
    {
        parent::booting();

        static::creating(function ($user) {
            // Garantir que o utilizador tem um perfil válido
            if (!$user->profile_id || !$this->isValidProfile($user->profile?->name ?? '')) {
                $defaultRole = self::ROLE_USER;

                // Procurar por um profile com a role padrão ou criar se não existir
                $existingProfile = UserProfile::where('name', $defaultRole)->first();

                if (!$existingProfile) {
                    UserProfile::create(['name' => $defaultRole]);
                    $existingProfile = UserProfile::where('name', $defaultRole)->first();
                }

                // Se ainda não tem profile_id, atribuir o padrão
                if ($user->profile_id === null || !$this->isValidProfile($user->profile?->name ?? '')) {
                    $user->profile_id = $existingProfile->id;
                }
            }
        });
    }

    /**
     * Garante que um utilizador tem perfil válido ao atualizar.
     */
    protected static function updating($model)
    {
        parent::updating($model);

        if (!$this->isValidProfile($model->profile?->name ?? '')) {
            $defaultRole = self::ROLE_USER;

            // Procurar por um profile com a role padrão ou criar se não existir
            $existingProfile = UserProfile::where('name', $defaultRole)->first();

            if (!$existingProfile) {
                UserProfile::create(['name' => $defaultRole]);
                $existingProfile = UserProfile::where('name', $defaultRole)->first();
            }

            // Se ainda não tem profile_id, atribuir o padrão
            if ($model->profile_id === null || !$this->isValidProfile($model->profile?->name ?? '')) {
                $model->profile_id = $existingProfile->id;
            }
        }
    }
}
