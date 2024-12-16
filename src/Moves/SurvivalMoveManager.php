<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;

class SurvivalMoveManager extends BaseMoveManager {

    const int MAX_DEPTH = 8;

    public function getMoves(string|null $snakeId = '', $allMoves = self::ALLMOVES): array {
        $possibleMoves = $allMoves;
        $moveSurvivalScores = [];
        foreach ($possibleMoves as $move) {
            $newGameData = $this->gameData->getNextMoveGameData($move);
            if ($newGameData === NULL) {
                $moveSurvivalScores[$move] = 0;
                continue;
            }
            $depthSurvived = $this->getMaxSurvivalDepth($newGameData, self::MAX_DEPTH);
            $moveSurvivalScores[$move] = $depthSurvived;
        }
        $maxScore = max($moveSurvivalScores);
        $bestMoves = [];
        foreach ($moveSurvivalScores as $move => $score) {
            if ($score === $maxScore) {
                $bestMoves[] = $move;
            }
        }
        return $bestMoves;
    }

    public function getMaxSurvivalDepth(GameData $gameData, int $stepsRemaining): int {
        // Base case: If we have no more steps to survive, we've succeeded.
        if ($stepsRemaining <= 0) {
            return self::MAX_DEPTH;
        }

        // Instantiate a temporary manager to explore this state
        $impossibleMoveManager = new ImpossibleMoveManager($gameData);
        $nextMoves = $impossibleMoveManager->getMoves();

        // If no moves are possible, we can't survive
        if (empty($nextMoves)) {
            return self::MAX_DEPTH - $stepsRemaining;
        }

        // Try each possible move
        $bestDepth = self::MAX_DEPTH - $stepsRemaining;
        foreach($nextMoves as $nextMove) {
            $nextState = $gameData->getNextMoveGameData($nextMove);

            if ($nextState === NULL) {
                $depth = self::MAX_DEPTH - $stepsRemaining;
            }
            else $depth = $this->getMaxSurvivalDepth($nextState, $stepsRemaining - 1);
            if ($depth > $bestDepth) {
                $bestDepth = $depth;
            }
        }
        return $bestDepth;

    }

}