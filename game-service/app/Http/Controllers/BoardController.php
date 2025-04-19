<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BoardService;

class BoardController extends Controller
{
    protected $boardService;
    public function __construct(BoardService $boardService)
    {
        $this->boardService = $boardService;
    }

    public function generateBoard(Request $request)
    {
        $size = $request->input('size', 4);
        $board = $this->boardService->generateBoard($size);
        return response()->json($board);
    }

    public function getBoard()
    {
        $board = $this->boardService->getCurrentBoard();
        return response()->json(['board' => $board]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
