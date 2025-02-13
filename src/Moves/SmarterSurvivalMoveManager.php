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

    public function scoreLookAhead(LookAhead $lookAhead, bool $useAccessibleSquares = FALSE): int {
        $score = 0;
        $depth = $lookAhead->depth;
        $new_length = $lookAhead->gameData->getYouLength();
        $new_health = $lookAhead->gameData->getYou()['health'];
        $new_snakeCount = $lookAhead->gameData->getSnakeCount();

        $current_length = $this->gameData->getYouLength();
        $current_health = $this->gameData->getYou()['health'];
        $current_snakeCount = $this->gameData->getSnakeCount();
        $iAmSmallest = TRUE;
        foreach ($lookAhead->gameData->getSnakes() as $snake) {
            if ($snake['id'] == $lookAhead->gameData->getYou()['id']) {
                continue;
            }
            if ($snake['length'] <= $new_length) {
                $iAmSmallest = FALSE;
            }
        }
        if ($this->checkPrimedForKilling($lookAhead)) {
            $score += 11;
        }
        if (!$this->checkPrimedForKilling($lookAhead, TRUE)) {
            $score += 15;
        }
        $score += $depth;
        if ($depth == 8) {
            $score += 10;
        }
        if ($new_length > $current_length) {
            $score += 10;
            if ($iAmSmallest) {
                $score += 5;
            }
        }
        if ($new_health > $current_health) {
            $score += 8;
            if ($iAmSmallest) {
                $score += 5;
            }
        }
        if ($new_snakeCount < $current_snakeCount) {
            $score += 12;
        }
        if ($useAccessibleSquares) {
            $new_accessibleSquares = $lookAhead->gameData->calculateAccessibleSquares($lookAhead->gameData->getYouHead(), $lookAhead->gameData->getYou()['id']);
            $current_accessibleSquares = $this->gameData->calculateAccessibleSquares($this->gameData->getYouHead(), $this->gameData->getYou()['id']);
            if ($new_accessibleSquares > $current_accessibleSquares) {
                $score += 15;
            }
            $closest_snake = [];
            $closest_distance = 1000;
            foreach ($this->gameData->getSnakes() as $snake) {
                if ($snake['id'] == $this->gameData->getYou()['id']) {
                    continue;
                }
                $distance = $this->getManhattanDistance($this->gameData->getYouHead(), $snake['head']);
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

    private function checkPrimedForKilling(LookAhead $lookAhead, bool $defensive = FALSE): bool
    {
        $primed_distance = $defensive ? 2 : 1;
        $you = $lookAhead->gameData->getYou();
        $youHead = $lookAhead->gameData->getYouHead();
        foreach ($lookAhead->gameData->getSnakes() as $snake) {
            if ($snake['id'] == $you['id']) {
                continue;
            }
            if (!$defensive && $snake['length'] >= $you['length']) {
                continue;
            }
            if ($defensive && $snake['length'] <= $you['length']) {
                continue;
            }
            $snakeHead = $lookAhead->gameData->getSnakeHead($snake['id']);
            if ($this->getManhattanDistance($youHead, $snakeHead) == $primed_distance) {
                return TRUE;
            }
        }
        return FALSE;
    }

}