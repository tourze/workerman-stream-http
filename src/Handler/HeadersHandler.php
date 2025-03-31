<?php

namespace Tourze\Workerman\StreamHTTP\Handler;

use Nyholm\Psr7\Request;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

class HeadersHandler implements RequestHandlerInterface
{
    public function processInput(string $buffer): int|false
    {
        // 找到头部结束标记（两个CRLF）的位置
        $pos = strpos($buffer, HttpProtocol::CRLF2);

        // 如果没找到头部结束标记
        if ($pos === false) {
            // 如果已经超过最大长度，返回false表示错误
            if (strlen($buffer) >= 16384) {
                return false;
            }
            // 否则返回0表示需要更多数据
            return 0;
        }

        // 返回到头部结束位置的长度，这样Workerman就会截取这么长的数据
        return $pos + strlen(HttpProtocol::CRLF2);
    }

    public function process(string $buffer, HttpContext $ctx): Request
    {
        // 解析所有头部行
        $headerLines = explode(HttpProtocol::CRLF, rtrim($buffer, HttpProtocol::CRLF2));
        $request = $ctx->request;

        foreach ($headerLines as $line) {
            if (empty($line)) continue;
            if (str_contains($line, ':')) {
                list($name, $value) = explode(':', $line, 2);
                $request = $request->withHeader(trim($name), trim($value));
            }
        }

        $ctx->request = $request;
        $ctx->phase = HttpPhase::BODY;
        return $request;
    }
}
