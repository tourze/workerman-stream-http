<?php

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require_once __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker("tcp://127.0.0.1:8087");
$worker->protocol = HttpProtocol::class;
$worker->name = 'HttpTestServer';

// 设置进程数
$worker->count = 1;

// 处理请求
$worker->onMessage = function(TcpConnection $connection, Request $request) {
    // 根据请求阶段输出不同的日志
    $phase = match(true) {
        empty($request->getHeaders()) => 'REQUEST_LINE',
        $request->getBody()->getSize() === 0 => 'HEADERS',
        default => 'BODY'
    };

    Worker::log(sprintf(
        '[%s] Received %s request: %s %s',
        $phase,
        $request->getMethod(),
        (string)$request->getUri(),
        $phase === 'HEADERS' ? sprintf('(headers: %d)', count($request->getHeaders())) :
            ($phase === 'BODY' ? sprintf('(body length: %d)', $request->getBody()->getSize()) : '')
    ));

    // 在最后一个阶段发送响应
    if ($phase === 'BODY' ||
        ($phase === 'HEADERS' && in_array($request->getMethod(), ['GET', 'HEAD', 'DELETE', 'OPTIONS']))) {
        $body = $request->getBody();
        $body->rewind(); // 确保从头开始读取

        $responseBody = json_encode([
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
            'headers' => $request->getHeaders(),
            'body' => (string)$body,
            'protocol' => $request->getProtocolVersion(),
        ]);

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody,
            $request->getProtocolVersion() // 使用请求的协议版本
        );

        $connection->send($response);
    }
};

Worker::runAll();
