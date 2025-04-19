<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DictionaryService
{
    protected string $url;

    public function __construct()
    {
        $this->url = env('DICTIONARY_SERVICE_URL', 'http://dictionary-service:8002/api/validate-word');
    }

    public function isValid(string $word): bool
    {
        $cacheKey = "valid_word_{$word}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($word) {
            $response = Http::post($this->url, ['word' => $word]);

            if ($response->successful()) {
                return $response->json('valid');
            }

            return false;
        });
    }
}
