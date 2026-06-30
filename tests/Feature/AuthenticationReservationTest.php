<?php

use App\Models\Movie;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Screening;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createScreeningWithSeat(): array
{
    $movie = Movie::create([
        'title' => 'Interestelar',
        'description' => 'Filme de ficcao cientifica.',
        'duration_minutes' => 169,
    ]);

    $room = Room::create(['name' => 'Sala 01']);
    $seat = Seat::create([
        'room_id' => $room->id,
        'row' => 'A',
        'number' => 1,
    ]);

    $screening = Screening::create([
        'movie_id' => $movie->id,
        'room_id' => $room->id,
        'start_time' => now()->addHour(),
        'price' => 29.90,
    ]);

    return [$screening, $seat];
}

test('login returns a sanctum bearer token', function () {
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => '12345678',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => '12345678',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']])
        ->assertJsonPath('token_type', 'Bearer');
});

test('reservation endpoints require authentication', function () {
    [$screening, $seat] = createScreeningWithSeat();

    $this->postJson('/api/reservar', [
        'screening_id' => $screening->id,
        'seat_id' => $seat->id,
    ])->assertUnauthorized();
});

test('reservation uses the authenticated user id', function () {
    [$screening, $seat] = createScreeningWithSeat();
    $loggedUser = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($loggedUser);

    $response = $this->postJson('/api/reservar', [
        'screening_id' => $screening->id,
        'seat_id' => $seat->id,
        'user_id' => $otherUser->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user_id', $loggedUser->id);

    $this->assertDatabaseHas('reservations', [
        'screening_id' => $screening->id,
        'seat_id' => $seat->id,
        'user_id' => $loggedUser->id,
    ]);
});

test('only the reservation owner can confirm it', function () {
    [$screening, $seat] = createScreeningWithSeat();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $reservation = Reservation::create([
        'screening_id' => $screening->id,
        'seat_id' => $seat->id,
        'user_id' => $owner->id,
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    Sanctum::actingAs($otherUser);

    $this->postJson("/api/reservas/{$reservation->id}/confirmar")
        ->assertForbidden();

    Sanctum::actingAs($owner);

    $this->postJson("/api/reservas/{$reservation->id}/confirmar")
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.user_id', $owner->id);
});
