<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;
use Battlesnake\Game\GameManager;

class DeadSnakesMoveManager extends BaseMoveManager
{
    const MAX_DEPTH = 7;

    public function getMoves(string|null $snakeId = '', $allMoves = self::ALLMOVES): array
    {
        $possibleMoves = $allMoves;
        $maxDeadSnakes = 0;
        $maxDeadSnakesMove = [];
        foreach ($possibleMoves as $move) {
            $newGameData = $this->gameData->getNextMoveGameData($move);
            if ($newGameData === NULL) {
                continue;
            }
            $deadSnakes = $this->redrum($newGameData, self::MAX_DEPTH, 0);
            if ($deadSnakes > $maxDeadSnakes) {
                $maxDeadSnakes = $deadSnakes;
                $maxDeadSnakesMove = [$move];
            } elseif ($deadSnakes == $maxDeadSnakes) {
                $maxDeadSnakesMove[] = $move;
            }
        }
        return $maxDeadSnakesMove;
    }
    public function redrum(GameData $gameData, int $stepsRemaining, int $deadSnakes): int {
        if ($stepsRemaining <= 0) {
            return $deadSnakes;
        }
        $startingSnakeCount = count($gameData->getSnakes());
        $tempGameManager = new GameManager($gameData->getData());
        $possibleMoves = $tempGameManager->getPossibilities();
        $preferredMoves = $tempGameManager->getPreferred($possibleMoves);
        if (empty($preferredMoves)) {
            return $deadSnakes;
        }
        if (count($preferredMoves) > 1) {
            $move = $preferredMoves[array_rand($preferredMoves)];
        } else {
            $move = array_values($preferredMoves)[0];
        }
        $newGameData = $gameData->getNextMoveGameData($move);
        if ($newGameData === NULL) {
            return $deadSnakes;
        }
        $newSnakeCount = count($newGameData->getSnakes());
        if ($newSnakeCount < $startingSnakeCount) {
            $deadSnakes = $deadSnakes + ($startingSnakeCount - $newSnakeCount);
        }
        return $this->redrum($newGameData, $stepsRemaining - 1, $deadSnakes);
    }
}