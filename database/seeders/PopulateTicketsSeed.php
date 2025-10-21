<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PopulateTicketsSeed extends Seeder
{
    /**
     * Insere 10.000.000 de tickets em lotes otimizados.
     * Usa insertOrIgnore para ser idempotente e rápido, com transações por lote.
     */
    public function run(): void
    {
        // Otimizações de memória e performance
        DB::disableQueryLog();

        $total = 10_000; // total de tickets
        $batchSize = 2_000; // tamanho do lote (reduzido para menor uso de memória)
        $padding = 7; // zeros à esquerda para manter largura suficiente (até 9.999.999)

        $insertedBatches = 0;
        $now = now();

        for ($start = 1; $start <= $total; $start += $batchSize) {
            $end = min($start + $batchSize - 1, $total);
            $rows = [];

            for ($i = $start; $i <= $end; $i++) {
                $rows[] = [
                    'number' => str_pad($i, $padding, '0', STR_PAD_LEFT),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::transaction(function () use (&$rows) {
                // insertOrIgnore ignora duplicados (chave única em tickets.number)
                DB::table('tickets')->insertOrIgnore($rows);
            });

            // libera memória do array e força GC
            unset($rows);
            gc_collect_cycles();

            $insertedBatches++;
        }

        $this->command?->info("PopulateTicketsSeed concluído. Lotes processados: {$insertedBatches}.");
    }
}
