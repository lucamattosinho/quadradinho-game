<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\GameController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

#Route::post('/board', [BoardController::class, 'generateBoard']);

Route::post('/validate-word', [GameController::class, 'validateWord']);

Route::get('/board', [BoardController::class, 'getBoard']);
