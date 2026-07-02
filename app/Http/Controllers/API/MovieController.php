<?php

namespace App\Http\Controllers\API;

use App\Services\MovieService;
use Exception;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class MovieController
{
    protected MovieService $movieService;
    
    public function __construct(MovieService $movieService)
    {
        $this->movieService = $movieService;
    }

    public function index()
    {
        $movie = $this->movieService->getAll();

        return response()->json([$movie, 200]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'duration'    => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        try {
            $movie = $this->movieService->store(
                $validated['title'],
                $validated['description'],
                $validated['duration']
            );

            return response()->json([
                'message' => 'Filme criado com sucesso!',
                'data'    => $movie
            ], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao criar o filme: ' . $e->getMessage()], 500);
        }

    }

    public function show(int $id)
    {
        try {
            $movie = $this->movieService->findById($id);

            return response()->json($movie, 200);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'duration'    => 'sometimes|required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $movie = $this->movieService->update($id, $request->only(['title', 'description', 'duration']));

            return response()->json([
                'message' => 'Filme atualizado com sucesso!',
                'data'    => $movie
            ], 200);

        } catch (Exception $e) {
            $statusCode = $e->getCode() == 404 ? 404 : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    /**
     * Remove um filme.
     */
    public function destroy(int $id)
    {
        try {
            $this->movieService->delete($id);

            return response()->json(['message' => 'Filme removido com sucesso!'], 200);

        } catch (Exception $e) {
            $statusCode = $e->getCode() == 404 ? 404 : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}