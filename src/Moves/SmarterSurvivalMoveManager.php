<?php

namespace Battlesnake\Moves;

use Battlesnake\Game\GameData;

class SmarterSurvivalMoveManager extends BaseMoveManager {

    const int MAX_DEPTH = 8;

    public function getMoves(string|null $snakeId = '', $allMoves = self::ALLMOVES): array {
        $possibleMoves = $allMoves;
        $moveLookAheads = [];
        foreach ($possibleMoves as $move) {
            $newGameData = $this->gameData->getNextMoveGameData($move);
            if ($newGameData === NULL) {
                $moveLookAheads[$move] = new LookAhead(NULL, 0);
                continue;
            }
            $moveLookAheads[$move] = $this->getDeepestLookAhead($newGameData, self::MAX_DEPTH);
        }
        return [$this->getBestMove($moveLookAheads)];
    }

    public function getDeepestLookAhead(GameData $gameData, int $stepsRemaining): LookAhead {
        // Base case: If we have no more steps to survive, we've succeeded.
        if ($stepsRemaining <= 0) {
            return new LookAhead($gameData, self::MAX_DEPTH);
        }

        // Instantiate a temporary manager to explore this state
        $impossibleMoveManager = new ImpossibleMoveManager($gameData);
        $nextMoves = $impossibleMoveManager->getMoves();

        // If no moves are possible, we can't survive
        if (empty($nextMoves)) {
            return new LookAhead($gameData, self::MAX_DEPTH - $stepsRemaining);
        }

        // Try each possible move
        $bestMove = new LookAhead($gameData, self::MAX_DEPTH - $stepsRemaining);
        foreach($nextMoves as $nextMove) {
            $nextState = $gameData->getNextMoveGameData($nextMove);

            if ($nextState === NULL) {
                $thisPath = new LookAhead(NULL, self::MAX_DEPTH - $stepsRemaining);
            }
            else $thisPath = $this->getDeepestLookAhead($nextState, $stepsRemaining - 1);
            if (count($nextMoves) == 1 && $thisPath->depth < self::MAX_DEPTH) {
                $thisPath->depth = self::MAX_DEPTH - $stepsRemaining;
            }
            if ($thisPath->depth > $bestMove->depth) {
                $bestMove = $thisPath;
            }
        }
        return $bestMove;
    }

    public function getBestMove(array $lookAheads): string {
        $lookAheads = array_filter($lookAheads, function($lookAhead) {
            return $lookAhead->gameData !== NULL;
        });
        $points = [];
        foreach ($lookAheads as $move => $lookAhead) {
            $points[$move] = 0;
        }

        // Award 10 points for the deepest look ahead
        $maxDepth = max(array_map(function($lookAhead) {
            return $lookAhead->depth;
        }, $lookAheads));
        foreach ($lookAheads as $move => $lookAhead) {
            if ($lookAhead->depth == $maxDepth) {
                $points[$move] += 10;
            }
        }
        foreach ($lookAheads as $move => $lookAhead) {
            if ($lookAhead->gameData->getYou()['health'] < 20) {
                $points[$move] -= 9;
            }
        }
        // Award 8 points for me being the longest (aka most food eaten)
        $maxSnakeLength = max(array_map(function($lookAhead) {
            return $lookAhead->gameData->getYouLength();
        }, $lookAheads));
        foreach ($lookAheads as $move => $lookAhead) {
            if ($lookAhead->gameData->getYouLength() == $maxSnakeLength) {
                $points[$move] += 8;
            }
        }
        $maxDeadSnakes = max(array_map(function($lookAhead) {
            return $this->gameData->getSnakeCount() - $lookAhead->gameData->getSnakeCount();
        }, $lookAheads));
        foreach ($lookAheads as $move => $lookAhead) {
            if ($this->gameData->getSnakeCount() - $lookAhead->gameData->getSnakeCount() == $maxDeadSnakes) {
                $points[$move] += 7;
            }
        }
        $maxAcessibleSquares = 0;
        $maxAccessibleSquaresMove = [];
        foreach ($lookAheads as $move => $lookAhead) {
            $accessibleSquares = $this->gameData->calculateAccessibleSquares($lookAhead->gameData->getYouHead(), $lookAhead->gameData->getYou()['id']);
            if ($accessibleSquares > $maxAcessibleSquares) {
                $maxAcessibleSquares = $accessibleSquares;
                $maxAccessibleSquaresMove = [$move];
            } elseif ($accessibleSquares == $maxAcessibleSquares) {
                $maxAccessibleSquaresMove[] = $move;
            }
        }
        foreach ($maxAccessibleSquaresMove as $move) {
            $points[$move] += 6;
        }
        // order the final moves by points
        arsort($points);
        return array_keys($points)[0];
    }

}