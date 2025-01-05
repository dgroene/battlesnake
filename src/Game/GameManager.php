<?php

namespace Battlesnake\Game;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\SmarterSurvivalMoveManager;

class GameManager {

    private GameData $gameData;

    public function __construct(protected array $data) {
        $this->gameData = new GameData($data);
    }

    public function getMove(): string{
        $smarterSurvivalMoveManager = new SmarterSurvivalMoveManager($this->gameData);
        $possibleMove = $smarterSurvivalMoveManager->getMoves();
        return !empty($possibleMove) ? $possibleMove[array_rand($possibleMove)] : MoveDirections::UP;
    }

}