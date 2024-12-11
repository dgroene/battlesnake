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
        $possibleMoves = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
        $edgeAvoidingMoves = [];
        $edgeAvoidingScore = 0;
        $boardWidth = $this->gameData->getBoardWidth();
        $boardHeight = $this->gameData->getBoardHeight();
        foreach ($possibleMoves as $move) {
            $new_head = $this->getNewHead($this->gameData->getSnakeHead($snakeId), $move);
            $distances_to_edge = [
                $new_head['x'],
                $boardWidth - $new_head['x'],
                $new_head['y'],
                $boardHeight - $new_head['y']
            ];
            $moveEdgeAvoidingScore = min($distances_to_edge);
            if ($moveEdgeAvoidingScore > $edgeAvoidingScore) {
                $edgeAvoidingScore = $moveEdgeAvoidingScore;
                $edgeAvoidingMoves = [$move];
            } elseif ($moveEdgeAvoidingScore == $edgeAvoidingScore) {
                $edgeAvoidingMoves[] = $move;
            }
        }
        return $edgeAvoidingMoves;
    }

}