<?php

declare(strict_types=1);

namespace App\Services\Administrator;

use App\Models\Raffle;

class RaffleManagementService
{
    /**
     * Listar raffles com filtros e paginação
     */
    public function listRaffles(array $filters = [], int $perPage = 15): array
    {
        $query = Raffle::with('creator');

        // Aplicar filtros
        if (isset($filters['status']) && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['min_prize']) && $filters['min_prize'] > 0) {
            $query->where('prize_value', '>=', $filters['min_prize']);
        }

        if (isset($filters['max_prize']) && $filters['max_prize'] > 0) {
            $query->where('prize_value', '<=', $filters['max_prize']);
        }

        // Busca por título/descrição
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Ordenação
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        if (in_array($sortBy, ['created_at', 'title', 'prize_value', 'status'])) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $raffles = $query->paginate($perPage);

        return [
            'raffles' => $raffles,
            'filters' => [
                'status' => $filters['status'] ?? null,
                'search' => $filters['search'] ?? null,
                'min_prize' => $filters['min_prize'] ?? null,
                'max_prize' => $filters['max_prize'] ?? null,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ];
    }

    /**
     * Buscar raffle por UUID
     */
    public function findRaffleByUuid(string $uuid): Raffle
    {
        return Raffle::with('creator')->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Criar novo raffle
     */
    public function createRaffle(array $data): Raffle
    {
        $raffle = Raffle::create($data);

        return $raffle->load('creator');
    }

    /**
     * Atualizar raffle
     */
    public function updateRaffle(Raffle $raffle, array $data): Raffle
    {
        $raffle->update($data);

        return $raffle->fresh()->load('creator');
    }

    /**
     * Deletar raffle (soft delete)
     */
    public function deleteRaffle(string $uuid): bool
    {
        $raffle = Raffle::where('uuid', $uuid)->firstOrFail();

        return $raffle->delete();
    }

    /**
     * Restaurar raffle deletado
     */
    public function restoreRaffle(string $uuid): Raffle
    {
        $raffle = Raffle::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $raffle->restore();

        return $raffle->load('creator');
    }

    /**
     * Alternar status do raffle
     */
    public function toggleRaffleStatus(string $uuid): array
    {
        $raffle = Raffle::where('uuid', $uuid)->firstOrFail();

        $newStatus = $raffle->status === 'active' ? 'inactive' : 'active';
        $raffle->update(['status' => $newStatus]);

        return [
            'raffle' => $raffle->load('creator'),
            'new_status' => $newStatus,
            'message' => "Status alterado para {$newStatus}",
        ];
    }

    /**
     * Obter estatísticas dos raffles
     */
    public function getRaffleStatistics(): array
    {
        return [
            'total_raffles' => Raffle::count(),
            'active_raffles' => Raffle::where('status', 'active')->count(),
            'pending_raffles' => Raffle::where('status', 'pending')->count(),
            'inactive_raffles' => Raffle::where('status', 'inactive')->count(),
            'cancelled_raffles' => Raffle::where('status', 'cancelled')->count(),
            'total_prize_value' => Raffle::where('status', 'active')->sum('prize_value'),
            'avg_prize_value' => Raffle::where('status', 'active')->avg('prize_value'),
            'recent_raffles' => Raffle::with('creator')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}
