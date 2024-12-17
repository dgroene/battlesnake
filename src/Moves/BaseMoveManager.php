<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;
use Battlesnake\Moves\MoveManagerInterface;

class BaseMoveManager implements MoveManagerInterface
{
    const ALLMOVES = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];

    public function __construct(protected GameData $gameData) {
    }

    #[\Override] public function getMoves(?string $snakeId = '', ?array $allMoves = self::ALLMOVES): array
    {
        return $allMoves;
    }
    public function getNewHead(array $current_head, string $move): array {
        if ($move == MoveDirections::UP) {
            return ['x' => $current_head['x'], 'y' => $current_head['y'] + 1];
        } elseif ($move == MoveDirections::DOWN) {
            return ['x' => $current_head['x'], 'y' => $current_head['y'] - 1];
        } elseif ($move == MoveDirections::LEFT) {
            return ['x' => $current_head['x'] - 1, 'y' => $current_head['y']];
        } elseif ($move == MoveDirections::RIGHT) {
            return ['x' => $current_head['x'] + 1, 'y' => $current_head['y']];
        }
        return $current_head;
    }

    public function getManhattanDistance(array $point1, array $point2): int {
        return abs($point1['x'] - $point2['x']) + abs($point1['y'] - $point2['y']);
    }
}