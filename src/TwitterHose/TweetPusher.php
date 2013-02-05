<?php

namespace TwitterHose;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class TweetPusher implements MessageComponentInterface
{
    private $logger;
    protected $clients;
    protected $buffer = '';

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
        $this->clients = new \SplObjectStorage;
    }

    public function onTweet($data)
    {
        $tweet = json_decode($data, true);

        if (isset($tweet['limit'])) {
            // It's a limit response
            $this->logger->info("Skipping limit response ($data)");

            return;
        }

        /*$numClients = count($this->clients);

        $this->logger->info(
            sprintf(
                'Sending tweet "%s" to %d connection%s',
                $tweet['id'],
                $numClients,
                $numClients == 1 ? '' : 's'
            )
        );*/

        $msg = json_encode(array(
            'type' => 'tweet',
            'tweet' => $tweet,
        ));

        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        $this->logger->info("New connection! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $error = json_encode(array(
           'type' => 'error',
            'message' => 'You are not allowed to make calls',
        ));

        $this->logger->info("Unexpected reception of message \"{$msg}\" from connection {$conn->resourceId}");

        $conn->send($error)->close();
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        $this->logger->info("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error("An error has occurred: {$e->getMessage()}");

        $conn->close();
    }
}
