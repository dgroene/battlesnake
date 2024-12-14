<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;
use Battlesnake\Moves\MoveManagerInterface;

class BaseMoveManager implements MoveManagerInterface
{
    public function __construct(protected GameData $gameData) {
    }

    #[\Override] public function getMoves(?string $snakeId = ''): array
    {
        return [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
    }
    public function getNewHead($current_head, $move) {
        if ($move == MoveDirections::UP) {
            return ['x' => $current_head['x'], 'y' => $current_head['y'] + 1];
        } elseif ($move == MoveDirections::DOWN) {
            return ['x' => $current_head['x'], 'y' => $current_head['y'] - 1];
        } elseif ($move == MoveDirections::LEFT) {
            return ['x' => $current_head['x'] - 1, 'y' => $current_head['y']];
        } elseif ($move == MoveDirections::RIGHT) {
            return ['x' => $current_head['x'] + 1, 'y' => $current_head['y']];
        }
    }

    public function getManhattanDistance($point1, $point2) {
        return abs($point1['x'] - $point2['x']) + abs($point1['y'] - $point2['y']);
    }
}