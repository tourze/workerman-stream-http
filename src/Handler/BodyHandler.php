<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Handler;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Exception\ContextException;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

class BodyHandler implements RequestHandlerInterface
{
    public const MAX_BODY_SIZE = 2097152; // 2MB

    private ?Request $currentRequest = null;

    public function __construct(
        private readonly Psr17Factory $psr17Factory,
        ?Request $initialRequest = null,
    ) {
        $this->currentRequest = $initialRequest;
    }

    /**
     * 处理输入缓冲区并判断是否有完整的请求体
     *
     * @param string $buffer 要处理的输入缓冲区
     * @return int|false 返回需要的请求体长度，0表示需要更多数据，false表示错误
     */
    public function processInput(string $buffer): int|false
    {
        // 如果是GET/HEAD/DELETE/OPTIONS请求，不需要处理body
        if ($this->shouldSkipBody()) {
            return strlen($buffer);
        }

        // 获取Content-Length
        $contentLength = (int) ($this->getCurrentRequest()?->getHeaderLine('Content-Length') ?? 0);
        if (0 === $contentLength) {
            // 如果没有Content-Length，检查是否是chunked编码
            $transferEncoding = strtolower($this->getCurrentRequest()?->getHeaderLine('Transfer-Encoding') ?? '');
            if ('chunked' === $transferEncoding) {
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
            if ($contentLength > self::MAX_BODY_SIZE) {
                return false;
            }

            // 否则继续等待数据
            return 0;
        }

        // 返回需要的长度
        return $contentLength;
    }

    /**
     * 处理分块传输编码的输入
     *
     * @param string $buffer 要处理的输入缓冲区
     * @return int|false 返回需要的总长度，0表示需要更多数据，false表示错误
     */
    private function processChunkedInput(string $buffer): int|false
    {
        $pos = 0;
        $totalLength = 0;

        while ($pos < strlen($buffer)) {
            $chunkResult = $this->processChunk($buffer, $pos, $totalLength);

            if (!$chunkResult['continue']) {
                return $chunkResult['result'];
            }

            $pos = $chunkResult['pos'] ?? $pos;
            $totalLength = $chunkResult['totalLength'] ?? $totalLength;
        }

        return 0; // 需要更多数据
    }

    /**
     * @return array{continue: bool, result: int|false, pos?: int, totalLength?: int}
     */
    private function processChunk(string $buffer, int $pos, int $totalLength): array
    {
        // 读取chunk大小行
        $chunkSizeLine = $this->readChunkSizeLine($buffer, $pos);
        if (false === $chunkSizeLine) {
            return ['continue' => false, 'result' => 0]; // 需要更多数据
        }

        $chunkSize = hexdec(trim($chunkSizeLine));

        // 处理最后一个chunk（大小为0）
        if (0 === $chunkSize) {
            return $this->handleFinalChunk($buffer, $pos + strlen($chunkSizeLine) + 2);
        }

        // 处理常规数据chunk
        return $this->handleRegularChunk($buffer, $pos + strlen($chunkSizeLine) + 2, (int) $chunkSize, $totalLength);
    }

    /**
     * 读取chunk大小行
     */
    private function readChunkSizeLine(string $buffer, int $pos): string|false
    {
        $lineEndPos = strpos($buffer, HttpProtocol::CRLF, $pos);
        if (false === $lineEndPos) {
            return false;
        }

        return substr($buffer, $pos, $lineEndPos - $pos);
    }

    /**
     * 处理最后一个chunk（chunk大小为0）
     *
     * @return array{continue: false, result: int}
     */
    private function handleFinalChunk(string $buffer, int $finalChunkStartPos): array
    {
        // 最后一个chunk后必须有CRLF结尾
        $finalCRLFPos = $finalChunkStartPos + 2;
        if ($finalCRLFPos + 2 > strlen($buffer)) {
            return ['continue' => false, 'result' => 0]; // 需要更多数据
        }

        if (HttpProtocol::CRLF !== substr($buffer, $finalCRLFPos, 2)) {
            return ['continue' => false, 'result' => 0]; // 格式错误，需要更多数据
        }

        return ['continue' => false, 'result' => $finalCRLFPos + 2];
    }

    /**
     * 处理常规数据chunk
     *
     * @return array{continue: bool, result: int|false, pos?: int, totalLength?: int}
     */
    private function handleRegularChunk(string $buffer, int $chunkDataStartPos, int $chunkSize, int $totalLength): array
    {
        // 检查chunk数据是否完整（数据 + CRLF）
        $chunkEndPos = $chunkDataStartPos + $chunkSize + 2;
        if ($chunkEndPos > strlen($buffer)) {
            return ['continue' => false, 'result' => 0]; // 需要更多数据
        }

        // 检查总大小限制
        $newTotalLength = $totalLength + $chunkSize;
        if ($newTotalLength > self::MAX_BODY_SIZE) {
            return ['continue' => false, 'result' => false];
        }

        return [
            'continue' => true,
            'result' => 0, // Continue processing
            'pos' => $chunkEndPos,
            'totalLength' => $newTotalLength,
        ];
    }

    /**
     * 处理请求体数据并更新HTTP请求对象
     *
     * @param string $buffer 包含请求体数据的输入缓冲区
     * @param HttpContext $ctx 要更新的HTTP上下文
     * @return Request 更新的HTTP请求对象
     * @throws ContextException 当上下文中没有找到请求时
     */
    public function process(string $buffer, HttpContext $ctx): Request
    {
        $request = $ctx->request;
        if (null === $request) {
            throw new ContextException('No request in context');
        }

        // 如果是GET/HEAD/DELETE/OPTIONS请求，不需要处理body
        if ($this->shouldSkipBody()) {
            return $request;
        }

        // 检查传输编码
        $transferEncoding = strtolower($request->getHeaderLine('Transfer-Encoding'));
        if ('chunked' === $transferEncoding) {
            $body = $this->decodeChunkedBody($buffer);
        } else {
            $body = $buffer;
        }

        // 创建body流
        $stream = $this->psr17Factory->createStream($body);

        return $request->withBody($stream);
    }

    /**
     * 解码分块传输编码的请求体
     *
     * @param string $body 分块编码的请求体
     * @return string 解码后的请求体
     */
    private function decodeChunkedBody(string $body): string
    {
        $result = '';
        $pos = 0;
        $bodyLength = strlen($body);

        while ($pos < $bodyLength) {
            // 读取chunk大小
            $lineEndPos = strpos($body, HttpProtocol::CRLF, (int) $pos);
            if (false === $lineEndPos) {
                break;
            }

            $line = substr($body, (int) $pos, (int) ($lineEndPos - $pos));
            $chunkSize = hexdec(trim($line));

            if (0 === $chunkSize) {
                break;
            }

            // 读取chunk数据
            $dataPos = $lineEndPos + 2;
            $result .= substr($body, $dataPos, (int) $chunkSize);

            // 移动到下一个chunk
            $pos = $dataPos + $chunkSize + 2; // +2 for CRLF after chunk data
        }

        return $result;
    }

    /**
     * 设置当前请求用于请求体处理
     *
     * @param Request|null $request 当前的HTTP请求
     */
    public function setCurrentRequest(?Request $request): void
    {
        $this->currentRequest = $request;
    }

    private function getCurrentRequest(): ?Request
    {
        return $this->currentRequest;
    }

    private function shouldSkipBody(): bool
    {
        $request = $this->getCurrentRequest();
        if (null === $request) {
            return true;
        }

        return in_array($request->getMethod(), ['GET', 'HEAD', 'DELETE', 'OPTIONS'], true);
    }
}
