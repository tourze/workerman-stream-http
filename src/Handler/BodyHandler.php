<?php

namespace Tourze\Workerman\StreamHTTP\Handler;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

class BodyHandler implements RequestHandlerInterface
{
    private Psr17Factory $psr17Factory;

    public function __construct(Psr17Factory $psr17Factory)
    {
        $this->psr17Factory = $psr17Factory;
    }

    public function processInput(string $buffer): int|false
    {
        // 如果是GET/HEAD/DELETE/OPTIONS请求，不需要处理body
        if ($this->shouldSkipBody()) {
            return strlen($buffer);
        }

        // 获取Content-Length
        $contentLength = (int)($this->getCurrentRequest()?->getHeaderLine('Content-Length') ?? 0);
        if ($contentLength === 0) {
            // 如果没有Content-Length，检查是否是chunked编码
            $transferEncoding = strtolower($this->getCurrentRequest()?->getHeaderLine('Transfer-Encoding') ?? '');
            if ($transferEncoding === 'chunked') {
                // 处理chunked编码
                return $this->processChunkedInput($buffer);
            }
            // 如果既没有Content-Length也不是chunked，则认为没有body
            return strlen($buffer);
        }

        // 检查body长度
        $bodyLength = strlen($buffer);
        if ($bodyLength < $contentLength) {
            // 如果body长度超过限制，返回错误
            if ($contentLength > 2097152) { // 2MB
                return false;
            }
            // 否则继续等待数据
            return 0;
        }

        // 返回需要的长度
        return $contentLength;
    }

    private function processChunkedInput(string $buffer): int|false
    {
        $pos = 0;
        $totalLength = 0;

        while ($pos < strlen($buffer)) {
            // 读取chunk大小
            $lineEndPos = strpos($buffer, HttpProtocol::CRLF, $pos);
            if ($lineEndPos === false) {
                return 0; // 需要更多数据
            }

            $line = substr($buffer, $pos, $lineEndPos - $pos);
            $chunkSize = hexdec(trim($line));

            if ($chunkSize === 0) {
                // 最后一个chunk，检查是否有结尾的CRLF
                if (substr($buffer, $lineEndPos + 2, 2) !== HttpProtocol::CRLF) {
                    return 0; // 需要更多数据
                }
                return $lineEndPos + 4; // +4 for final CRLF
            }

            // 检查chunk数据是否完整
            $chunkDataEnd = $lineEndPos + 2 + $chunkSize + 2;
            if ($chunkDataEnd > strlen($buffer)) {
                return 0; // 需要更多数据
            }

            // 移动到下一个chunk
            $pos = $chunkDataEnd;
            $totalLength += $chunkSize;

            // 检查大小限制
            if ($totalLength > 2097152) { // 2MB
                return false;
            }
        }

        return 0; // 需要更多数据
    }

    public function process(string $buffer, HttpContext $ctx): Request
    {
        $request = $ctx->request;
        if ($request === null) {
            throw new \RuntimeException('No request in context');
        }

        // 如果是GET/HEAD/DELETE/OPTIONS请求，不需要处理body
        if ($this->shouldSkipBody()) {
            return $request;
        }

        // 检查传输编码
        $transferEncoding = strtolower($request->getHeaderLine('Transfer-Encoding'));
        if ($transferEncoding === 'chunked') {
            $body = $this->decodeChunkedBody($buffer);
        } else {
            $body = $buffer;
        }

        // 创建body流
        $stream = $this->psr17Factory->createStream($body);
        return $request->withBody($stream);
    }

    private function decodeChunkedBody(string $body): string
    {
        $result = '';
        $pos = 0;

        while ($pos < strlen($body)) {
            // 读取chunk大小
            $lineEndPos = strpos($body, HttpProtocol::CRLF, $pos);
            if ($lineEndPos === false) {
                break;
            }

            $line = substr($body, $pos, $lineEndPos - $pos);
            $chunkSize = hexdec(trim($line));

            if ($chunkSize === 0) {
                break;
            }

            // 读取chunk数据
            $dataPos = $lineEndPos + 2;
            $result .= substr($body, $dataPos, $chunkSize);

            // 移动到下一个chunk
            $pos = $dataPos + $chunkSize + 2; // +2 for CRLF after chunk data
        }

        return $result;
    }

    private function getCurrentRequest(): ?Request
    {
        return $GLOBALS['_current_request'] ?? null;
    }

    private function shouldSkipBody(): bool
    {
        $request = $this->getCurrentRequest();
        if ($request === null) {
            return true;
        }

        return in_array($request->getMethod(), ['GET', 'HEAD', 'DELETE', 'OPTIONS'], true);
    }
}
