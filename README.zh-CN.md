# Workerman Stream HTTP

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-stream-http.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-stream-http)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg?style=flat-square)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Workerman的流式HTTP协议实现。

## 功能特性

- 为Workerman提供流式HTTP请求处理
- 符合PSR-7标准的请求和响应处理
- 支持分块传输编码
- 高性能HTTP解析
- 支持连接保持活动（keep-alive）
- 可扩展的HTTP方法支持

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

## 贡献

请查看[CONTRIBUTING.md](CONTRIBUTING.md)了解详情。

## 许可证

MIT许可证（MIT）。请查看[许可证文件](LICENSE)了解更多信息。
