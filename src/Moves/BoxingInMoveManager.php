<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Game\GameData;

class BoxingInMoveManager extends BaseMoveManager
{

    public function getMoves(string|null $snakeId = ''): array
    {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $possibleMoves = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
        $boxiestMove = [];
        $boxiestTotal = 0;
        foreach ($possibleMoves as $move) {
            $snakes = $this->gameData->getSnakes();
            $snakes = array_filter($snakes, function($snake) use ($snakeId){
                return $snake['id'] != $snakeId;
            });
            $boxyTotal = 0;
            foreach ($snakes as $snake) {
                $current_accessible_squares = $this->calculateAccessibleSquares($snake['head'], $this->gameData);
                $new_head = $this->getNewHead($this->gameData->getSnakeHead($snakeId), $move);
                $new_game_data = $this->gameData->getNextMoveGameData($move);
                $new_snake_head = $new_game_data->getSnakeHead($snakeId);
                if (empty($new_snake_head)) {
                    $new_accessible_squares = 0;
                }
                else {
                    $new_accessible_squares = $this->calculateAccessibleSquares($new_snake_head, $new_game_data);
                }
                $boxyTotal += ($current_accessible_squares - $new_accessible_squares);
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

    public function calculateAccessibleSquares(array $head, GameData $gameData): int {
        $directions = [
            MoveDirections::UP,
            MoveDirections::DOWN,
            MoveDirections::LEFT,
            MoveDirections::RIGHT
        ];

        $maxDepth = 10; // Number of moves to look ahead
        $width = $gameData->getBoardWidth();   // e.g. 11
        $height = $gameData->getBoardHeight(); // e.g. 11

        // Visited array to avoid revisiting cells
        // Using a boolean array keyed by "x,y"
        $visited = [];
        $startKey = "{$head['x']},{$head['y']}";
        $visited[$startKey] = true;

        // Queue holds entries like [x, y, depth]
        $queue = [[$head['x'], $head['y'], 0]];

        // Count accessible squares
        // Decide whether to count the starting square as accessible
        // Usually you would, since it's where the snake's head currently is.
        $accessible_squares = 1;

        while (!empty($queue)) {
            list($x, $y, $depth) = array_shift($queue);

            // If we've reached max depth, do not expand further
            if ($depth >= $maxDepth) {
                continue;
            }

            // Explore neighbors
            foreach ($directions as $dir) {
                if ($dir == MoveDirections::UP) {
                    $nh = ['x' => 0, 'y' => -1];
                } elseif ($dir == MoveDirections::DOWN) {
                    $nh = ['x' => 0, 'y' => 1];
                } elseif ($dir == MoveDirections::LEFT) {
                    $nh = ['x' => -1, 'y' => 0];
                } elseif ($dir == MoveDirections::RIGHT) {
                    $nh = ['x' => 1, 'y' => 0];
                }
                $nx = $x + $nh['x'];
                $ny = $y + $nh['y'];
                $key = "$nx,$ny";

                // Check board boundaries
                if ($nx <= 0 || $nx >= $width || $ny <= 0 || $ny >= $height) {
                    continue;
                }

                // Check if this cell is safe (not a snake body, not blocked)
                if (!$gameData->isCellSafe($nx, $ny)) {
                    continue;
                }

                // Check if visited
                if (isset($visited[$key])) {
                    continue;
                }

                // Mark visited and add to the queue
                $visited[$key] = true;
                $accessible_squares++;
                $queue[] = [$nx, $ny, $depth + 1];
            }
        }

        return $accessible_squares;
    }

}