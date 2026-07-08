<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class Ticket extends Model
{
    use HasFactory;
    use Auditable;

    // Nomes esperados na tabela `ticket_statuses`
    public const STATUS_OPEN = 'aberta';
    public const STATUS_IN_PROGRESS = 'em curso';
    public const STATUS_CLOSED = 'fechada';

    // Prioridades de avaria
    public const PRIORITY_LOW = 'baixa';
    public const PRIORITY_MEDIUM = 'média';
    public const PRIORITY_HIGH = 'alta';

    // Estados do Orçamento
    public const BUDGET_PENDING = 'pending';
    public const BUDGET_APPROVED = 'approved';
    public const BUDGET_REJECTED = 'rejected';

    protected $fillable = [
        'equipment_id',
        'user_id',
        'assigned_to',
        'room_id',
        'status_id',
        'title',
        'description',
        'priority',
        'opened_at',
        'in_progress_at',
        'closed_at',
        'reopened_at',
        'scheduled_at',
        'scheduled_end',
        'scheduled',
        'minutes_spent',
        'cost',
        'budget_requested',
        'budget_status',
        'budget_amount',
        'budget_approved_by',
    ];

    protected $casts = [
        'opened_at'        => 'datetime',
        'in_progress_at'   => 'datetime',
        'closed_at'        => 'datetime',
        'reopened_at'      => 'datetime',
        'scheduled_at'     => 'datetime',
        'scheduled_end'    => 'datetime',
        'scheduled'        => 'boolean',
        'budget_requested' => 'boolean',
        'cost'             => 'decimal:2',
        'budget_amount'    => 'decimal:2',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    public function budgetApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'budget_approved_by');
    }

    public function workflowHistory(): HasMany
    {
        return $this->hasMany(TicketWorkflowHistory::class, 'ticket_id');
    }

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

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Inicia a reparação do ticket mudando o estado para 'em curso'.
     */
    public function startRepair(): bool
    {
        // Em vez de $this->status = 'em curso', procuramos o ID correto pelo nome do estado
        $statusInProgress = TicketStatus::where('name', self::STATUS_IN_PROGRESS)->first();

        if (!$statusInProgress) {
            return false;
        }

        $this->status_id = $statusInProgress->id;
        $this->in_progress_at = now();
        return $this->save();
    }

    /**
     * Fecha o ticket se o custo estiver abaixo do limiar.
     */
    public function checkAutoClose(float $threshold): bool
    {
        if ($this->cost === null) {
            return false;
        }

        if ($this->cost <= $threshold) {
            $statusClosed = TicketStatus::where('name', self::STATUS_CLOSED)->first();
            if ($statusClosed) {
                $this->status_id = $statusClosed->id;
            }
            $this->closed_at = now();
            return $this->save();
        }

        return false;
    }

    public function requestBudgetAuthorization(float $threshold): bool
    {
        if ($this->cost === null) {
            return false;
        }

        if ($this->cost > $threshold) {
            $this->budget_requested = true;
            $this->budget_status = self::BUDGET_PENDING;
            $this->budget_amount = $this->cost;
            return $this->save();
        }

        return false;
    }

    public function approveBudget(User $admin): bool
    {
        if (!$admin->isAdmin()) {
            return false;
        }

        $this->budget_status = self::BUDGET_APPROVED;
        $this->budget_approved_by = $admin->id;
        return $this->save();
    }

    /**
     * Obtém o ID do status pelo nome.
     */
    public static function getStatusIdByName(string $statusName): ?int
    {
        return TicketStatus::where('name', $statusName)->value('id');
    }

    /**
     * Verifica se o ticket está num determinado estado pelo nome.
     */
    public function hasStatus(string $statusName): bool
    {
        if (!$this->status_id) {
            return false;
        }
        
        $statusId = self::getStatusIdByName($statusName);
        return $this->status_id === $statusId;
    }
}
