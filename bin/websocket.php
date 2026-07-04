<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\ConsultationSignaling;

// Ensure this runs without timeout
set_time_limit(0);

$port = 8080;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ConsultationSignaling()
        )
    ),
    $port
);

echo "WebSocket server started on port {$port}...\n";
$server->run();
