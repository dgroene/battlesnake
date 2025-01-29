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
        if ($this->checkPrimedForKilling($lookAhead)) {
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
            $new_accessibleSquares = $lookAhead->gameData->calculateAccessibleSquares($lookAhead->gameData->getYouHead(), $lookAhead->gameData->getYou()['id']);
            $current_accessibleSquares = $this->gameData->calculateAccessibleSquares($this->gameData->getYouHead(), $this->gameData->getYou()['id']);
            if ($new_accessibleSquares > $current_accessibleSquares) {
                $score += 7;
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

    public function getBestMove(array $lookAheadsToTest, bool $useAccessibleSquares = FALSE): string {
        $lookAheads = array_filter($lookAheadsToTest, function($lookAhead) {
            return $lookAhead->gameData !== NULL && $lookAhead->depth !== NULL;
        });
        if (empty($lookAheads)) {
            $moves = array_keys($lookAheadsToTest);
            return $moves[array_rand($moves)];
        }
        if (count($lookAheads) == 1) {
            return array_keys($lookAheads)[0];
        }
        $points = [];
        foreach ($lookAheads as $move => $lookAhead) {
            $points[$move] = 0;
        }
        if ($useAccessibleSquares) {
            $currentMyAS = $this->gameData->calculateAccessibleSquares($this->gameData->getYouHead(), $this->gameData->getYou()['id']);
            $maxFreedomMoves = [];
            $maxFreedom = 0;
            foreach ($lookAheads as $move => $lookAhead) {
                $newMyAS = $lookAhead->gameData->calculateAccessibleSquares($lookAhead->gameData->getYouHead(), $lookAhead->gameData->getYou()['id']);
                $freedom = $newMyAS - $currentMyAS;
                if ($freedom > $maxFreedom) {
                    $maxFreedom = $freedom;
                    $maxFreedomMoves = [$move];
                }
                else if ($freedom == $maxFreedom) {
                    $maxFreedomMoves[] = $move;
                }
            }
            foreach ($maxFreedomMoves as $maxFreedomMove) {
                $points[$maxFreedomMove] += 9;
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
            if (!empty($closest_snake)) {
                $maxEnemyASReduction = 0;
                $maxEnemyASReductionMoves = [];
                foreach ($lookAheads as $move => $lookAhead) {
                    if (empty($lookAhead->gameData->getSnakeById($closest_snake['id']))) {
                        $maxEnemyASReduction = 1000;
                        $maxEnemyASReductionMoves = [$move];
                        continue;
                    }
                    $currentEnemyAs = $this->gameData->calculateAccessibleSquares($this->gameData->getSnakeHead($closest_snake['id']), $closest_snake['id']);
                    $newEnemyAs = $lookAhead->gameData->calculateAccessibleSquares($lookAhead->gameData->getSnakeHead($closest_snake['id']), $closest_snake['id']);
                    $lookAheadEnemyASReduction = $currentEnemyAs - $newEnemyAs;
                    if ($lookAheadEnemyASReduction > $maxEnemyASReduction) {
                        $maxEnemyASReduction = $lookAheadEnemyASReduction;
                        $maxEnemyASReductionMoves = [$move];
                    }
                    else if ($lookAheadEnemyASReduction == $maxEnemyASReduction) {
                        $maxEnemyASReductionMoves[] = $move;
                    }
                }
                foreach ($maxEnemyASReductionMoves as $maxEnemyASReductionMove) {
                    $points[$maxEnemyASReductionMove] += 9;
                }
            }
        }

        $maxDepth = max(array_map(function($lookAhead) {
            return $lookAhead->depth;
        }, $lookAheads));
        $maxHealth = max(array_map(function($lookAhead) {
            return $lookAhead->gameData->getYou()['health'] + $lookAhead->depth;
        }, $lookAheads));
        $maxSnakeLength = max(array_map(function($lookAhead) {
            return $lookAhead->gameData->getYouLength();
        }, $lookAheads));
        $maxDeadSnakes = max(array_map(function($lookAhead) {
            return $this->gameData->getSnakeCount() - $lookAhead->gameData->getSnakeCount();
        }, $lookAheads));
        foreach ($lookAheads as $move => $lookAhead) {
            $primedForKilling = $this->checkPrimedForKilling($lookAhead);
            if ($lookAhead->depth == $maxDepth) {
                $points[$move] += 10;
            }
            if ($primedForKilling) {
                $points[$move] += 8;
            }
            if ($this->gameData->getSnakeCount() - $lookAhead->gameData->getSnakeCount() == $maxDeadSnakes) {
                $points[$move] += 6;
            }
            if ($lookAhead->gameData->getYou()['health'] + $lookAhead->depth == $maxHealth) {
                $points[$move] += 7;
            }
            if ($lookAhead->gameData->getYouLength() == $maxSnakeLength) {
                $points[$move] += 9;
            }
        }
        // order the final moves by points
        arsort($points);
        return array_keys($points)[0];
    }

    private function checkPrimedForKilling(LookAhead $lookAhead): bool
    {
        $you = $lookAhead->gameData->getYou();
        $youHead = $lookAhead->gameData->getYouHead();
        foreach ($lookAhead->gameData->getSnakes() as $snake) {
            if ($snake['id'] == $you['id']) {
                continue;
            }
            if ($snake['length'] >= $you['length']) {
                continue;
            }
            $snakeHead = $lookAhead->gameData->getSnakeHead($snake['id']);
            if ($this->getManhattanDistance($youHead, $snakeHead) == 1) {
                return TRUE;
            }
        }
        return FALSE;
    }
}