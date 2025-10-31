<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler\Tests\Helper;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SfResponse;

/**
 * 测试用的HttpMessageFactory复合实现
 *
 * @internal
 */
final class TestHttpMessageFactory implements HttpMessageFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface
{
    public function createResponse(SfResponse $symfonyResponse): ResponseInterface
    {
        return new Response(
            $symfonyResponse->getStatusCode(),
            [],
            false !== $symfonyResponse->getContent() ? $symfonyResponse->getContent() : ''
        );
    }

    public function createRequest(Request $symfonyRequest): ServerRequestInterface
    {
        return new ServerRequest(
            $symfonyRequest->getMethod(),
            $symfonyRequest->getUri(),
            $symfonyRequest->server->all()
        );
    }

    /**
     * @param array $serverParams
     * @phpstan-ignore-next-line missingType.iterableValue
     */
    public function createServerRequest(string $method, mixed $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, $serverParams);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::create($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = fopen($filename, $mode);
        if (false === $resource) {
            throw new \RuntimeException('Could not open file: ' . $filename);
        }

        return Stream::create($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return Stream::create($resource);
    }
}
