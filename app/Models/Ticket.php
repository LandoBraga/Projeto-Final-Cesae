<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\User;
use App\Models\Equipment;
use App\Models\Room;

/**
 * Modelo `Ticket` representa um registo de avaria/solicitação de intervenção.
 *
 * Campos importantes:
 * - `status`: controlado pelas constantes STATUS_* (aberta, em curso, fechada).
 * - `cost`: custo estimado/valor cobrado pela intervenção.
 * - orçamento: suporte a pedido/aprovação de orçamentos com campos
 *   `budget_requested`, `budget_status`, `budget_amount`, `budget_approved_by`.
 *
 * Métodos de conveniência abaixo implementam ações do Técnico e do ADM
 * conforme requisitos: iniciar reparação, pedir autorização de orçamento,
 * fechar ticket automaticamente se o valor for baixo e aprovar orçamentos.
 */
class Ticket extends Model
{
    use HasFactory;

    // Estados do ticket usados pela aplicação (texto em PT para UI/DB)
    public const STATUS_OPEN = 'aberta';
    public const STATUS_IN_PROGRESS = 'em curso';
    public const STATUS_CLOSED = 'fechada';

    // Estados do processo de orçamento (internos, em inglês para compatibilidade)
    public const BUDGET_PENDING = 'pending';
    public const BUDGET_APPROVED = 'approved';
    public const BUDGET_REJECTED = 'rejected';

    /**
     * Campos preenchíveis em massa (mass assignment).
     * Inclui campos de orçamento adicionados pela migration correspondente.
     */
    protected $fillable = [
        'user_id',
        'assigned_to',
        'equipment_id',
        'room_id',
        'title',
        'description',
        'status',
        'opened_at',
        'in_progress_at',
        'closed_at',
        'minutes_spent',
        'cost',
        'budget_requested',
        'budget_status',
        'budget_amount',
        'budget_approved_by',
    ];

    /**
     * Casts para tipos nativos ao serializar/atribuir atributos.
     */
    protected $casts = [
        'opened_at' => 'datetime',
        'in_progress_at' => 'datetime',
        'closed_at' => 'datetime',
        'minutes_spent' => 'integer',
        'cost' => 'decimal:2',
        'budget_requested' => 'boolean',
        'budget_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Inicia reparação por um Técnico.
     * - Atribui `assigned_to` ao id do técnico
     * - Define `status` para `em curso`
     * - Regista `in_progress_at`
     */
    public function startRepair(User $technician): void
    {
        $this->assigned_to = $technician->id;
        $this->status = self::STATUS_IN_PROGRESS;
        $this->in_progress_at = now();
        $this->save();
    }

    /**
     * Fecha o ticket automaticamente se o `cost` for menor ou igual ao
     * limiar (`$threshold`) fornecido.
     * - Só opera quando o ticket estiver `em curso`.
     * - Regista `closed_at` e altera `status` para `fechada`.
     * Retorna `true` se o ticket foi fechado por esta operação.
     */
    public function closeIfLowValue(float $threshold): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        if ($this->cost === null) {
            return false;
        }

        if ($this->cost <= $threshold) {
            $this->status = self::STATUS_CLOSED;
            $this->closed_at = now();
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Solicita autorização de orçamento quando o `cost` do ticket ultrapassa
     * o limiar informado.
     * - Marca `budget_requested = true` e `budget_status = pending`.
     * - Copia `cost` para `budget_amount`.
     * Retorna `true` se um pedido de orçamento foi criado.
     */
    public function requestBudgetAuthorization(float $threshold): bool
    {
        if ($this->cost === null) {
            return false;
        }

        if ($this->cost > $threshold) {
            $this->budget_requested = true;
            $this->budget_status = self::BUDGET_PENDING;
            $this->budget_amount = $this->cost;
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Aprova o pedido de orçamento.
     * - Verifica que o utilizador passado tem perfil de ADM
     * - Define `budget_status = approved` e grava `budget_approved_by`.
     * Retorna `true` se a aprovação foi efectuada com sucesso.
     */
    public function approveBudget(User $admin): bool
    {
        if (! $admin->isAdmin()) {
            return false;
        }

        $this->budget_status = self::BUDGET_APPROVED;
        $this->budget_approved_by = $admin->id;
        $this->save();

        return true;
    }
}
