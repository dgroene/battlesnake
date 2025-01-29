<?php

namespace Battlesnake\Game;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\FoodMoveManager;
use Battlesnake\Moves\ImpossibleMoveManager;
use Battlesnake\Moves\SmarterSurvivalMoveManager;

class GameManager {

    private GameData $gameData;

    public function __construct(protected array $data) {
        $this->gameData = new GameData($data);
    }

    public function getMove(): string {
        if ($this->gameData->getTurn() < 3) {
            $impossibleMoveManager = new ImpossibleMoveManager($this->gameData);
            $possibleMove = $impossibleMoveManager->getMoves();
            $foodMoveManager = new FoodMoveManager($this->gameData);
            $foodMove = $foodMoveManager->getMoves();
            if (!empty(array_intersect($possibleMove, $foodMove))) {
                $possibleMove = array_intersect($possibleMove, $foodMove);
            }
        }
        else {
            $smarterSurvivalMoveManager = new SmarterSurvivalMoveManager($this->gameData);
            $possibleMove = $smarterSurvivalMoveManager->getMoves();
        }
        return !empty($possibleMove) ? $possibleMove[array_rand($possibleMove)] : MoveDirections::UP;
    }

}