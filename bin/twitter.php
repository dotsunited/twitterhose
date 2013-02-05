<?php

require __DIR__.'/../vendor/autoload.php';

$logger = new Monolog\Logger('twitterhose');
$logger->pushHandler(new Monolog\Handler\StreamHandler('php://output', Monolog\Logger::DEBUG));

$shortopts  = "";
$longopts  = array(
    "username:",
    "password:",
    "follow::",
    "track::",
    "locations::",
);
$options = getopt($shortopts, $longopts);

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$httpClientfactory = new React\HttpClient\Factory();
$httpClient = $httpClientfactory->create($loop, $dnsResolver);

$dataVars = $options;
unset($dataVars['username'], $dataVars['password']);
$data = http_build_query($dataVars, null, '&');

$logger->info('Connecting to filter streaming api with ' . urldecode($data));

$request = $httpClient->request('POST', 'https://stream.twitter.com/1.1/statuses/filter.json ', array(
    'Authorization' => 'Basic ' . base64_encode($options['username'] . ':' . $options['password']),
    'Content-type' => 'application/x-www-form-urlencoded',
    'Content-length' => strlen($data),
    'Connection-type' => 'Close'
));
$request->write($data);

$zmqContext = new React\ZMQ\Context($loop);
$zmqSocket = $zmqContext->getSocket(\ZMQ::SOCKET_PUSH, 'twitterhose');
$zmqSocket->connect("tcp://127.0.0.1:5555");

$buffer = '';

$request->on('response', function ($response) use ($zmqSocket, &$buffer, $logger) {
    $response->on('data', function ($data) use ($zmqSocket, &$buffer, $logger) {
        if ('' === trim($data)) {
            return;
        }

        $buffer .= $data;

        if (false === ($eol = strpos($buffer, "\r\n"))) {
            return;
        }

        $tweet = substr($buffer, 0, $eol);
        $buffer = substr($buffer, $eol + 2); // consume off buffer, + 2 = "\r\n"

        $zmqSocket->send($tweet);
    });
});

$request->on('end', function ($error) use ($logger) {
    $logger->info('Connection closed with "' . $error . '"');
});

$request->end();
$loop->run();
