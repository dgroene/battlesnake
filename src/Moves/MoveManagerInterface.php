<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;

interface MoveManagerInterface {
    public function getMoves(string | NULL $snakeId=''): array;
}