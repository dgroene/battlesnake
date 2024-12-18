<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;

class FoodMoveManager extends BaseMoveManager
{

    public function getMoves(string|null $snakeId = '', $allMoves = self::ALLMOVES): array
    {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $foodMoves = $allMoves;

        $foodMoves = array_filter($foodMoves, function ($move) use ($snakeId) {
            $new_head = $this->getNewHead($this->gameData->getSnakeHead($snakeId), $move);
            $food = $this->gameData->getFood();
            if (!$this->gameData->amIDying()) {
                $food = array_filter($food, function ($food_item) {
                    if ($food_item['x'] == 0 || $food_item['x'] == $this->gameData->getBoardWidth() - 1
                        || $food_item['y'] == 0 || $food_item['y'] == $this->gameData->getBoardHeight() - 1) {
                        return false;
                    }
                    return true;
                });
            }
            $min_food_distance = 1000000;
            $min_food = [];
            foreach ($food as $food_item) {
                $food_distance = $this->getManhattanDistance($new_head, $food_item);
                if ($food_distance < $min_food_distance && $this->canIGetThereFirst($food_item)) {
                    $min_food_distance = $food_distance;
                    $min_food = $food_item;
                }
            }
            if (empty($min_food) && !empty($food)) {
                // Find a target area where food is clustered.
                $xcoordinates = array_column($food, 'x');
                $ycoordinates = array_column($food, 'y');
                sort($xcoordinates);
                sort($ycoordinates);
                $count = count($food);
                if ($count % 2 == 1) {
                    $medianX = $xcoordinates[floor($count / 2)];
                    $medianY = $ycoordinates[floor($count / 2)];
                }
                else {
                    $mid = $count / 2;
                    $medianX = ($xcoordinates[$mid - 1] + $xcoordinates[$mid]) / 2;
                    $medianY = ($ycoordinates[$mid - 1] + $ycoordinates[$mid]) / 2;
                }
                $min_food = ['x' => $medianX, 'y' => $medianY];
            }
            if (!empty($min_food)) {
                if ($min_food['x'] >= $new_head['x'] && $move == MoveDirections::RIGHT) {
                    return true;
                }
                if ($min_food['x'] <= $new_head['x'] && $move == MoveDirections::LEFT) {
                    return true;
                }
                if ($min_food['y'] >= $new_head['y'] && $move == MoveDirections::UP) {
                    return true;
                }
                if ($min_food['y'] <= $new_head['y'] && $move == MoveDirections::DOWN) {
                    return true;
                }
            }
            return false;
        });
        return $foodMoves;
    }
    public function canIGetThereFirst(array $food_item, string | NULL $snakeId = NULL): bool {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $closest_snake = $snakeId;
        $closest_snake_distance = $this->getManhattanDistance($this->gameData->getSnakeHead($snakeId), $food_item);
        foreach ($this->gameData->getSnakes() as $snake) {
            if ($snake['id'] == $snakeId) {
                continue;
            }
            $distance = $this->getManhattanDistance($snake['head'], $food_item);
            if ($distance <= $closest_snake_distance) {
                $closest_snake_distance = $distance;
                $closest_snake = $snake['id'];
            }
        }
        return $closest_snake == $snakeId;
    }

}