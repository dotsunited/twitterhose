<?php

require __DIR__.'/../vendor/autoload.php';

$logger = new Monolog\Logger('twitterhose');
$logger->pushHandler(new Monolog\Handler\StreamHandler('php://output', Monolog\Logger::DEBUG));

$loop   = React\EventLoop\Factory::create();
$pusher = new TwitterHose\TweetPusher($logger);

$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:5555');
$pull->on('message', array($pusher, 'onTweet'));

$socketServer = new React\Socket\Server($loop);
$socketServer->listen(1337, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
$server = new Ratchet\Server\IoServer(
    new Ratchet\WebSocket\WsServer($pusher),
    $socketServer,
    $loop
);

$server->run();
