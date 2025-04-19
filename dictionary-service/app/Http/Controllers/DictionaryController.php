<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DictionaryController extends Controller
{
    public function validate(Request $request)
    {
        $word = strtolower($request->input('word'));

        if (strlen($word) < 4) {
            return response()->json(['valid' => false, 'reason' => 'Palavra muito curta'], 400);
        }

        $response = Http::get("https://api.dicionario-aberto.net/word/{$word}");

        if ($response->successful() && !empty($response->json())) {
            return response()->json(['valid' => true]);
        }

        return response()->json(['valid' => false, 'reason' => 'Palavra não encontrada']);
    }
}
