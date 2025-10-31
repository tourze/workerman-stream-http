# Workerman Stream HTTP

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-stream-http.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-stream-http)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg?style=flat-square)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Workerman的流式HTTP协议实现，可以在数据到达时逐步处理HTTP请求，为高并发场景提供更好的内存效率和性能。

## 功能特性

- 🚀 **流式HTTP处理** - 分阶段处理请求（请求行、请求头、请求体）
- 📋 **PSR-7兼容** - 完全符合PSR-7请求和响应处理标准
- 📦 **分块传输编码** - 支持HTTP分块传输编码
- ⚡ **高性能** - 针对Workerman事件驱动架构优化
- 🔄 **Keep-alive支持** - HTTP/1.1持久连接
- 🔧 **可扩展** - 支持自定义HTTP方法
- 🛡️ **安全特性** - 内置请求大小限制和错误处理
- 🧪 **测试完善** - 全面的测试覆盖

## 依赖要求

本包需要：

- PHP 8.1 或更高版本
- [Workerman](https://github.com/walkor/Workerman) 5.1 或更高版本
- [nyholm/psr7](https://github.com/Nyholm/psr7) 用于 PSR-7 实现
- [tourze/enum-extra](https://github.com/tourze/enum-extra) 用于增强枚举支持

## 安装

```bash
composer require tourze/workerman-stream-http
```

## 快速开始

```php
<?php

use Nyholm\Psr7\Response;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require_once 'vendor/autoload.php';

// 创建使用TCP的worker
$worker = new Worker("tcp://0.0.0.0:8080");

// 设置协议为我们的HTTP实现
$worker->protocol = HttpProtocol::class;
$worker->name = 'StreamHTTPServer';

// 设置进程数
$worker->count = 4;

// 处理请求
$worker->onMessage = function(TcpConnection $connection, $request) {
    // $request是一个PSR-7 Request对象
    $method = $request->getMethod();
    $uri = (string)$request->getUri();
    $headers = $request->getHeaders();

    // 创建PSR-7响应
    $response = new Response(
        200,
        ['Content-Type' => 'text/plain'],
        "你好，世界！\n方法：$method\nURI：$uri"
    );

    // 发送响应回客户端
    $connection->send($response);
};

// 运行worker
Worker::runAll();
```

## 文档

### 流式请求处理

本实现分阶段处理HTTP请求：

1. **请求行** - 处理HTTP方法、URI和协议版本
2. **请求头** - 处理HTTP头部
3. **请求体** - 处理HTTP正文（如果有）

每个阶段都会发出一个PSR-7 Request对象，随着接收到更多数据而逐步构建。

### 添加自定义HTTP方法

您可以添加对自定义HTTP方法的支持：

```php
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

// 添加对自定义方法的支持
HttpProtocol::addAllowedMethod('CUSTOM');

// 获取当前支持的所有方法
$methods = HttpProtocol::getAllowedMethods();
```

### 错误处理

该实现为无效请求提供自动错误处理：

- 无效的请求行（400 Bad Request）
- 超大的请求头（431 Request Header Fields Too Large）
- 超大的请求体（413 Request Entity Too Large）
- 请求超时（408 Request Timeout）
- 服务器错误（500 Internal Server Error）

## 高级用法

### 自定义协议配置

您可以通过扩展 HttpProtocol 类来自定义协议行为：

```php
<?php

use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

class CustomHttpProtocol extends HttpProtocol
{
    // 重写默认行为
    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        // 自定义输入处理逻辑
        return parent::input($buffer, $connection);
    }
}
```

### 请求/响应中间件

您可以实现中间件模式来处理请求/响应：

```php
<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthMiddleware
{
    public function process(RequestInterface $request): RequestInterface
    {
        // 认证逻辑
        return $request;
    }
}

$worker->onMessage = function($connection, $request) {
    $middleware = new AuthMiddleware();
    $request = $middleware->process($request);
    
    // 处理已认证的请求
    // ...
};
```

### 性能调优

对于高性能场景，考虑以下优化：

```php
<?php

// 增加工作进程数
$worker->count = 8;

// 设置连接限制
$worker->connections = 1000;

// 配置缓冲区大小
$worker->sendMsgToWorker = true;

// 启用SSL安全连接
$worker = new Worker("tcp://0.0.0.0:443", [
    'ssl' => [
        'local_cert' => '/path/to/cert.pem',
        'local_pk' => '/path/to/private.key',
    ]
]);
```

## 贡献

请查看[CONTRIBUTING.md](CONTRIBUTING.md)了解详情。

## 许可证

MIT许可证（MIT）。请查看[许可证文件](LICENSE)了解更多信息。
