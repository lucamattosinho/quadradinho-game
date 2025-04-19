<?php

use Illuminate\Http\Request;
use App\Http\Controllers\DictionaryController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/validate-word', [DictionaryController::class, 'validate']);