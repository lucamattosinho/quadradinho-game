<?php

namespace App\Services;


class BoardService{
    /**
     * Register services.
     */
    protected $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
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
        return $board;
    }

    public function getRandomLetter()
    {
        return $this->letters[rand(0, count($this->letters) - 1)];
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
