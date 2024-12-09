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
        if (count(array_intersect($possibleMove, $scaredyCat_moves)) > 0) {
            $possibleMove = array_intersect($possibleMove, $scaredyCat_moves);
        }
        $snakes_left = count($this->gameData->getSnakes());
        if ($snakes_left < 3 && count(array_intersect($possibleMove, $boxingIn_moves)) > 0) {
            $possibleMove = array_intersect($possibleMove, $boxingIn_moves);
        }
        if (count(array_intersect($possibleMove, $food_moves)) > 0) {
            $possibleMove = array_intersect($possibleMove, $food_moves);
        }
        if (count(array_intersect($possibleMove, $edgeAvoiding_moves)) > 0) {
            $possibleMove = array_intersect($possibleMove, $edgeAvoiding_moves);
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

        return !empty($possibleMove) ? $possibleMove[array_rand($possibleMove)] : MoveDirections::UP;
    }

    public function testMoves(array $possibleMoves) {
        $survivable_moves = [];
        foreach ($possibleMoves as $move) {
           $newGameData = $this->getNextMoveGameData($move);
           if ($this->canSurvive($newGameData, 10)) {
               $survivable_moves[] = $move;
           }
        }
        return $survivable_moves;
    }

    public function canSurvive(array $gameData, int $stepsRemaining): bool {
        // Base case: If we have no more steps to survive, we've succeeded.
        if ($stepsRemaining <= 0) {
            return true;
        }

        // Instantiate a temporary manager to explore this state
        $tempManager = new self($gameData);
        $nextMoves = $tempManager->getPossibilities();

        // If no moves are possible, we can't survive
        if (empty($nextMoves)) {
            return false;
        }

        // Try each possible move
        foreach($nextMoves as $nextMove) {
            $nextState = $tempManager->getNextMoveGameData($nextMove);
            if ($this->canSurvive($nextState, $stepsRemaining - 1)) {
                return true;
            }
        }

        // If none of the moves worked out, we cannot survive
        return false;
    }

    public function getNextMoveGameData(string $move): array {
        $ImpossibleMoveManager = new ImpossibleMoveManager($this->gameData);
        $newGameData = $this->data;
        $new_head = $ImpossibleMoveManager->getNewHead($this->gameData->getYouHead(), $move);
        $new_body = $this->getNextMoveBody($newGameData['you']['body'], $new_head);
        $newGameData['you']['head'] = $new_head;
        $newGameData['you']['body'] = $new_body;

        $my_id = $newGameData['you']['id'];
        for ($i = 0; $i < count($newGameData['board']['snakes']); $i++) {
           if ($newGameData['board']['snakes'][$i]['id'] == $my_id) {
               $newGameData['board']['snakes'][$i]['head'] = $new_head;
               $newGameData['board']['snakes'][$i]['body'] = $new_body;
           }
        }

        for ($i = 0; $i < count($newGameData['board']['snakes']); $i++) {
            if ($my_id == $newGameData['board']['snakes'][$i]['id']) {
                continue;
            }
            $snake = $newGameData['board']['snakes'][$i];
            $moves = $ImpossibleMoveManager->getMoves($snake['id']);
            $move = !empty($moves) ? $moves[array_rand($moves)] : MoveDirections::UP;
            $new_head = $ImpossibleMoveManager->getNewHead($snake['head'], $move);
            $new_body = $this->getNextMoveBody($snake['body'], $new_head);
            $newGameData['board']['snakes'][$i]['head'] = $new_head;
            $newGameData['board']['snakes'][$i]['body'] = $new_body;
        }
        return $newGameData;
    }

    protected function getNextMoveBody($body, $newHead) {
        $newBody = $body;
        array_unshift($newBody, $newHead);
        array_pop($newBody);
        return $newBody;
    }
}