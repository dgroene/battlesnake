<?php

namespace Battlesnake\Game;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\ImpossibleMoveManager;

class GameData {

    public function __construct(protected array $data) {
    }

    public function getData(): array {
        return $this->data;
    }

    public function getBoard(): array {
        return $this->data['board'];
    }

    public function getGame(): array {
        return $this->data['game'];
    }

    public function getYou(): array {
        return $this->data['you'];
    }

    public function getyouHead(): array {
        return $this->data['you']['head'];
    }

    public function getyouBody(): array {
        return $this->data['you']['body'];
    }

    public function getyouLength(): int {
        return count($this->data['you']['body']);
    }

    public function getTurn(): int {
        return $this->data['turn'];
    }

    public function getBoardHeight(): int {
        return $this->data['board']['height'];
    }

    public function getBoardWidth(): int {
        return $this->data['board']['width'];
    }

    public function getFood(): array {
        return $this->data['board']['food'];
    }

    public function getSnakes(): array {
        return $this->data['board']['snakes'];
    }

    public function getSnakeById(string $id): array {
        foreach ($this->getSnakes() as $snake) {
            if ($snake['id'] === $id) {
                return $snake;
            }
        }
        return [];
    }

    public function getSnakeByIndex(int $index): array {
        return $this->getSnakes()[$index];
    }

    public function getSnakeCount(): int {
        return count($this->getSnakes());
    }

    public function getSnakeHealth(string $id): int {
        return $this->getSnakeById($id)['health'];
    }

    public function getSnakeLength(string $id): int {
        return count($this->getSnakeById($id)['body']);
    }

    public function getSnakeHead(string $id): array {
        return $this->getSnakeById($id)['head'];
    }

    public function getSnakeBody(string $id): array {
        return $this->getSnakeById($id)['body'];
    }

    public function getSnakeTail(string $id): array {
        return $this->getSnakeById($id)['body'][count($this->getSnakeBody($id)) - 1];
    }

    public function isCellSafe(int $x, int $y): bool {
        foreach ($this->getSnakes() as $snake) {
            foreach ($snake['body'] as $bodyPart) {
                if ($bodyPart['x'] == $x && $bodyPart['y'] == $y) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getNextMoveGameData(string $move): GameData {
        $ImpossibleMoveManager = new ImpossibleMoveManager($this);
        $newGameData = $this->data;
        $new_head = $ImpossibleMoveManager->getNewHead($this->getYouHead(), $move);
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

        $dead_snakes = [];
        for ($i = 0; $i < count($newGameData['board']['snakes']); $i++) {
            if ($my_id == $newGameData['board']['snakes'][$i]['id']) {
                continue;
            }
            $snake = $newGameData['board']['snakes'][$i];
            $moves = $ImpossibleMoveManager->getMoves($snake['id']);
            if (empty($moves)) {
                $dead_snakes[] = $snake['id'];
                continue;
            }
            $move = $moves[array_rand($moves)];
            $new_head = $ImpossibleMoveManager->getNewHead($snake['head'], $move);
            $new_body = $this->getNextMoveBody($snake['body'], $new_head);
            $newGameData['board']['snakes'][$i]['head'] = $new_head;
            $newGameData['board']['snakes'][$i]['body'] = $new_body;
        }
        foreach ($dead_snakes as $dead_snake) {
            $newGameData['board']['snakes'] = array_filter($newGameData['board']['snakes'], function($snake) use ($dead_snake) {
                return $snake['id'] != $dead_snake;
            });
            $newGameData['board']['snakes'] = array_values($newGameData['board']['snakes']);
        }
        return new self($newGameData);
    }

    protected function getNextMoveBody($body, $newHead) {
        $newBody = $body;
        array_unshift($newBody, $newHead);
        array_pop($newBody);
        return $newBody;
    }
}