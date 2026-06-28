<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Screening;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CinemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        $user = User::create([
            'name' => 'user',
            'email' => 'user@teste.com',
            'password' => Hash::make('12345678'),
        ]);

        $movie = Movie::create([
            'title' => 'Interestelar',
            'description' => 'Uma equipe de exploradores viaja através de um buraco de minhoca no espaço para garantir a sobrevivência da humanidade.',
            'duration_minutes' => 169,
        ]);

        $room = Room::create([
            'name' => 'Sala 01 - IMAX',
        ]);

        $rows = ['A', 'B', 'C', 'D', 'E', 'F'];
        $seatsData = [];

        foreach ($rows as $row) {
            for ($number = 1; $number <= 10; $number++) {
                $seatsData[] = [
                    'room_id'    => $room->id,
                    'row'        => $row,
                    'number'     => $number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Seat::insert($seatsData);

        Screening::create([
            'movie_id'   => $movie->id,
            'room_id'    => $room->id,
            'start_time' => now()->addHours(3), 
            'price'      => 29.90,
        ]);
    }
}