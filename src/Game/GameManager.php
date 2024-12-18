<?php

namespace Battlesnake\Game;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\BoxingInMoveManager;
use Battlesnake\Moves\EdgeAvoidingMoveManager;
use Battlesnake\Moves\FoodMoveManager;
use Battlesnake\Moves\ImpossibleMoveManager;
use Battlesnake\Moves\PersonalSpaceMoveManager;
use Battlesnake\Moves\ScaredyCatMoveManager;
use Battlesnake\Moves\SurvivalMoveManager;

class GameManager {

    private GameData $gameData;
    const int MAX_DEPTH = 10;

    public function __construct(protected array $data) {
        $this->gameData = new GameData($data);
    }

    public function getPossibilities(): array {
        $ImpossibleMoveManager = new ImpossibleMoveManager($this->gameData);
        return $ImpossibleMoveManager->getMoves();
    }

    private function whittleMoves(array $possible_moves, array $moves_managers): array {
        $whittledMoves = $possible_moves;
        foreach ($moves_managers as $move_manager) {
            if (count($whittledMoves) == 1) {
                break;
            }
            $moves = $move_manager->getMoves(NULL, $whittledMoves);
//            var_dump($moves);
//            print("\n". $move_manager::class . "\n");
            if (count(array_intersect($whittledMoves, $moves)) > 0) {
                $whittledMoves = array_intersect($whittledMoves, $moves);
            }
        }
        return $whittledMoves;
    }

    public function getPreferred(array $possibleMove): array{
        $foodMoveManager = new FoodMoveManager($this->gameData);
        $scaredyCatMoveManager = new ScaredyCatMoveManager($this->gameData);
        $edgeAvoidingMoveManager = new EdgeAvoidingMoveManager($this->gameData);
        $boxingInMoveManager = new BoxingInMoveManager($this->gameData);
        $survivalMoveManager = new SurvivalMoveManager($this->gameData);
        $PersonalSpaceMoveManager = new PersonalSpaceMoveManager($this->gameData);
        $health = $this->gameData->getYou()['health'];
        $otherSnakeCount = count(array_filter($this->gameData->getSnakes(), function($snake) {
            return $snake['id'] != $this->gameData->getYou()['id'];
        }));
        $bigger_snakes = $this->gameData->getBiggerSnakes();
        if ($otherSnakeCount > 1 && $health > 50) {
            $crowded_move_managers = [
                $scaredyCatMoveManager,
                $survivalMoveManager,
                $PersonalSpaceMoveManager,
                $foodMoveManager,
                $edgeAvoidingMoveManager,
                $boxingInMoveManager
            ];
            $possibleMove = $this->whittleMoves($possibleMove, $crowded_move_managers);
        }
        else {
            $default_move_managers = [
                $scaredyCatMoveManager,
                $survivalMoveManager,
                $foodMoveManager,
                $edgeAvoidingMoveManager,
                $boxingInMoveManager
            ];
            $possibleMove = $this->whittleMoves($possibleMove, $default_move_managers);
        }
        return $possibleMove;
    }

    public function getMove(): string{
        $possibleMove = $this->getPossibilities();
//        if ($this->gameData->getTurn() < 3) {
//            return !empty($possibleMove) ? $possibleMove[array_rand($possibleMove)] : MoveDirections::UP;
//        }
        $possibleMove = $this->getPreferred($possibleMove);

        return !empty($possibleMove) ? $possibleMove[array_rand($possibleMove)] : MoveDirections::UP;
    }

}