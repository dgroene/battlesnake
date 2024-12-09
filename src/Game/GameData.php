<?php

namespace Battlesnake\Game;

class GameData {

    public function __construct(protected array $data) {
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
}