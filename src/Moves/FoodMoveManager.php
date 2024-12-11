<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;

class FoodMoveManager extends BaseMoveManager
{

    public function getMoves(string|null $snakeId = ''): array
    {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $foodMoves = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
        $foodMoves = array_filter($foodMoves, function ($move) use ($snakeId) {
            $new_head = $this->getNewHead($this->gameData->getSnakeHead($snakeId), $move);
            $food = $this->gameData->getFood();
            $food = array_filter($food, function ($food_item) {
                if ($food_item['x'] == 0 || $food_item['x'] == $this->gameData->getBoardWidth() - 1
                    || $food_item['y'] == 0 || $food_item['y'] == $this->gameData->getBoardHeight() - 1) {
                    return false;
                }
                return true;
            });
            $min_food_distance = 1000000;
            $min_food = [];
            foreach ($food as $food_item) {
                $food_distance = abs($food_item['x'] - $new_head['x']) + abs($food_item['y'] - $new_head['y']);
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
    public function canIGetThereFirst(array $food_item, string | NULL $snakeId = NULL) {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $my_snake = $this->gameData->getSnakeById($snakeId);
        $closest_snake = $my_snake;
        $closest_snake_distance = abs($my_snake['head']['x'] - $food_item['x']) + abs($my_snake['head']['y'] - $food_item['y']);
        foreach ($this->gameData->getSnakes() as $snake) {
            if ($snake['id'] == $snakeId) {
                continue;
            }
            $distance = abs($snake['head']['x'] - $food_item['x']) + abs($snake['head']['y'] - $food_item['y']);
            if ($distance <= $closest_snake_distance) {
                $closest_snake_distance = $distance;
                $closest_snake = $snake;
            }
        }
        return $closest_snake['id'] == $snakeId;
    }

}