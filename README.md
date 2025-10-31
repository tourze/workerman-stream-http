# Workerman Stream HTTP

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-stream-http.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-stream-http)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg?style=flat-square)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

A streaming HTTP protocol implementation for Workerman that processes HTTP requests progressively as data arrives, providing better memory efficiency and performance for high-concurrency scenarios.

## Features

- 🚀 **Streaming HTTP processing** - Processes requests in stages (request line, headers, body)
- 📋 **PSR-7 compliant** - Full PSR-7 request and response handling
- 📦 **Chunked transfer encoding** - Support for HTTP chunked transfer encoding
- ⚡ **High performance** - Optimized for Workerman's event-driven architecture
- 🔄 **Keep-alive support** - HTTP/1.1 persistent connections
- 🔧 **Extensible** - Support for custom HTTP methods
- 🛡️ **Security features** - Built-in request size limits and error handling
- 🧪 **Well-tested** - Comprehensive test coverage

## Dependencies

This package requires:

- PHP 8.1 or higher
- [Workerman](https://github.com/walkor/Workerman) 5.1 or higher
- [nyholm/psr7](https://github.com/Nyholm/psr7) for PSR-7 implementation
- [tourze/enum-extra](https://github.com/tourze/enum-extra) for enhanced enum support

## Installation

```bash
composer require tourze/workerman-stream-http
```

## Quick Start

```php
<?php

use Nyholm\Psr7\Response;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require_once 'vendor/autoload.php';

// Create a worker using TCP
$worker = new Worker("tcp://0.0.0.0:8080");

// Set the protocol to our HTTP implementation
$worker->protocol = HttpProtocol::class;
$worker->name = 'StreamHTTPServer';

// Set the number of processes
$worker->count = 4;

// Handle requests
$worker->onMessage = function(TcpConnection $connection, $request) {
    // $request is a PSR-7 Request object
    $method = $request->getMethod();
    $uri = (string)$request->getUri();
    $headers = $request->getHeaders();

    // Create a PSR-7 response
    $response = new Response(
        200,
        ['Content-Type' => 'text/plain'],
        "Hello World!\nMethod: $method\nURI: $uri"
    );

    // Send the response back to the client
    $connection->send($response);
};

// Run the worker
Worker::runAll();
```

## Documentation

### Streaming Request Handling

This implementation processes HTTP requests in stages:

1. **Request Line** - Processes HTTP method, URI, and protocol version
2. **Headers** - Processes HTTP headers
3. **Body** - Processes HTTP body (if any)

Each stage emits a PSR-7 Request object that progressively builds up as more data is received.

### Adding Custom HTTP Methods

You can add support for custom HTTP methods:

```php
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

// Add support for a custom method
HttpProtocol::addAllowedMethod('CUSTOM');

// Get all currently supported methods
$methods = HttpProtocol::getAllowedMethods();
```

### Error Handling

The implementation provides automatic error handling for invalid requests:

- Invalid request lines (400 Bad Request)
- Oversized headers (431 Request Header Fields Too Large)
- Oversized body (413 Request Entity Too Large)
- Request timeout (408 Request Timeout)
- Server errors (500 Internal Server Error)

## Advanced Usage

### Custom Protocol Configuration

You can customize the protocol behavior by extending the HttpProtocol class:

```php
<?php

use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

class CustomHttpProtocol extends HttpProtocol
{
    // Override default behavior
    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        // Custom input processing logic
        return parent::input($buffer, $connection);
    }
}
```

### Request/Response Middleware

You can implement middleware pattern for request/response processing:

```php
<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthMiddleware
{
    public function process(RequestInterface $request): RequestInterface
    {
        // Authentication logic
        return $request;
    }
}

$worker->onMessage = function($connection, $request) {
    $middleware = new AuthMiddleware();
    $request = $middleware->process($request);
    
    // Handle authenticated request
    // ...
};
```

### Performance Tuning

For high-performance scenarios, consider these optimizations:

```php
<?php

// Increase worker processes
$worker->count = 8;

// Set connection limits
$worker->connections = 1000;

// Configure buffer sizes
$worker->sendMsgToWorker = true;

// Enable SSL for secure connections
$worker = new Worker("tcp://0.0.0.0:443", [
    'ssl' => [
        'local_cert' => '/path/to/cert.pem',
        'local_pk' => '/path/to/private.key',
    ]
]);
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
