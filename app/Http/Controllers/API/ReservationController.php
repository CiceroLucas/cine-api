<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ReservationController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function reserve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'screening_id' => 'required|exists:screenings,id',
            'seat_id'      => 'required|exists:seats,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $reservation = $this->reservationService->reserveSeat(
                $request->screening_id,
                $request->seat_id,
                $request->user()->id
            );

            return response()->json([
                'message' => 'Assento bloqueado com sucesso! Você tem 10 minutos para pagar.',
                'data' => $reservation
            ], 201);

        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function confirm(Request $request, $id)
    {
        try {
            $reservation = $this->reservationService->confirmReservation($id, $request->user()->id);

            return response()->json([
                'message' => 'Pagamento confirmado com sucesso! O seu lugar está garantido.',
                'data' => $reservation
            ], 200);

        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function getSeats($screeningId)
    {
        try {
            $data = $this->reservationService->getScreeningSeats($screeningId);

            return response()->json($data, 200);

        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}
