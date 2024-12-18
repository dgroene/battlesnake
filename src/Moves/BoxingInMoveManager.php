<?php

namespace Battlesnake\Moves;

class BoxingInMoveManager extends BaseMoveManager
{

    private const EDGE_MULTIPLIER = 2.0;
    private const DISTANCE_THRESHOLD = 3;
    private const CLOSE_ENEMY_MULTIPLIER = 1.5;

    public function getMoves(string|null $snakeId = '', $allMoves = self::ALLMOVES): array
    {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $possibleMoves = $allMoves;
        $boxiestMove = [];
        $boxiestTotal = 0;
        foreach ($possibleMoves as $move) {
            $snakes = $this->gameData->getSnakes();
            $snakes = array_filter($snakes, function($snake) use ($snakeId){
                return $snake['id'] != $snakeId;
            });
            $new_game_data = $this->gameData->getNextMoveGameData($move);
            if ($new_game_data == NULL) {
                continue;
            }
            $boxyTotal = 0;
            foreach ($snakes as $snake) {
                $enemy_snake_id = $snake['id'];
                $enemy_snake_head = $snake['head'];
                $current_accessible_squares = $this->gameData->calculateAccessibleSquares($enemy_snake_head, $enemy_snake_id);
                if (empty($new_game_data->getSnakeById($enemy_snake_id))) {
                    $boxyDelta = 50;
                }
                else {
                    $new_snake_head = $new_game_data->getSnakeHead($enemy_snake_id);
                    $new_accessible_squares = $this->gameData->calculateAccessibleSquares($new_snake_head, $enemy_snake_id);
                    $boxyDelta = $current_accessible_squares - $new_accessible_squares;
                    if (empty($new_snake_head)) {
                        $boxyDelta = 50;
                    }
                    if ($this->isNearEdge($new_snake_head)) {
                        $boxyDelta *= self::EDGE_MULTIPLIER;
                    }
                    $distance_from_snake = $this->getManhattanDistance($new_snake_head, $this->gameData->getSnakeHead($snakeId));
                    if ($distance_from_snake <= self::DISTANCE_THRESHOLD) {
                        $boxyDelta *= self::CLOSE_ENEMY_MULTIPLIER;
                    }
                }
                $boxyTotal += $boxyDelta;
            }
            if ($boxyTotal > $boxiestTotal) {
                $boxiestTotal = $boxyTotal;
                $boxiestMove = [$move];
            } elseif ($boxyTotal == $boxiestTotal) {
                $boxiestMove[] = $move;
            }
        }
        return $boxiestMove;
    }

}