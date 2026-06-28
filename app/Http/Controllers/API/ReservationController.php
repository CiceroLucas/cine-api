<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Screening;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    public function reserve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'screening_id' => 'required|exists:screenings,id',
            'seat_id'    => 'required|exists:seats,id',
            'user_id'    => 'required|exists:users,id', 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $screeningId = $request->screening_id;
        $seatId = $request->seat_id;
        $userId = $request->user_id;

        try {
            $reservation = DB::transaction(function () use ($screeningId, $seatId, $userId) {
                
                $screening = Screening::find($screeningId);
                $seat = Seat::find($seatId);

                if ($seat->room_id !== $screening->room_id) {
                    throw new \Exception("Este assento não pertence à sala desta sessão.", 422);
                }

                $alreadyReserved = Reservation::where('screening_id', $screeningId)
                    ->where('seat_id', $seatId)
                    ->active()
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyReserved) {
                    throw new \Exception("Desculpe, este assento já está reservado ou no carrinho de outro utilizador.", 422);
                }

                return Reservation::create([
                    'screening_id' => $screeningId,
                    'seat_id'    => $seatId,
                    'user_id'    => $userId,
                    'status'     => 'pending',
                    'expires_at' => now()->addMinutes(10),
                ]);
            });

            return response()->json([
                'message' => 'Assento bloqueado com sucesso! Você tem 10 minutos para pagar.',
                'data' => $reservation
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], $e->getCode() == 422 ? 422 : 500);
        }
    }

    public function confirm($id)
    {
        $reservation = Reservation::find($id);

        if(!$reservation)
        {
            return response()->json(['error' => 'Reserva não encontrada.', 404]);
        }

        if($reservation->status === 'confirmed')
        {
            return response()->json(['error' => 'Esta reserva já foi paga e confirmada.', 422]);
        }

        if($reservation->status === 'canceled')
        {
            return response()->json(['error' => 'Esta reserva foi cancelada e não pode ser paga.', 422]);
        }

        if($reservation->expire_at && $reservation->expires_at->isPast())
        {
            $reservation->update(['status' => 'cancelled']);

            return response()->json(['error' => 'O tempo limite de 10 minutos para o pagamento expirou', 422]);
        }

        $reservation->update([
            'status' => 'confirmed',
            'expires_at' => null,
        ]);

        return response()->json([
            'message' => 'Pagamento confirmado com sucesso! o Seu lugar esta garantido.',
            'data' => $reservation
        ], 200);

    }
}