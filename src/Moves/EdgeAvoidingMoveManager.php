<?php

namespace Battlesnake\Moves;

use Battlesnake\Enums\MoveDirections;

class EdgeAvoidingMoveManager extends BaseMoveManager
{

    public function getMoves(string|null $snakeId = ''): array
    {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $edgeAvoidingMoves = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
        $edgeAvoidingMoves = array_filter($edgeAvoidingMoves, function ($move) use ($snakeId) {
            $new_head = $this->getNewHead($this->gameData->getSnakeHead($snakeId), $move);
            $boardWidth = $this->gameData->getBoardWidth();
            $boardHeight = $this->gameData->getBoardHeight();
            if ($new_head['x'] == 0 && $move == MoveDirections::LEFT) {
                return false;
            }
            if ($new_head['x'] == $boardWidth - 1 && $move == MoveDirections::RIGHT) {
                return false;
            }
            if ($new_head['y'] == 0 && $move == MoveDirections::DOWN) {
                return false;
            }
            if ($new_head['y'] == $boardHeight - 1 && $move == MoveDirections::UP) {
                return false;
            }
            return true;
        });
        return $edgeAvoidingMoves;
    }

}