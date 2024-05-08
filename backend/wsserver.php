<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;

require_once __DIR__ . '/vendor/autoload.php';

$ws_worker = new Worker("websocket://0.0.0.0:2346");

$ws_worker->count = 1;

$timer = null;
$intervalId = null;

function generateColor() {
    // Generate a random color
    $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

    return $color;
}


$ws_worker->onConnect = function($connection) use ($ws_worker) {
    $uuid = uniqid();
    $connection->uuid = $uuid;
    $color = generateColor();
    $connection->color = $color;  // Store the uuid in the connection object
    $connection->send(json_encode(["uuid" => $uuid, "color" => $color]));
    echo ("New Connection!");

    foreach ($ws_worker->connections as $client) {
        if (isset($client->playerData)) {
            $connection->send(json_encode($client->playerData));
        }
    }
};

$ws_worker->onMessage = function($connection, $data) use ($ws_worker, &$timer, &$intervalId) {
    $playerData = json_decode($data);
    $playerData->color = $connection->color;
    $playerData->uuid = $connection->uuid;
    if (isset($playerData->trail)) {
        $connection->trailLength = count($playerData->trail);
       
    }
    if (isset($playerData->killed) && $playerData->killed === true) {
        echo("_MATOMATO_");
        // The player was hit, so find the winner
        foreach ($ws_worker->connections as $client) {
            if ($client->uuid !== $connection->uuid) {
                $winner = $client->uuid; // Set the winner to be the player who is not hit
                foreach ($ws_worker->connections as $client) {
                    $client->send(json_encode(["winner" => $winner]));
                }
            }
        }
    }

    if (isset($playerData->points)) {
        // Points data received, so broadcast it to all other clients
        foreach ($ws_worker->connections as $client) {
          if ($client->uuid !== $connection->uuid) {
            $client->send(json_encode(["uuid" => $connection->uuid, "points" => $playerData->points]));
          }
        }
      }

    if (isset($playerData->timer) && $intervalId === null) {
        $timer = $playerData->timer;
        $intervalId = \Workerman\Lib\Timer::add(1, function() use ($ws_worker, &$timer, &$intervalId) {
            if ($timer > 0) {
                $timer--;
            } else {
                \Workerman\Lib\Timer::del($intervalId);
                $intervalId = null;

                $maxTrailLength = 0;
                $winner = null;

                foreach ($ws_worker->connections as $client) {
                    if ($client->trailLength > $maxTrailLength) {
                        $maxTrailLength = $client->trailLength;
                        $winner = $client;
                    }
                }

                if ($winner !== null) {
                    echo 'The winner is: ' . $winner->uuid;

                    foreach ($ws_worker->connections as $client) {
                        $client->send(json_encode(["winner" => $winner->uuid]));
                    }
                }
            }

            foreach ($ws_worker->connections as $client) {
                $client->send(json_encode(["timer" => $timer]));
            }
        });
    } else {
        $connection->playerData = $playerData;

        foreach ($ws_worker->connections as $client) {
            $client->send(json_encode($playerData));
        }
    }
};

$ws_worker->onClose = function($connection) {
    echo "Connection closed\n";
};

Worker::runAll();
?>
