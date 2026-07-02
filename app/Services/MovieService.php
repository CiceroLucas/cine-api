<?php

namespace App\Services;

use App\Models\Movie;
use Exception;

class MovieService
{
    public function store (string $title, string $description, int $duration): Movie
    {
        return Movie::create([
            'title'       => $title,
            'description' => $description,
            'duration'    => $duration,
        ]);
    }

    public function getAll()
    {
        return Movie::orderBy('title')->get();
    }

    public function findById(int $id): Movie
    {
        $movie = Movie::find($id);

        if (!$movie) {
            throw new Exception("Filme não encontrado.", 404);
        }

        return $movie;
    }

    public function update(int $id, array $data): Movie
    {
        $movie = $this->findById($id);
        $movie->update($data);
        return $movie;
    }

    public function delete(int $id): void
    {
        $movie = $this->findById($id);
        $movie->delete();
    }
}