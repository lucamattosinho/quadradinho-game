<?php

namespace App\Services;


class BoardService{
    /**
     * Register services.
     */
    protected $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    protected $board;

    public function __construct()
    {
        $this->generateBoard();
    }

    public function generateBoard($size=4)
    {
        $board = [];
        for ($i = 0; $i < $size; $i++) {
            $row = [];
            for ($j = 0; $j < $size; $j++) {
                $row[] = $this->getRandomLetter();
            }
            $board[] = $row;
        }
        $this->board = $board;
        return $board;
    }

    public function getRandomLetter()
    {
        return $this->letters[rand(0, count($this->letters) - 1)];
    }


    public function isPathValid(array $path, $size=4): bool
    {

        for ($i = 0; $i < count($path) - 1; $i++) {
            $current = $path[$i];
            $next = $path[$i + 1];

            // Verifica se as posições estão dentro do grid
            if (
                $current['x'] < 0 || $current['x'] >= $size ||
                $current['y'] < 0 || $current['y'] >= $size ||
                $next['x'] < 0 || $next['x'] >= $size ||
                $next['y'] < 0 || $next['y'] >= $size
            ) {
                return false;
            }

            // Verifica 4-conexidade (sem diagonais)
            $dx = abs($current['x'] - $next['x']);
            $dy = abs($current['y'] - $next['y']);

            if (!($dx + $dy === 1)) {
                return false;
            }
        }

        return true;
    }

    public function getWordFromPath(array $grid, array $path): string
    {
        $word = '';
        foreach ($path as $pos) {
            $x = $pos['x'];
            $y = $pos['y'];
            $word .= $grid[$y][$x]; // cuidado com a ordem [linha][coluna]
        }
        return $word;
    }

    public function isPathSequenceValid(array $path, $size=4): bool
    {
        for ($i = 0; $i < count($path) - 1; $i++) {
            if (!$this->isPathValid([$path[$i], $path[$i + 1]], $size)) {
                return false;
            }
        }
        return true;
    }

    public function getCurrentBoard(): array
    {
        return $this->board;
    }

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
