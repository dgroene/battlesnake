<?php

use Battlesnake\Game\GameData;
use Battlesnake\Game\GameManager;
use Battlesnake\Moves\api;
use Battlesnake\Moves\FoodMoveManager;
use Battlesnake\Moves\ImpossibleMoveManager;
use Battlesnake\Moves\ScaredyCatMoveManager;

require_once('./vendor/autoload.php');

$api = new api();
/**
 * Basic index.php router that checks the incoming REQUEST_URI and decides what response to send.
 *
 * Simple API response functions used here are located in api.php.
 *
 * Most of your snake implementation will need to happen in the "/move" command.
 */

// Get the requested URI without any query parameters on the end
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
if ($requestUri == '/')  
{   //Index Section
    $apiversion = "1";
    $author     = "";           // TODO: Your Battlesnake Username
    $color      = "#065535";    // TODO: Personalize
    $head       = "caffeine";    // TODO: Personalize
    $tail       = "coffee";    // TODO: Personalize

    $api->indexResponse($apiversion,$author,$color,$head, $tail);
}
elseif ($requestUri == '/start')
{
    // read the incoming request body stream and decode the JSON
    $data = json_decode(file_get_contents('php://input'));

    // TODO - if you have a stateful snake, you could do initialization work here
    $api->startResponse();
}
elseif ($requestUri == '/move')
{   //Move Section
    // read the incoming request body stream and decode the JSON
    $data = json_decode(file_get_contents('php://input'), true);
    // Log the data so it will show up in cloudwatch
    error_log(json_encode($data));

    $gameManager = new GameManager($data);

    $api->moveResponse($gameManager->getMove());
}
elseif ($requestUri == '/end')
{
     // read the incoming request body stream and decode the JSON
     $data = json_decode(file_get_contents('php://input'));

     // TODO - if you have a stateful snake, you could do finalize work here
    $api->endResponse();
}
else
{
    header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
}
