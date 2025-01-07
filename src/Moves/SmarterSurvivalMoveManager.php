<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;

class SmarterSurvivalMoveManager extends BaseMoveManager {

    const int MAX_DEPTH = 8;

    public function getMoves(string|null $snakeId = '', $allMoves = self::ALLMOVES): array {
        $impossibleMoveManager = new ImpossibleMoveManager($this->gameData);
        $possibleMoves = $impossibleMoveManager->getMoves();
        $moveLookAheads = [];
        foreach ($possibleMoves as $move) {
            $newGameData = $this->gameData->getNextMoveGameData($move);
            if ($newGameData === NULL) {
                $moveLookAheads[$move] = new LookAhead(NULL, 0);
                continue;
            }
            $moveLookAheads[$move] = $this->getDeepestLookAhead($newGameData, self::MAX_DEPTH);
        }
        return [$this->getBestMove($moveLookAheads, TRUE)];
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
                $thisPath = new LookAhead($gameData, self::MAX_DEPTH - $stepsRemaining);
            }
            else $thisPath = $this->getDeepestLookAhead($nextState, $stepsRemaining - 1);

            if ($thisPath->depth > $bestMove->depth) {
                $bestMove = $thisPath;
            }
            else if ($thisPath->depth == $bestMove->depth) {
                $tiebreak = $this->getBestMove(['existing' => $bestMove, $nextMove => $thisPath]);
                if ($tiebreak == $nextMove) {
                    $bestMove = $thisPath;
                }
                $bestMove = $thisPath;
            }
        }
        return $bestMove;
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
            $minAccessibleSquares = 10;
            $minAccessibleMoves = [];
            foreach ($lookAheads as $move => $lookAhead) {
                $myAccessibleSquaresPenalty = 0;
                $lookAheadTotalAccessibleSquares = 0;
                foreach ($lookAhead->gameData->getSnakes() as $snake) {
                    if ($snake['id'] == $lookAhead->gameData->getYou()['id']) {
                        $myAccessibleSquares = $lookAhead->gameData->calculateAccessibleSquares($lookAhead->gameData->getSnakeHead($snake['id']), $snake['id']);
                        if ($myAccessibleSquares < 11) {
                            $myAccessibleSquaresPenalty = 11;
                        }
                        continue;
                    }
                    $lookAheadTotalAccessibleSquares += $lookAhead->gameData->calculateAccessibleSquares($lookAhead->gameData->getSnakeHead($snake['id']), $snake['id']);
                }
                if ($lookAheadTotalAccessibleSquares < $minAccessibleSquares) {
                    $minAccessibleSquares = $lookAheadTotalAccessibleSquares;
                    $minAccessibleMoves = [$move];
                }
                else if ($lookAheadTotalAccessibleSquares == $minAccessibleSquares) {
                    $minAccessibleMoves[] = $move;
                }
                $points[$move] -= $myAccessibleSquaresPenalty;
            }
            foreach ($minAccessibleMoves as $minAccessibleMove) {
                $points[$minAccessibleMove] += 10;
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
                $points[$move] += 9;
            }
            if ($this->gameData->getSnakeCount() - $lookAhead->gameData->getSnakeCount() == $maxDeadSnakes) {
                $points[$move] += 7;
            }
            if ($lookAhead->gameData->getYou()['health'] + $lookAhead->depth == $maxHealth) {
                $points[$move] += 8;
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