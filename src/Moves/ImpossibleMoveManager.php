<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;

class ImpossibleMoveManager extends BaseMoveManager {

    public function getMoves(string | NULL $snakeId = '', $allMoves = self::ALLMOVES): array
    {
        $possibleMoves = $allMoves;
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $possibleMoves = array_filter($possibleMoves, function($move) use ($snakeId){
            $new_head = $this->gameData->getNextMoveHead($this->gameData->getSnakeHead($snakeId), $move);

            // Exclude moves that take you off the board
            if ($new_head['x'] < 0 || $new_head['x'] >= $this->gameData->getBoardWidth() || $new_head['y'] < 0 || $new_head['y'] >= $this->gameData->getBoardHeight()) {
                return false;
            }
            // Exclude moves that collide with own body after removing tail.
            $new_body = $this->gameData->getNextMoveBody($this->gameData->getSnakeBody($snakeId), $new_head);

            if (in_array($new_head, array_slice($new_body, 1))) {
                return false;
            }
            // Exclude moves that collide with other snakes
            foreach ($this->gameData->getSnakes() as $snake) {
                if ($snake['id'] == $snakeId) {
                    continue;
                }
                $other_snake_body = $this->gameData->getSnakeBody($snake['id']);
                $other_snake_head = $this->gameData->getSnakeHead($snake['id']);
                if (!in_array($other_snake_head, $this->gameData->getFood())) {
                    array_pop($other_snake_body);
                }
                if (in_array($new_head, $other_snake_body)) {
                    return false;
                }
            }
            return true;
        });
        return $possibleMoves;
    }

}