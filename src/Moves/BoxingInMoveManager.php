<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;

class BoxingInMoveManager extends BaseMoveManager
{

    public function getMoves(string|null $snakeId = ''): array
    {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $possibleMoves = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
        $BoxingInMoves = array_filter($possibleMoves, function ($move) use ($snakeId) {
            $new_head = $this->getNewHead($this->gameData->getSnakeHead($snakeId), $move);
            $nearest_snake = [];
            $nearest_snake_distance = 1000000;
            foreach ($this->gameData->getSnakes() as $snake) {
                if ($snake['id'] == $snakeId || $this->gameData->getSnakeLength($snake['id']) >= $this->gameData->getSnakeLength($snakeId)) {
                    continue;
                }
                $distance = abs($snake['head']['x'] - $new_head['x']) + abs($snake['head']['y'] - $new_head['y']);
                if ($distance < $nearest_snake_distance) {
                    $nearest_snake_distance = $distance;
                    $nearest_snake = $snake;
                }
            }
            if (!empty($nearest_snake)) {
                $goal_square = [];
                $nearest_snake_head = $nearest_snake['head'];
                $one_third_width = floor($this->gameData->getBoardWidth() / 3);
                $one_third_height = floor($this->gameData->getBoardHeight() / 3);
                if ($nearest_snake_head['x'] <= $one_third_width) {
                    $goal_square = ['x' => $nearest_snake_head['x'] + 1, 'y' => $nearest_snake_head['y']];
                }
                if ($nearest_snake_head['x'] >= $this->gameData->getBoardWidth() - $one_third_width) {
                    $goal_square = ['x' => $nearest_snake_head['x'] - 1, 'y' => $nearest_snake_head['y']];
                }
                if ($nearest_snake_head['y'] <= $one_third_height) {
                    $goal_square = ['x' => $nearest_snake_head['x'], 'y' => $nearest_snake_head['y'] + 1];
                }
                if ($nearest_snake_head['y'] >= $this->gameData->getBoardHeight() - $one_third_height) {
                    $goal_square = ['x' => $nearest_snake_head['x'], 'y' => $nearest_snake_head['y'] - 1];
                }
                // Select possible moves that go towards goal.
                if ($goal_square['x'] >= $new_head['x'] && $move == MoveDirections::RIGHT) {
                    return true;
                }
                if ($goal_square['x'] <= $new_head['x'] && $move == MoveDirections::LEFT) {
                    return true;
                }
                if ($goal_square['y'] >= $new_head['y'] && $move == MoveDirections::UP) {
                    return true;
                }
                if ($goal_square['y'] <= $new_head['y'] && $move == MoveDirections::DOWN) {
                    return true;
                }
                return false;
            }
            return false;
        });
        return $BoxingInMoves;
    }

}