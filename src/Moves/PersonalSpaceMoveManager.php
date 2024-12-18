<?php

namespace Battlesnake\Moves;

class PersonalSpaceMoveManager extends BaseMoveManager
{

    public function getMoves(string|null $snakeId = '', $allMoves = self::ALLMOVES): array
    {
        if ($snakeId == NULL) {
            $snakeId = $this->gameData->getYou()['id'];
        }
        $possibleMoves = $allMoves;
        $boardWidth = $this->gameData->getBoardWidth();
        $boardHeight = $this->gameData->getBoardHeight();
        $spaciestMove = [];
        $spaciestTotal = 0;
        foreach ($possibleMoves as $move) {
            $new_game_data = $this->gameData->getNextMoveGameData($move);
            if ($new_game_data == NULL) {
                continue;
            }
            $new_head = $new_game_data->getSnakeHead($snakeId);
            $spacyTotal = 0;
            for ($i = $new_head['x'] + 1; $i < $boardWidth; $i++) {
                if (!$new_game_data->isCellSafe($i, $new_head['y'], $snakeId)) {
                    break;
                }
                $spacyTotal++;
            }
            for ($i = $new_head['x'] - 1; $i >= 0; $i--) {
                if (!$new_game_data->isCellSafe($i, $new_head['y'], $snakeId)) {
                    break;
                }
                $spacyTotal++;
            }
            for ($i = $new_head['y'] + 1; $i < $boardHeight; $i++) {
                if (!$new_game_data->isCellSafe($new_head['x'], $i, $snakeId)) {
                    break;
                }
                $spacyTotal++;
            }
            for ($i = $new_head['y'] - 1; $i >= 0; $i--) {
                if (!$new_game_data->isCellSafe($new_head['x'], $i, $snakeId)) {
                    break;
                }
                $spacyTotal++;
            }
            if ($spacyTotal > $spaciestTotal) {
                $spaciestTotal = $spacyTotal;
                $spaciestMove = [$move];
            } elseif ($spacyTotal == $spaciestTotal) {
                $spaciestMove[] = $move;
            }
        }
        return $spaciestMove;
    }

}