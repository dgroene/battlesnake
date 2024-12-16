<?php

namespace Battlesnake\Enums;

enum MoveDirections: string {
    public const UP = 'up';
    public const DOWN = 'down';
    public const LEFT = 'left';
    public const RIGHT = 'right';

    public function getAllMoves(): array {
        return [MoveDirections::UP, MoveDirections::DOWN, MoveDirections::LEFT, MoveDirections::RIGHT];
    }
}
