<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Screening;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Exception;

class ReservationService
{
    /**
     * Lida com a lógica de bloqueio e criação da reserva temporária.
     */
    public function reserveSeat(int $screeningId, int $seatId, int $userId): Reservation
    {
        return DB::transaction(function () use ($screeningId, $seatId, $userId) {
            
            $screening = Screening::find($screeningId);
            $seat = Seat::find($seatId);

            if ($seat->room_id !== $screening->room_id) {
                throw new Exception("Este assento não pertence à sala desta sessão.", 422);
            }

            $alreadyReserved = Reservation::where('screening_id', $screeningId)
                ->where('seat_id', $seatId)
                ->active()
                ->lockForUpdate()
                ->exists();

            if ($alreadyReserved) {
                throw new Exception("Desculpe, este assento já está reservado ou no carrinho de outro utilizador.", 422);
            }

            return Reservation::create([
                'screening_id' => $screeningId,
                'seat_id'      => $seatId,
                'user_id'      => $userId,
                'status'       => 'pending',
                'expires_at'   => now()->addMinutes(10),
            ]);
        });
    }

    /**
     * Lida com a lógica de confirmação do pagamento.
     */
    public function confirmReservation(int $id, int $userId): Reservation
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            throw new Exception("Reserva não encontrada.", 404);
        }

        if ($reservation->user_id !== $userId) {
            throw new Exception("Esta reserva pertence a outro usuário.", 403);
        }

        if ($reservation->status === 'confirmed') {
            throw new Exception("Esta reserva já foi paga e confirmada.", 422);
        }

        if ($reservation->status === 'cancelled') {
            throw new Exception("Esta reserva foi cancelada e não pode ser paga.", 422);
        }

        if ($reservation->expires_at && $reservation->expires_at->isPast()) {
            $reservation->update(['status' => 'cancelled']);
            throw new Exception("O tempo limite de 10 minutos para o pagamento expirou.", 422);
        }

        $reservation->update([
            'status'     => 'confirmed',
            'expires_at' => null,
        ]);

        return $reservation;
    }

    /**
     * Lida com a lógica de mapeamento dos assentos e seus status.
     */
    public function getScreeningSeats(int $screeningId): array
    {
        $screening = Screening::with(['movie', 'room.seats'])->find($screeningId);

        if (!$screening) {
            throw new Exception("Sessão não encontrada.", 404);
        }

        $activeReservations = Reservation::where('screening_id', $screeningId)
            ->active()
            ->get()
            ->keyBy('seat_id');

        $seatsWithStatus = $screening->room->seats->map(function ($seat) use ($activeReservations) {
            $status = 'available';
            
            if ($activeReservations->has($seat->id)) {
                $status = $activeReservations->get($seat->id)->status;
            }

            return [
                'id'     => $seat->id,
                'row'    => $seat->row,
                'number' => $seat->number,
                'status' => $status,
            ];
        });

        return [
            'session_id' => $screening->id,
            'movie'      => $screening->movie->title,
            'room'       => $screening->room->name,
            'start_time' => $screening->start_time->toIso8601String(),
            'price'      => $screening->price,
            'seats'      => $seatsWithStatus
        ];
    }
}
