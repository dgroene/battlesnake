<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;

class ImpossibleMoveManager extends BaseMoveManager {

    public function getMoves(string | NULL $snakeId = ''): array
    {
        $possibleMoves = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $possibleMoves = array_filter($possibleMoves, function($move) use ($snakeId){
            $new_head = $this->getNewHead($this->gameData->getSnakeHead($snakeId), $move);

            // Exclude moves that take you off the board
            if ($new_head['x'] < 0 || $new_head['x'] >= $this->gameData->getBoardWidth() || $new_head['y'] < 0 || $new_head['y'] >= $this->gameData->getBoardHeight()) {
                return false;
            }
            // Exclude moves that collide with own body after removing tail.
            $new_body = array_slice($this->gameData->getSnakeBody($snakeId), 0, -1);
            if (in_array($new_head,$new_body)) {
                return false;
            }
            // Exclude moves that collide with other snakes
            foreach ($this->gameData->getSnakes() as $snake) {
                if ($snake['id'] == $snakeId) {
                    continue;
                }
                $other_snake_body = $this->gameData->getSnakeBody($snake['id']);
                array_pop($other_snake_body);
                if (in_array($new_head, $other_snake_body)) {
                    return false;
                }
            }
            return true;
        });
        return $possibleMoves;
    }

}