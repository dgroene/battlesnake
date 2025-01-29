<?php

namespace Battlesnake\Game;

use Battlesnake\Enums\MoveDirections;
use Battlesnake\Moves\ImpossibleMoveManager;
use Battlesnake\Moves\LookAhead;
use Battlesnake\Moves\SmarterSurvivalMoveManager;

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
        $mySnakeLength = $this->getSnakeLength($snakeId);
        foreach ($this->getSnakes() as $snake) {
            $collision_body = $snake['body'];
            if ($snake['id'] == $snakeId) {
                $collision_body = array_slice($collision_body, 1);
            }
            if ($this->getSnakeLength($snake['id']) < $mySnakeLength) {
                $collision_body = array_slice($collision_body, 1);
            }
            if (in_array(['x' => $x, 'y' => $y], $collision_body)) {
                return false;
            }
        }
        return true;
    }

    public function getNextMoveGameData(string $move): GameData | NULL {
        $ImpossibleMoveManager = new ImpossibleMoveManager($this);
        $newGameData = $this->data;
        $newHealth = $newGameData['you']['health'] - 1;
        if (in_array($newGameData['you']['head'], $newGameData['board']['food'])) {
            $newHealth = 100;
        }
        if ($newHealth <= 0) {
            return NULL;
        }

        $new_head = $this->getNextMoveHead($newGameData['you']['head'], $move);
        $new_body = $this->getNextMoveBody($newGameData['you']['body'], $new_head);
        $newGameData['you']['head'] = $new_head;
        $newGameData['you']['body'] = $new_body;
        $newGameData['you']['health'] = $newHealth;

        $my_id = $newGameData['you']['id'];
        for ($i = 0; $i < count($newGameData['board']['snakes']); $i++) {
            if ($newGameData['board']['snakes'][$i]['id'] == $my_id) {
                $newGameData['board']['snakes'][$i]['head'] = $new_head;
                $newGameData['board']['snakes'][$i]['body'] = $new_body;
                $newGameData['board']['snakes'][$i]['health'] = $newHealth;
            }
        }

        $dead_snakes = [];
        for ($i = 0; $i < count($newGameData['board']['snakes']); $i++) {
            if ($my_id == $newGameData['board']['snakes'][$i]['id']) {
                continue;
            }
            $snake = $newGameData['board']['snakes'][$i];
            $possibleEnemyMoves = $ImpossibleMoveManager->getMoves($snake['id']);

            if (empty($possibleEnemyMoves)) {
                $dead_snakes[] = $snake['id'];
                continue;
            }
            $bestEnemyMove = count($possibleEnemyMoves) == 1 ? $possibleEnemyMoves : [];
            $bestEnemyMoveScore = 0;

            if (count($possibleEnemyMoves) > 1) {
                foreach ($possibleEnemyMoves as $possibleEnemyMove) {
                    $thisMoveGameData = $newGameData;
                    $new_head = $this->getNextMoveHead($snake['head'], $possibleEnemyMove);
                    $new_body = $this->getNextMoveBody($snake['body'], $new_head);
                    $thisMoveGameData['board']['snakes'][$i]['head'] = $new_head;
                    $thisMoveGameData['board']['snakes'][$i]['body'] = $new_body;
                    $thisMoveGameData['board']['snakes'] = array_filter($thisMoveGameData['board']['snakes'], function ($newsnake) use ($dead_snakes) {
                        return !in_array($newsnake['id'], $dead_snakes);
                    });
                    $thisMoveGameData['board']['snakes'] = array_values($thisMoveGameData['board']['snakes']);
                    $thisMoveGameDataObject = new GameData($thisMoveGameData);
                    $thisMoveLookahead = new LookAhead($thisMoveGameDataObject, 1);
                    $smarterSurvivalMoveManager = new SmarterSurvivalMoveManager($this);
                    $score = $smarterSurvivalMoveManager->scoreLookAhead($thisMoveLookahead, FALSE, $snake['id']);
                    if ($score > $bestEnemyMoveScore) {
                        $bestEnemyMove = [$possibleEnemyMove];
                        $bestEnemyMoveScore = $score;
                    } elseif ($score == $bestEnemyMoveScore) {
                        $bestEnemyMove[] = $possibleEnemyMove;
                    }
                }
            }

            $bestEnemyMove = $bestEnemyMove[array_rand($bestEnemyMove)];
            $new_head = $this->getNextMoveHead($snake['head'], $bestEnemyMove);
            $new_body = $this->getNextMoveBody($snake['body'], $new_head);
            $newGameData['board']['snakes'][$i]['head'] = $new_head;
            $newGameData['board']['snakes'][$i]['body'] = $new_body;
        }

        $newGameData['board']['snakes'] = array_filter($newGameData['board']['snakes'], function ($newsnake) use ($dead_snakes) {
            return !in_array($newsnake['id'], $dead_snakes);
        });
        $newGameData['board']['snakes'] = array_values($newGameData['board']['snakes']);
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
        $visited[$startKey] = 1;

        // Queue holds entries like [x, y, depth]
        $queue = [[$head['x'], $head['y'], 0]];

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
                    $visited[$key]++;
                    continue;
                }

                // Mark visited and add to the queue
                $visited[$key] = 1;
                $queue[] = [$nx, $ny, $depth + 1];
            }
        }
        $weightedAccessibleSquares = 0;
        foreach ($visited as $squareValue) {
            $weightedAccessibleSquares += $squareValue;
        }
        return $weightedAccessibleSquares;
    }

}