<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;

class SurvivalMoveManager extends BaseMoveManager {

    const int MAX_DEPTH = 7;

    public function getMoves(string|null $snakeId = ''): array {
        $possibleMoves = [MoveDirections::DOWN, MoveDirections::UP, MoveDirections::LEFT, MoveDirections::RIGHT];
        $survivable_moves = [];
        foreach ($possibleMoves as $move) {
            $newGameData = $this->gameData->getNextMoveGameData($move);
            if ($newGameData === NULL) {
                continue;
            }
            if ($this->canSurvive($newGameData, self::MAX_DEPTH)) {
                $survivable_moves[] = $move;
            }
        }
        return $survivable_moves;

    }

    public function canSurvive(GameData $gameData, int $stepsRemaining): bool {
        // Base case: If we have no more steps to survive, we've succeeded.
        if ($stepsRemaining <= 0) {
            return true;
        }

        // Instantiate a temporary manager to explore this state
        $impossibleMoveManager = new ImpossibleMoveManager($gameData);
        $nextMoves = $impossibleMoveManager->getMoves();

        // If no moves are possible, we can't survive
        if (empty($nextMoves)) {
            return false;
        }

        // Try each possible move
        $happy_path = [];
        foreach($nextMoves as $nextMove) {
            $nextState = $gameData->getNextMoveGameData($nextMove);

            if ($nextState === NULL) {
                continue;
            }
            if ($this->canSurvive($nextState, $stepsRemaining - 1)) {
                $happy_path[] = $nextMove;
            }
        }
        return !empty($happy_path);

    }

}