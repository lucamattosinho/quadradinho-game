<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DictionaryService;
use App\Services\BoardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GameController extends Controller
{
    public function validateWord(Request $request, BoardService $board)
    {
        $grid = $board->getCurrentBoard();
        $word = strtolower($request->input('word'));
        $path = $request->input('path');

        if (!is_array($grid) || empty($word)) {
            return response()->json(['valid' => false, 'reason' => 'Grid ou palavra ausentes ou malformados.'], 200);
        }

        if (strlen($word) < 4) {
            return response()->json(['valid' => false, 'reason' => 'too_short'], 200);
        }
        

        // Cache local antes de chamar o dicionário
        $cacheKey = 'word_' . strtolower($word);
        if (Cache::has($cacheKey)) {
            return response()->json(['valid' => true, 'word' => $word, 'path' => $path, 'cached' => true], 200);
        }

        // Chamar o dictionary-service
        $response = Http::post(env('DICTIONARY_SERVICE_URL'), [
            'word' => $word,
        ]);

        if ($response->successful() && $response->json('valid')) {
            Cache::put($cacheKey, true, 3600); // 1 hora
            return response()->json(['valid' => true, 'word' => $word, 'path' => $path], 200);
        }

        return response()->json(['valid' => false, 'word' => $word, 'reason' => 'not_found'], 200);
    }

    private function findPathForWord(array $grid, string $word): ?array
    {
        $rows = count($grid);
        $cols = count($grid[0]);

        for ($y = 0; $y < $rows; $y++) {
            for ($x = 0; $x < $cols; $x++) {
                if ($grid[$y][$x] === $word[0]) {
                    $visited = array_fill(0, $rows, array_fill(0, $cols, false));
                    $path = $this->dfs($grid, $x, $y, $word, 0, $visited, []);
                    if ($path !== null) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }

    private function dfs(array &$grid, int $x, int $y, string $word, int $index, array $visited, array $path): ?array
    {
        $rows = count($grid);
        $cols = count($grid[0]);

        if ($x < 0 || $y < 0 || $x >= $cols || $y >= $rows) return null;
        if ($visited[$y][$x]) return null;
        if ($grid[$y][$x] !== $word[$index]) return null;

        $visited[$y][$x] = true;
        $path[] = ['x' => $x, 'y' => $y];

        if ($index === strlen($word) - 1) {
            return $path;
        }

        $dirs = [[0, -1], [0, 1], [-1, 0], [1, 0]];
        foreach ($dirs as [$dx, $dy]) {
            // Criar nova cópia do mapa de visitados para cada tentativa
            $newVisited = $visited;
            $newPath = $path;
            $result = $this->dfs($grid, $x + $dx, $y + $dy, $word, $index + 1, $newVisited, $newPath);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }



    public function play(Request $request, BoardService $board, DictionaryService $dictionary)
    {
        $request->validate([
            'word' => 'required|string|min:4',
            'path' => 'required|array', // Ex: [['x' => 0, 'y' => 1], ['x' => 0, 'y' => 2], ...]
        ]);

        $word = mb_strtolower($request->input('word'));
        $path = $request->input('path');
        $size = count($board->getCurrentBoard());
        // 1. Validação do path 4-conexo
        if (!$board->isPathValid($path, $size)) {
            return response()->json([
                'valid' => false,
                'reason' => 'path_invalid',
            ], 400);
        }

        // 2. Geração da palavra a partir do path
        $matrix = $board->getCurrentBoard(); // deve retornar a matriz do tabuleiro atual
        $wordFromBoard = mb_strtolower($board->getWordFromPath($path, $matrix));

        if ($word !== $wordFromBoard) {
            return response()->json([
                'valid' => false,
                'reason' => 'mismatch_path_word',
            ], 400);
        }

        // 3. Cache de validação
        $cached = Cache::remember("word_valid:{$word}", now()->addHours(12), function () use ($dictionary, $word) {
            return $dictionary->isValid($word);
        });

        if (!$cached) {
            return response()->json([
                'valid' => false,
                'reason' => 'not_in_dictionary',
            ], 400);
        }

        // 4. Aqui pode salvar a palavra, se quiser
        return response()->json([
            'valid' => true,
            'word' => $word,
        ]);
    }
}
