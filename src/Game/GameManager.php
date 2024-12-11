<?php

namespace Battlesnake\Game;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\BoxingInMoveManager;
use Battlesnake\Moves\EdgeAvoidingMoveManager;
use Battlesnake\Moves\FoodMoveManager;
use Battlesnake\Moves\ImpossibleMoveManager;
use Battlesnake\Moves\ScaredyCatMoveManager;

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

    public function getPreferred(array $possibleMove): array{
        $foodMoveManager = new FoodMoveManager($this->gameData);
        $food_moves = $foodMoveManager->getMoves();
        $scaredyCatMoveManager = new ScaredyCatMoveManager($this->gameData);
        $scaredyCat_moves = $scaredyCatMoveManager->getMoves();
        $edgeAvoidingMoveManager = new EdgeAvoidingMoveManager($this->gameData);
        $edgeAvoiding_moves = $edgeAvoidingMoveManager->getMoves();
        $boxingInMoveManager = new BoxingInMoveManager($this->gameData);
        $boxingIn_moves = $boxingInMoveManager->getMoves();
        $health = $this->gameData->getYou()['health'];
        if (count(array_intersect($possibleMove, $scaredyCat_moves)) > 0) {
            $possibleMove = array_intersect($possibleMove, $scaredyCat_moves);
        }
        if (count(array_intersect($possibleMove, $food_moves)) > 0) {
            $possibleMove = array_intersect($possibleMove, $food_moves);
        }
        if (count(array_intersect($possibleMove, $edgeAvoiding_moves)) > 0) {
            $possibleMove = array_intersect($possibleMove, $edgeAvoiding_moves);
        }
        if (count(array_intersect($possibleMove, $boxingIn_moves)) > 0) {
            $possibleMove = array_intersect($possibleMove, $boxingIn_moves);
        }

        return $possibleMove;
    }

    public function getMove(): string{
        $possibleMove = $this->getPossibilities();
        $survivable_moves = $this->testMoves($possibleMove);
        if (count($survivable_moves) > 0) {
            $possibleMove = $survivable_moves;
        }
        $possibleMove = $this->getPreferred($possibleMove);
        if (count($possibleMove) > 0) {
            $possibleMove = $this->maximizeDeadSnakes($possibleMove);
        }

        return !empty($possibleMove) ? $possibleMove[array_rand($possibleMove)] : MoveDirections::UP;
    }

    public function maximizeDeadSnakes(array $possibleMove): array {
        $maxDeadSnakes = 0;
        $maxDeadSnakesMove = [];
        foreach ($possibleMove as $move) {
            $newGameData = $this->gameData->getNextMoveGameData($move);
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

    public function testMoves(array $possibleMoves) {
        $survivable_moves = [];
        foreach ($possibleMoves as $move) {
           $newGameData = $this->gameData->getNextMoveGameData($move);
           if ($this->canSurvive($newGameData, self::MAX_DEPTH)) {
               $survivable_moves[] = $move;
           }
        }
        return $survivable_moves;
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
        $newSnakeCount = count($newGameData->getSnakes());
        if ($newSnakeCount < $startingSnakeCount) {
            $deadSnakes = $deadSnakes + ($startingSnakeCount - $newSnakeCount);
        }
        return $this->redrum($newGameData, $stepsRemaining - 1, $deadSnakes);
    }

    public function canSurvive(GameData $gameData, int $stepsRemaining): bool {
        // Base case: If we have no more steps to survive, we've succeeded.
        if ($stepsRemaining <= 0) {
            return true;
        }

        // Instantiate a temporary manager to explore this state
        $impossibleMoveManager = new ImpossibleMoveManager($gameData);
        $nextMoves = $impossibleMoveManager->getMoves();

        // If no moves are possible, we can't survive
        if (empty($nextMoves)) {
            return false;
        }

        // Try each possible move
        foreach($nextMoves as $nextMove) {
            $nextState = $gameData->getNextMoveGameData($nextMove);
            if ($this->canSurvive($nextState, $stepsRemaining - 1)) {
                return true;
            }
        }

        // If none of the moves worked out, we cannot survive
        return false;
    }


}