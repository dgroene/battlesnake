<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\BaseMoveManager;

class ScaredyCatMoveManager extends BaseMoveManager {

    public function getMoves(string | NULL $snakeId = ''): array
    {
        $possibleMoves = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $possibleMoves = array_filter($possibleMoves, function($move) use ($snakeId){
            $new_head = $this->getNewHead($this->gameData->getSnakeHead($snakeId), $move);

            // Exclude moves that cound collide with bigger snakes.
            foreach ($this->gameData->getSnakes() as $snake) {
                if ($snake['id'] == $snakeId) {
                    continue;
                }
                if ($this->gameData->getSnakeLength($snake['id']) >= $this->gameData->getSnakeLength($snakeId)) {
                    $other_snake_head = $this->gameData->getSnakeHead($snake['id']);
                    $possible_head_moves = [
                        ['x' => $other_snake_head['x'], 'y' => $other_snake_head['y'] + 1],
                        ['x' => $other_snake_head['x'], 'y' => $other_snake_head['y'] - 1],
                        ['x' => $other_snake_head['x'] + 1, 'y' => $other_snake_head['y']],
                        ['x' => $other_snake_head['x'] - 1, 'y' => $other_snake_head['y']]
                    ];
                    if (in_array($new_head, $possible_head_moves)) {
                        return false;
                    }
                }
            }
            return true;
        });
        return $possibleMoves;
    }
}