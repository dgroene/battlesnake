<?php

namespace Battlesnake\Moves;

use Battlesnake\Game\GameData;

class LookAhead
{

    public function __construct(public GameData | NULL $gameData, public int $depth = 1) {
    }
}