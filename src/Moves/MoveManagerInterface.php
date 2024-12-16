<?php

namespace Battlesnake\Moves;

interface MoveManagerInterface {
    public function getMoves(string | NULL $snakeId, array | NULL $allMoves): array;
}