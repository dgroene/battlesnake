<?php

namespace Battlesnake\Game;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\BoxingInMoveManager;
use Battlesnake\Moves\EdgeAvoidingMoveManager;
use Battlesnake\Moves\FoodMoveManager;
use Battlesnake\Moves\ImpossibleMoveManager;
use Battlesnake\Moves\ScaredyCatMoveManager;
use Battlesnake\Moves\SurvivalMoveManager;

class GameManager {

    private GameData $gameData;
    const int MAX_DEPTH = 15;

    public function __construct(protected array $data) {
        $this->gameData = new GameData($data);
    }

    public function getPossibilities(): array {
        $ImpossibleMoveManager = new ImpossibleMoveManager($this->gameData);
        $possibleMove = $ImpossibleMoveManager->getMoves();

        return $possibleMove;
    }

    private function whittleMoves(array $moves_in_order): array {
        $whittledMoves = [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
        foreach ($moves_in_order as $moves) {
            if (count(array_intersect($whittledMoves, $moves)) > 0) {
                $whittledMoves = array_intersect($whittledMoves, $moves);
            }
        }
        return $whittledMoves;
    }

    public function getPreferred(array $possibleMove): array{
        $foodMoveManager = new FoodMoveManager($this->gameData);
        $food_moves = $foodMoveManager->getMoves();
        $scaredyCatMoveManager = new ScaredyCatMoveManager($this->gameData);
        $scaredyCat_moves = $scaredyCatMoveManager->getMoves();
        $edgeAvoidingMoveManager = new EdgeAvoidingMoveManager($this->gameData);
        $edgeAvoiding_moves = $edgeAvoidingMoveManager->getMoves();
        $boxingInMoveManager = new BoxingInMoveManager($this->gameData);
        $boxingIn_moves = $boxingInMoveManager->getMoves();
        $survivalMoveManager = new SurvivalMoveManager($this->gameData);
        $survival_moves = $survivalMoveManager->getMoves();
        $health = $this->gameData->getYou()['health'];

        if ($health < 20) {
            $possibleMove = $this->whittleMoves([$possibleMove, $food_moves, $scaredyCat_moves, $survival_moves, $edgeAvoiding_moves, $boxingIn_moves]);
        }
        else if ($this->gameData->getSnakeCount() < 3) {
            $possibleMove = $this->whittleMoves([$possibleMove, $scaredyCat_moves, $survival_moves, $boxingIn_moves, $edgeAvoiding_moves, $food_moves]);
        }
        else {
            $possibleMove = $this->whittleMoves([$possibleMove, $scaredyCat_moves, $survival_moves, $food_moves, $edgeAvoiding_moves, $boxingIn_moves]);
        }

        return $possibleMove;
    }

    public function getMove(): string{
        $possibleMove = $this->getPossibilities();
        if ($this->gameData->getTurn() < 3) {
            return !empty($possibleMove) ? $possibleMove[array_rand($possibleMove)] : MoveDirections::UP;
        }

        $possibleMove = $this->getPreferred($possibleMove);

        return !empty($possibleMove) ? $possibleMove[array_rand($possibleMove)] : MoveDirections::UP;
    }

    public function maximizeDeadSnakes(array $possibleMove): array {
        $maxDeadSnakes = 0;
        $maxDeadSnakesMove = [];
        foreach ($possibleMove as $move) {
            $newGameData = $this->gameData->getNextMoveGameData($move);
            if ($newGameData === NULL) {
                continue;
            }
            $deadSnakes = $this->redrum($newGameData, self::MAX_DEPTH, 0);
            if ($deadSnakes > $maxDeadSnakes) {
                $maxDeadSnakes = $deadSnakes;
                $maxDeadSnakesMove = [$move];
            } elseif ($deadSnakes == $maxDeadSnakes) {
                $maxDeadSnakesMove[] = $move;
            }
        }
        return $maxDeadSnakesMove;
    }

    public function redrum(GameData $gameData, int $stepsRemaining, int $deadSnakes): int {
        if ($stepsRemaining <= 0) {
            return $deadSnakes;
        }
        $startingSnakeCount = count($gameData->getSnakes());
        $tempGameManager = new GameManager($gameData->getData());
        $possibleMoves = $tempGameManager->getPossibilities();
        $preferredMoves = $tempGameManager->getPreferred($possibleMoves);
        if (empty($preferredMoves)) {
            return $deadSnakes;
        }
        if (count($preferredMoves) > 1) {
            $move = $preferredMoves[array_rand($preferredMoves)];
        } else {
            $move = array_values($preferredMoves)[0];
        }
        $newGameData = $gameData->getNextMoveGameData($move);
        if ($newGameData === NULL) {
            return $deadSnakes;
        }
        $newSnakeCount = count($newGameData->getSnakes());
        if ($newSnakeCount < $startingSnakeCount) {
            $deadSnakes = $deadSnakes + ($startingSnakeCount - $newSnakeCount);
        }
        return $this->redrum($newGameData, $stepsRemaining - 1, $deadSnakes);
    }

}