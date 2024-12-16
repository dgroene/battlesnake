<?php

namespace Battlesnake\Game;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\BoxingInMoveManager;
use Battlesnake\Moves\ImpossibleMoveManager;

class GameData {

    public function __construct(protected array $data) {
    }

    public function getTurn(): int {
        return $this->data['turn'];
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

    public function getBiggerSnakes(): array {
        $snakes = $this->getSnakes();
        return array_filter($snakes, function($snake) {
            return $snake['length'] > $this->getyouLength() && $snake['id'] != $this->getYou()['id'];
        });
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

    public function amIDying(): bool {
        return $this->getYou()['health'] < 20;
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

    public function isCellSafe(int $x, int $y, string $snakeId = NULL): bool {
        if (empty($snakeId)) {
            $snakeId = $this->getYou()['id'];
        }
        foreach ($this->getSnakes() as $snake) {
            foreach ($snake['body'] as $index => $bodyPart) {
                if ($snake['id'] == $snakeId && $index == 0) {
                    continue;
                }
                if ($bodyPart['x'] == $x && $bodyPart['y'] == $y) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getNextMoveGameData(string $move): GameData | NULL {
        $ImpossibleMoveManager = new ImpossibleMoveManager($this);
        $newGameData = $this->data;

        $new_head = $this->getNextMoveHead($newGameData['you']['head'], $move);
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
            $aggressive_moves = [];
            foreach ($moves as $move) {
                if ($move == MoveDirections::UP && $newGameData['you']['head']['y'] > $snake['head']['y']) {
                    $aggressive_moves[] = $move;
                }
                if ($move == MoveDirections::DOWN && $newGameData['you']['head']['y'] < $snake['head']['y']) {
                    $aggressive_moves[] = $move;
                }
                if ($move == MoveDirections::LEFT && $newGameData['you']['head']['x'] < $snake['head']['x']) {
                    $aggressive_moves[] = $move;
                }
                if ($move == MoveDirections::RIGHT && $newGameData['you']['head']['x'] > $snake['head']['x']) {
                    $aggressive_moves[] = $move;
                }
            }
            if (!empty($aggressive_moves)) {
                $moves = $aggressive_moves;
            }

            $move = $moves[array_rand($moves)];
            $new_head = $this->getNextMoveHead($snake['head'], $move);
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
        $newGameDataObject = new GameData($newGameData);

        return $newGameDataObject->isCellSafe(...$newGameData['you']['head']) ? $newGameDataObject : NULL;
    }

    public function getNextMoveHead($head, $move) {
        $new_head = $head;
        if ($move == MoveDirections::UP) {
            $new_head['y']++;
        } elseif ($move == MoveDirections::DOWN) {
            $new_head['y']--;
        } elseif ($move == MoveDirections::LEFT) {
            $new_head['x']--;
        } elseif ($move == MoveDirections::RIGHT) {
            $new_head['x']++;
        }
        return $new_head;
    }

    public function getNextMoveBody($body, $newHead) {
        $newBody = $body;
        array_unshift($newBody, $newHead);
        array_pop($newBody);
        foreach($this->getFood() as $food_item) {
            if ($newHead == $food_item) {
                $newBody[] = $body[count($body) - 1];
            }
        }
        return $newBody;
    }

    public function calculateAccessibleSquares(array $head, string $snakeId): int {
        $directions = [
            MoveDirections::UP,
            MoveDirections::DOWN,
            MoveDirections::LEFT,
            MoveDirections::RIGHT
        ];

        $maxDepth = 20; // Number of moves to look ahead
        $width = $this->getBoardWidth();   // e.g. 11
        $height = $this->getBoardHeight(); // e.g. 11

        // Visited array to avoid revisiting cells
        // Using a boolean array keyed by "x,y"
        $visited = [];
        $startKey = "{$head['x']},{$head['y']}";
        $visited[$startKey] = true;

        // Queue holds entries like [x, y, depth]
        $queue = [[$head['x'], $head['y'], 0]];

        // Count accessible squares
        // Decide whether to count the starting square as accessible
        // Usually you would, since it's where the snake's head currently is.
        $accessible_squares = 1;

        while (!empty($queue)) {
            list($x, $y, $depth) = array_shift($queue);

            // If we've reached max depth, do not expand further
            if ($depth >= $maxDepth) {
                continue;
            }

            // Explore neighbors
            foreach ($directions as $dir) {
                if ($dir == MoveDirections::UP) {
                    $nh = ['x' => 0, 'y' => -1];
                } elseif ($dir == MoveDirections::DOWN) {
                    $nh = ['x' => 0, 'y' => 1];
                } elseif ($dir == MoveDirections::LEFT) {
                    $nh = ['x' => -1, 'y' => 0];
                } elseif ($dir == MoveDirections::RIGHT) {
                    $nh = ['x' => 1, 'y' => 0];
                }
                $nx = $x + $nh['x'];
                $ny = $y + $nh['y'];
                $key = "$nx,$ny";

                // Check board boundaries
                if ($nx <= 0 || $nx >= $width || $ny <= 0 || $ny >= $height) {
                    continue;
                }

                // Check if this cell is safe (not a snake body, not blocked)
                if (!$this->isCellSafe($nx, $ny, $snakeId)) {
                    continue;
                }

                // Check if visited
                if (isset($visited[$key])) {
                    continue;
                }

                // Mark visited and add to the queue
                $visited[$key] = true;
                $accessible_squares++;
                $queue[] = [$nx, $ny, $depth + 1];
            }
        }

        return $accessible_squares;
    }

}