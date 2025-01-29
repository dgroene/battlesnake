<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;

class SmarterSurvivalMoveManager extends BaseMoveManager {

    const int MAX_DEPTH = 8;

    public function getMoves(string|null $snakeId = '', $allMoves = self::ALLMOVES): array {
        $impossibleMoveManager = new ImpossibleMoveManager($this->gameData);
        $possibleMoves = $impossibleMoveManager->getMoves();
        if (count($possibleMoves) == 0 || count($possibleMoves) == 1) {
            return $possibleMoves;
        }
        return [$this->compareMoves($possibleMoves)];
    }

    function compareMoves($moves): string {
       $bestMove = [];
       $bestScore = 0;
       foreach ($moves as $move) {
           $nextState = $this->gameData->getNextMoveGameData($move);
           if ($nextState === NULL) {
             continue;
           }
           foreach($this->getDeepestLookAhead($nextState, self::MAX_DEPTH) as $lookAhead) {
               $score = $this->scoreLookAhead($lookAhead, TRUE);
               if ($score > $bestScore) {
                   $bestMove = [$move];
                   $bestScore = $score;
               }
               else if ($score == $bestScore) {
                   $bestMove[] = $move;
               }
           }
       }
       return $bestMove[array_rand($bestMove)];
    }

    public function getDeepestLookAhead(GameData $gameData, int $stepsRemaining) {
        // Base case: If we have no more steps to survive, we've succeeded.
        if ($stepsRemaining <= 0) {
            yield new LookAhead($gameData, self::MAX_DEPTH);
            return;
        }

        // Instantiate a temporary manager to explore this state
        $impossibleMoveManager = new ImpossibleMoveManager($gameData);
        $nextMoves = $impossibleMoveManager->getMoves();

        // If no moves are possible, we can't survive
        if (empty($nextMoves)) {
            yield new LookAhead($gameData, self::MAX_DEPTH - $stepsRemaining);
            return;
        }

        // Try each possible move
        $bestMove = new LookAhead($gameData, self::MAX_DEPTH - $stepsRemaining);
        foreach($nextMoves as $nextMove) {
            $nextState = $gameData->getNextMoveGameData($nextMove);

            if ($nextState === NULL) {
                continue;
            }
            else {
                foreach ($this->getDeepestLookAhead($nextState, $stepsRemaining - 1) as $lookAhead) {
                    if ($lookAhead->depth > $bestMove->depth) {
                        $bestMove = $lookAhead;
                    }
                    else if ($lookAhead->depth == $bestMove->depth) {
                        $bestMoveScore = $this->scoreLookAhead($bestMove);
                        $thisPathScore = $this->scoreLookAhead($lookAhead);
                        if ($thisPathScore > $bestMoveScore) {
                            $bestMove = $lookAhead;
                        }
                    }
                }
            }
        }
        yield $bestMove;
    }

    public function scoreLookAhead(LookAhead $lookAhead, bool $useAccessibleSquares = FALSE, $snakeId = ''): int {
        if ($snakeId == NULL) {
            $snakeId = $lookAhead->gameData->getYou()['id'];
        }
        $score = 0;
        $depth = $lookAhead->depth;
        $new_length = $lookAhead->gameData->getSnakeLength($snakeId);
        $new_health = $lookAhead->gameData->getSnakeHealth($snakeId);
        $new_snakeCount = $lookAhead->gameData->getSnakeCount();

        $current_length = $this->gameData->getSnakeLength($snakeId);
        $current_health = $this->gameData->getSnakeHealth($snakeId);
        $current_snakeCount = $this->gameData->getSnakeCount();
        if ($this->checkPrimedForKilling($lookAhead, TRUE, $snakeId)) {
            $score += 12;
        }
        if (!$this->checkPrimedForKilling($lookAhead, FALSE, $snakeId)) {
            $score += 12;
        }
        $score += $depth + 2;
        if ($new_length > $current_length) {
            $score += 10;
        }
        if ($new_health > $current_health) {
            $score += 8;
        }
        if ($new_snakeCount < $current_snakeCount) {
            $score += 7;
        }
        if ($useAccessibleSquares) {
            $new_accessibleSquares = $lookAhead->gameData->calculateAccessibleSquares($lookAhead->gameData->getSnakeHead($snakeId), $snakeId);
            $current_accessibleSquares = $this->gameData->calculateAccessibleSquares($this->gameData->getSnakeHead($snakeId), $snakeId);
            if ($new_accessibleSquares > $current_accessibleSquares) {
                $score += 7;
            }
            $closest_snake = [];
            $closest_distance = 1000;
            foreach ($this->gameData->getSnakes() as $snake) {
                if ($snake['id'] == $snakeId) {
                    continue;
                }
                $distance = $this->getManhattanDistance($this->gameData->getSnakeHead($snakeId), $snake['head']);
                if ($distance < $closest_distance) {
                    $closest_distance = $distance;
                    $closest_snake = $snake;
                }
            }
            if (!empty($closest_snake) && !empty($lookAhead->gameData->getSnakeById($closest_snake['id']))) {
                $currentEnemyAs = $this->gameData->calculateAccessibleSquares($this->gameData->getSnakeHead($closest_snake['id']), $closest_snake['id']);
                $newEnemyAs = $lookAhead->gameData->calculateAccessibleSquares($lookAhead->gameData->getSnakeHead($closest_snake['id']), $closest_snake['id']);
                if ($newEnemyAs < $currentEnemyAs) {
                    $score += 7;
                }
            }
        }
        return $score;
    }

    private function checkPrimedForKilling(LookAhead $lookAhead, bool $offense = TRUE, $snakeId = ''): bool
    {
        if ($snakeId == NULL) {
            $snakeId = $lookAhead->gameData->getYou()['id'];
        }
        $snake = $lookAhead->gameData->getSnakeById($snakeId);
        $snakeHead = $lookAhead->gameData->getSnakeHead($snakeId);

        foreach ($lookAhead->gameData->getSnakes() as $enemySnake) {
            if ($enemySnake['id'] == $snakeId) {
                continue;
            }
            if ($enemySnake['length'] >= $snake['length'] && $offense) {
                continue;
            }
            if ($enemySnake['length'] <= $snake['length'] && !$offense) {
                continue;
            }
            $enemySnakeHead = $lookAhead->gameData->getSnakeHead($enemySnake['id']);
            if ($this->getManhattanDistance($snakeHead, $enemySnakeHead) == 1) {
                return TRUE;
            }
        }
        return FALSE;
    }
}