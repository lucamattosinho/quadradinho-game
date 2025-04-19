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
        $path = $request->input('path'); // caminho [{x:1, y:2}, ...]

        if (!is_array($grid) || !is_array($path)) {
            return response()->json(['valid' => false, 'reason' => 'Grid ou path ausentes ou malformados.'], 200);
        }

        $size = count($grid);

        if (!$board->isPathSequenceValid($path, $size)) {
            return response()->json(['valid' => false, 'reason' => 'path_invalid'], 200);
        }

        $word = $board->getWordFromPath($grid, $path);

        if (strlen($word) < 4) {
            return response()->json(['valid' => false, 'reason' => 'too_short'], 200);
        }

        // Cache local antes de chamar o dicionário
        $cacheKey = 'word_' . strtolower($word);
        if (Cache::has($cacheKey)) {
            return response()->json(['valid' => true, 'word' => $word, 'cached' => true], 200);
        }

        // Chamar o dictionary-service
        $response = Http::get(env('DICTIONARY_SERVICE_URL') . '/validate', [
            'word' => $word,
        ]);

        if ($response->successful() && $response->json('valid')) {
            Cache::put($cacheKey, true, 3600); // 1 hora
            return response()->json(['valid' => true, 'word' => $word], 200);
        }

        return response()->json(['valid' => false, 'word' => $word, 'reason' => 'not_found'], 200);
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
