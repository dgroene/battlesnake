<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;

class BaseMoveManager implements MoveManagerInterface
{
    const ALLMOVES = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];

    public function __construct(protected GameData $gameData) {
    }

    #[\Override] public function getMoves(?string $snakeId = '', ?array $allMoves = self::ALLMOVES): array
    {
        return $allMoves;
    }

    public function getManhattanDistance(array $point1, array $point2): int {
        return abs($point1['x'] - $point2['x']) + abs($point1['y'] - $point2['y']);
    }

    public function setGameData(GameData $gameData): void {
        $this->gameData = $gameData;
    }

}