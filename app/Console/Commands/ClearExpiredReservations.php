<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class ClearExpiredReservations extends Command
{
    protected $signature = 'reservations:clear-expired';

    protected $description = 'Liberta os assentos de reservas pendentes que ultrapassaram o limite de 10 minutos';

    public function handle()
    {
        $this->info('A iniciar a limpeza de reservas expiradas...');

        $expiredCount = Reservation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'cancelled',
            ]);

        $this->info("Sucesso! {$expiredCount} reserva(s) expirada(s) foram cancelada(s) e os assentos estão livres.");
    }
}